<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class AgentAIService
{
    private ?string $apiKey;
    private TemplateConclusionService $templateService;
    public bool $aiUsed = false;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->templateService = new TemplateConclusionService();
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
        int $processorBenchmark = 0,
        bool $descriptionIgnored = false,
        bool $useAi = false
    ): string {
        // Selalu gunakan template sebagai fallback utama
        $templateConclusion = $this->templateService->getConclusion(
            $laptopName, $score, $status, $description,
            $lcdScore, $keyboardScore, $ramSize, $batteryScore,
            $processorName, $processorBenchmark, $descriptionIgnored
        );

        // Cek toggle global + parameter request
        if (!$useAi || empty($this->apiKey)) {
            return $templateConclusion;
        }

        // Coba panggil Gemini AI
        try {
            $prompt = $this->buildPrompt(
                $laptopName, $score, $status, $description,
                $lcdScore, $keyboardScore, $ramSize, $batteryScore, $processorName, $processorBenchmark
            );

            $response = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $this->apiKey,
                ['contents' => [['parts' => [['text' => $prompt]]]]]
            );

            if ($response->successful()) {
                $json = $response->json();
                $candidates = $json['candidates'] ?? [];
                if (!empty($candidates)) {
                    $parts = $candidates[0]['content']['parts'] ?? [];
                    $text = '';
                    foreach ($parts as $part) {
                        $text .= $part['text'] ?? '';
                    }
                    $text = trim($text);
                    if (!empty($text)) {
                        $this->aiUsed = true;
                        return $this->sanitize($this->appendWarning($text, $descriptionIgnored));
                    }
                }
                \Log::warning('Gemini returned empty response', ['laptop' => $laptopName]);
            } else {
                \Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Exception $e) {
            \Log::error('Gemini API exception: ' . $e->getMessage(), ['laptop' => $laptopName]);
        }

        // Fallback ke template jika AI gagal
        return $templateConclusion;
    }

    private function appendWarning(string $text, bool $descriptionIgnored): string
    {
        if (!$descriptionIgnored) {
            return $text;
        }

        return $text . "\n\nPERINGATAN: Deskripsi yang Anda berikan tidak relevan dengan konteks penilaian laptop dan telah diabaikan. Harap berikan deskripsi terkait kondisi laptop untuk analisis yang lebih akurat.";
    }

    private function sanitize(string $text): string
    {
        $text = preg_replace('/[*_#`~\[\]()>|\\-]{2,}/', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/^[#*>\-|]+\s*/m', '', $text);
        $text = preg_replace('/\b(bagus|oke|jelek|worth it|recommended|sangat direkomendasikan|kosmetik)\b/i', '', $text);
        $text = preg_replace('/\s{2,}/', ' ', $text);
        $text = preg_replace('/\.\s*\./', '.', $text);
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
            ? "Catatan teknisi: \"{$description}\"."
            : "";

        return
            "Anda adalah TEKNISI LAPTOP BERPENGALAMAN. Tulis LAPORAN TEKNIS untuk teknisi menentukan REPARASI/UPGRADE prioritas.\n\n" .
            "Data laptop:\n" .
            "- Model: {$laptopName}\n" .
            "- Skor kelayakan: {$score}/100\n" .
            "- LCD: {$lcdScore}/100\n" .
            "- Keyboard: {$keyboardScore}/100\n" .
            "- RAM: {$ramSize} GB\n" .
            "- Baterai: {$batteryScore}%\n" .
            "- Processor: {$processorName} (benchmark {$processorBenchmark})\n" .
            ($descText ? "- {$descText}\n" : "") .
            "\nATURAN WAJIB:\n" .
            "1. JANGAN sebut skor/status (tidak 'skor 93', 'status Layak').\n" .
            "2. JANGAN gunakan markdown, simbol **, *, _, #, -, >, |, `, bullet points.\n" .
            "3. JANGAN kata subjektif: bagus, oke, jelek, worth it, recommended, layak, tidak layak.\n" .
            "4. STRUKTUR WAJIB 3 BAGIAN (pisah paragraf):\n" .
            "   A. KONDISI FISIK: LCD, Keyboard, Baterai - sebut angka persen, sebut butuh reparasi apa (ganti LCD, ganti keyboard, ganti baterai).\n" .
            "   B. PERFORMA & UPGRADE: RAM & Processor - sebut cocok untuk beban apa, apakah butuh upgrade RAM/SSD.\n" .
            "   C. REPARASI PRIORITAS TEKNISI: daftar prioritas perbaikan dari yang paling urgent ke minor, atau 'tidak ada' jika semua baik.\n" .
            "5. Catatan teknisi (port rusak, engsel, upgrade, dll): KAITKAN ke komponen di bagian A/B.\n" .
            "6. Bahasa: Indonesia formal teknis, singkat, actionable. Hindari kata 'kosmetik' - gunakan 'fisik', 'eksternal', 'body', 'casing', 'perbaikan body/casing'.\n\n" .
            "CONTOH:\n" .
            "---\n" .
            "Input: LCD 95, Keyboard 95, Baterai 95, RAM 16, Proc AMD Ryzen 9 5900HX (22082), Catatan: 'port USB rusak'\n" .
            "Output: \"LCD 95% - kondisi prima, tidak perlu reparasi. Keyboard 95% - normal, tidak perlu reparasi. Baterai 95% - sehat, tidak perlu ganti. RAM 16 GB dan AMD Ryzen 9 5900HX mendukung editing video dan multitasking berat. Catatan teknisi: beberapa port USB tidak berfungsi, perlu perbaikan port I/O untuk fleksibilitas penuh. REPARASI PRIORITAS TEKNISI: 1. perbaikan port I/O.\"\n" .
            "---\n" .
            "Input: LCD 60, Keyboard 40, Baterai 30, RAM 8, Proc Intel Core i5-8250U (7600), Catatan: ''\n" .
            "Output: \"LCD 60% - ada cacat visual, perlu ganti panel LCD. Keyboard 40% - tombol tidak responsif, perlu ganti keyboard. Baterai 30% - aus berat, WAJIB ganti baterai. RAM 8 GB memadai tugas ringan, processor Intel Core i5-8250U terbatas browsing dan office. REPARASI PRIORITAS TEKNISI: 1. ganti baterai (urgent), 2. ganti panel LCD, 3. ganti keyboard.\"\n" .
            "---\n" .
            "Input: LCD 85, Keyboard 80, Baterai 70, RAM 32, Proc Intel Core i7-12700H (28000), Catatan: 'sudah upgrade RAM dan SSD'\n" .
            "Output: \"LCD 85% - baik, tidak perlu reparasi. Keyboard 80% - normal, tidak perlu reparasi. Baterai 70% - masih memadai, monitor kesehatan. RAM 32 GB (upgrade) dan Intel Core i7-12700H siap rendering, compile, virtualisasi. Upgrade RAM/SSD sudah dilakukan, value tambah. REPARASI PRIORITAS TEKNISI: tidak ada, hanya monitoring baterai.\"\n" .
            "---\n" .
            "Sekarang tulis laporan teknis untuk data di atas:";
    }
}
