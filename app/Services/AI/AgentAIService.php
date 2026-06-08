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
            return 'tidak ada catatan tambahan';
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
                    $text = $candidates[0]['content']['parts'][0]['text'];
                    return $this->sanitize($text);
                }
            }

            return 'tidak ada catatan tambahan';
        } catch (\Exception $e) {
            return 'tidak ada catatan tambahan';
        }
    }

    private function sanitize(string $text): string
    {
        $text = preg_replace('/[*_#`~\[\]()>|\\-]{2,}/', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/^[#*>\-|]+\s*/m', '', $text);
        return trim($text);
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
            "Analisis kondisi laptop berikut secara objektif dalam 2-3 kalimat. " .
            "Gunakan bahasa Indonesia faktual, seperti laporan teknis. " .
            "Jangan gunakan opini subjektif atau kata 'saya'.\n\n" .
            "Data laptop:\n" .
            "- Nama: {$laptopName}\n" .
            "- Skor kelayakan: {$score}/100\n" .
            "- Status: {$status}\n" .
            "- LCD: {$lcdScore}/100\n" .
            "- Keyboard: {$keyboardScore}/100\n" .
            "- RAM: {$ramSize} GB\n" .
            "- Baterai: {$batteryScore}%\n" .
            "- Processor: {$processorName} (benchmark {$processorBenchmark})\n" .
            ($descText ? "- Catatan pengguna: {$description}\n" : "") .
            "\nAturan:\n" .
            "- Jangan menyebut skor atau status secara eksplisit.\n" .
            "- JANGAN gunakan simbol seperti **, *, _, #, -, >, |, ` atau format markdown.\n" .
            "- Gunakan sudut pandang ketiga (misal: 'perangkat ini memiliki').\n" .
            "- Hindari kata sifat subjektif seperti 'bagus', 'oke', 'jelek', 'worth it'.\n\n" .
            "Struktur:\n" .
            "1. Kondisi komponen utama berdasarkan data.\n" .
            ($descText ? "2. Jika catatan pengguna relevan dengan laptop, kaitkan dengan data. Jika tidak relevan, abaikan.\n" : "2. Gambaran kesesuaian spesifikasi untuk penggunaan umum.\n") .
            "3. Kesimpulan objektif tentang segmen pengguna yang sesuai.\n\n" .
            "Contoh hasil:\n" .
            "\"LCD dan keyboard dalam kondisi baik. RAM 8 GB mencukupi untuk aplikasi perkantoran dan browsing, namun dapat mengalami keterbatasan saat membuka banyak aplikasi secara bersamaan. Perangkat ini sesuai untuk pengguna dengan kebutuhan komputasi ringan hingga menengah.\"";
    }


}
