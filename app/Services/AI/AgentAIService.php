<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class AgentAIService
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    public function getConclusion(
        string $laptopName,
        float $score,
        string $status,
        ?string $description,
        int $lcdScore = 0,
        int $keyboardScore = 0,
        float $ramSize = 0,
        int $batteryScore = 0,
        string $processorName = '',
        int $processorBenchmark = 0
    ): string {
        if (empty($this->apiKey)) {
            return $this->getFallbackConclusion(
                $laptopName, $score, $description,
                $lcdScore, $keyboardScore, $ramSize, $batteryScore, $processorName, $processorBenchmark
            );
        }

        try {
            $prompt = $this->buildPrompt(
                $laptopName, $score, $status, $description,
                $lcdScore, $keyboardScore, $ramSize, $batteryScore, $processorName, $processorBenchmark
            );

            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $this->apiKey, [
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]);

            if ($response->successful()) {
                $candidates = $response->json()['candidates'] ?? [];
                if (!empty($candidates)) {
                    return $candidates[0]['content']['parts'][0]['text'];
                }
            }

            return $this->getFallbackConclusion(
                $laptopName, $score, $description,
                $lcdScore, $keyboardScore, $ramSize, $batteryScore, $processorName, $processorBenchmark
            );
        } catch (\Exception $e) {
            return $this->getFallbackConclusion(
                $laptopName, $score, $description,
                $lcdScore, $keyboardScore, $ramSize, $batteryScore, $processorName, $processorBenchmark
            );
        }
    }

    private function buildPrompt(
        string $laptopName,
        float $score,
        string $status,
        ?string $description,
        int $lcdScore,
        int $keyboardScore,
        float $ramSize,
        int $batteryScore,
        string $processorName,
        int $processorBenchmark
    ): string {
        $descText = $description
            ? "Pengguna juga memberikan catatan tambahan: \"{$description}\"."
            : "";

        return
            "Kamu adalah seorang teknisi laptop senior dengan pengalaman 15 tahun di bengkel servis. " .
            "Tugasmu memberi wawasan teknis dan saran praktis berdasarkan data komponen — bukan merangkum skor atau status.\n\n" .
            "Data laptop:\n" .
            "- Nama: {$laptopName}\n" .
            "- Skor kelayakan: {$score}/100\n" .
            "- Status: {$status}\n" .
            "- LCD: {$lcdScore}/100\n" .
            "- Keyboard: {$keyboardScore}/100\n" .
            "- RAM: {$ramSize} GB\n" .
            "- Baterai: {$batteryScore}%\n" .
            "- Processor: {$processorName} (benchmark {$processorBenchmark})\n" .
            ($descText ? "- Catatan: {$descText}\n" : "") .
            "\nGunakan bahasa Indonesia yang santai dan mudah dipahami seperti ngobrol dengan teman. " .
            "Jangan menyebut skor atau status secara eksplisit — itu sudah ada di layar.\n\n" .
            "3 kalimat saja:\n" .
            "1. Berdasarkan data komponen, jelaskan bagaimana kira-kira pengalaman pakai laptop ini sehari-hari — apa yang terasa masih responsif, apa yang mulai kurang.\n" .
            "2. Jika ada catatan dari pengguna, respons catatan tersebut dengan saran teknis. Jika tidak ada, beri tips perawatan atau hal yang perlu dicek dalam waktu dekat.\n" .
            "3. Saran jujur: laptop ini cocok buat siapa dan kebutuhan apa.\n\n" .
            "Contoh benar:\n" .
            "\"LCD dan keyboard masih oke buat ngetik dan nonton, responsif. Tapi RAM 8 GB mulai terasa sempit kalau kamu suka buka banyak tab Chrome atau aplikasi desain. Saya saranin upgrade ke 16 GB kalau mau lebih nyaman. Buat kerja kantoran dan browsing sih masih worth it.\"\n\n" .
            "Contoh SALAH:\n" .
            "\"Laptop ini mendapat skor 85 sehingga status Layak...\" (jangan, itu cuma merangkum)";
    }

    private function getFallbackConclusion(
        string $laptopName,
        float $score,
        ?string $description,
        int $lcdScore,
        int $keyboardScore,
        float $ramSize,
        int $batteryScore,
        string $processorName,
        int $processorBenchmark
    ): string {
        $parts = [];

        if ($lcdScore >= 80) {
            $parts[] = "LCD masih jernih dan layak dipakai.";
        } elseif ($lcdScore >= 50) {
            $parts[] = "LCD masih oke, tapi mungkin ada sedikit baret atau masalah ringan.";
        } else {
            $parts[] = "LCD perlu diperhatikan, ada kemungkinan muncul masalah seperti dead pixel atau layar redup.";
        }

        if ($ramSize >= 16) {
            $parts[] = "RAM segini nyaman buat multitasking.";
        } elseif ($ramSize >= 8) {
            $parts[] = "RAM 8 GB cukup buat harian, tapi kalau sering buka banyak aplikasi bersamaan mungkin bakal perlu upgrade.";
        } else {
            $parts[] = "RAM termasuk kecil, saran saya upgrade biar laptop tidak lemot.";
        }

        if ($batteryScore >= 80) {
            $parts[] = "Baterai masih sehat dan tahan lama.";
        } elseif ($batteryScore >= 50) {
            $parts[] = "Baterai masih bisa dipakai, tapi mungkin perlu charging lebih sering.";
        } else {
            $parts[] = "Baterai sudah cukup menurun, siap-siap ganti dalam waktu dekat.";
        }

        if ($processorBenchmark >= 15000) {
            $parts[] = "Processor ini masih cukup bertenaga buat kerjaan sehari-hari sampai menengah.";
        } elseif ($processorBenchmark >= 8000) {
            $parts[] = "Processor ini masih oke untuk kebutuhan standar, tapi kurang cocok buat tugas berat.";
        } else {
            $parts[] = "Processor ini sudah agak tua, cocoknya buat tugas ringan aja.";
        }

        $rec = implode(" ", $parts);

        if ($score >= 80) {
            $rec .= " Secara keseluruhan laptop ini masih layak dipakai. ";
        } elseif ($score >= 60) {
            $rec .= " Buat kebutuhan kantoran dan browsing sih masih oke. ";
        } else {
            $rec .= " Kalau tetap mau dibeli, siapkan budget buat servis dan penggantian komponen. ";
        }

        if ($description) {
            $rec .= "Catatan kamu: {$description}.";
        }

        return $rec;
    }
}
