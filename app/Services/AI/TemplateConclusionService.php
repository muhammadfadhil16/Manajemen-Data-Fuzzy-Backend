<?php

namespace App\Services\AI;

class TemplateConclusionService
{
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
        bool $descriptionIgnored = false
    ): string {
        $sections = [];

        $sections[] = $this->buildComponentSection($lcdScore, $keyboardScore, $batteryScore);
        $sections[] = $this->buildPerformanceSection($ramSize, $processorName, $processorBenchmark);
        $sections[] = $this->buildSuitabilitySection($score, $ramSize, $processorBenchmark);

        $text = implode(' ', array_filter($sections));

        return $this->appendWarning($text, $descriptionIgnored);
    }

    private function buildComponentSection(int $lcdScore, int $keyboardScore, int $batteryScore): string
    {
        $parts = [];

        // LCD
        if ($lcdScore >= 80) {
            $parts[] = 'LCD dalam kondisi baik';
        } elseif ($lcdScore >= 50) {
            $parts[] = 'LCD dalam kondisi cukup';
        } else {
            $parts[] = 'LCD perlu perhatian karena kondisi yang kurang optimal';
        }

        // Keyboard
        if ($keyboardScore >= 80) {
            $parts[] = 'keyboard berfungsi normal';
        } elseif ($keyboardScore >= 50) {
            $parts[] = 'keyboard dalam kondisi cukup';
        } else {
            $parts[] = 'keyboard perlu perhatian karena kondisi yang kurang optimal';
        }

        // Baterai
        if ($batteryScore >= 80) {
            $parts[] = 'baterai memiliki masa pakai yang baik';
        } elseif ($batteryScore >= 50) {
            $parts[] = 'baterai dalam kondisi cukup namun perlu perawatan';
        } else {
            $parts[] = 'baterai sudah aus dan sebaiknya segera diganti';
        }

        return $this->joinIndonesian($parts) . '.';
    }

    private function buildPerformanceSection(float $ramSize, string $processorName, int $processorBenchmark): string
    {
        $parts = [];

        // RAM
        if ($ramSize >= 16) {
            $parts[] = "RAM {$ramSize} GB memadai untuk multitasking berat dan aplikasi berat";
        } elseif ($ramSize >= 8) {
            $parts[] = "RAM {$ramSize} GB mencukupi untuk aplikasi perkantoran dan penelusuran web";
        } elseif ($ramSize >= 4) {
            $parts[] = "RAM {$ramSize} GB hanya memadai untuk komputasi dasar";
        } else {
            $parts[] = "RAM {$ramSize} GB sangat terbatas dan akan mengalami keterbatasan saat menjalankan beberapa aplikasi";
        }

        // Processor
        if ($processorBenchmark >= 18000) {
            $parts[] = "prosesor {$processorName} memberikan performa tinggi untuk tugas berat";
        } elseif ($processorBenchmark >= 8000) {
            $parts[] = "prosesor {$processorName} memberikan performa yang memadai untuk penggunaan sehari-hari";
        } else {
            $parts[] = "prosesor {$processorName} cocok untuk tugas komputasi ringan";
        }

        return $this->joinIndonesian($parts) . '.';
    }

    private function buildSuitabilitySection(float $score, float $ramSize, int $processorBenchmark): string
    {
        if ($score >= 80) {
            return 'Perangkat ini sangat direkomendasikan dan masih layak digunakan untuk kebutuhan komputasi sehari-hari.';
        }

        if ($score >= 60) {
            if ($ramSize >= 8 && $processorBenchmark >= 8000) {
                return 'Perangkat ini sesuai untuk pengguna dengan kebutuhan komputasi ringan hingga menengah.';
            }
            return 'Perangkat ini sesuai untuk pengguna dengan kebutuhan komputasi ringan.';
        }

        if ($score >= 40) {
            return 'Perangkat ini masih dapat digunakan untuk tugas dasar namun dengan keterbatasan performa.';
        }

        return 'Perangkat ini sudah mencapai batas usia pakai dan disarankan untuk penggantian.';
    }

    private function joinIndonesian(array $parts): string
    {
        $count = count($parts);
        if ($count === 0) return '';
        if ($count === 1) return $parts[0];
        if ($count === 2) return "{$parts[0]} dan {$parts[1]}";

        $last = array_pop($parts);
        return implode(', ', $parts) . ', dan ' . $last;
    }

    private function appendWarning(string $text, bool $descriptionIgnored): string
    {
        if (!$descriptionIgnored) {
            return $text;
        }

        return $text . "\n\nPERINGATAN: Deskripsi yang Anda berikan tidak relevan dengan konteks penilaian laptop dan telah diabaikan. Harap berikan deskripsi terkait kondisi laptop untuk analisis yang lebih akurat.";
    }
}
