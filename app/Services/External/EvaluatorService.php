<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Http;
use App\Models\FuzzyConfig;
use App\Models\FuzzyRule;
use App\Models\FuzzyThreshold;

class EvaluatorService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.evaluator.url', 'http://evaluator'), '/');
    }

    public function evaluate(array $input)
    {
        // 1. Ambil Konfigurasi Fungsi Keanggotaan (Fuzzifikasi)
        $configs = $this->formatFuzzyConfigs();

        // 2. Ambil Matriks Aturan (Inference Matrix)
        $matrix = $this->formatInferenceMatrix();

        // 3. Ambil Konfigurasi Defuzzifikasi dari Database
        $defuzzifikasi = $this->formatDefuzzifikasiConfigs();

        // 4. Ambil Threshold Batas Kelayakan dari Database
        $thresholds = $this->formatThresholds();

        $payload = [
            'input' => $input,
            'rules' => [
                'fuzzifikasi' => $configs,
                'matrix_aturan' => $matrix,
                'defuzzifikasi' => $defuzzifikasi,
                'thresholds' => $thresholds,
            ]
        ];

        // HTTP POST ke Fuzzy Service
        $url = "{$this->baseUrl}/api/evaluator";
        $response = Http::acceptJson()->post($url, $payload);

        if ($response->failed()) {
            $errorBody = $response->body();
            throw new \Exception("Evaluator Service Error ({$response->status()}): " . ($errorBody ?: "Tidak merespon."));
        }

        $json = $response->json();
        if (!is_array($json) || !array_key_exists('data', $json)) {
            throw new \Exception("Evaluator Service Error: Invalid JSON response from {$url}.");
        }

        return $json['data'];
    }

    private function formatFuzzyConfigs(): array
    {
        $allConfigs = FuzzyConfig::where('variable', '!=', 'Kelayakan')->get();
        $formatted = [];

        foreach ($allConfigs as $config) {
            $formatted[$config->variable][$config->category] = $config->parameters;
        }

        return $formatted;
    }

    private function formatInferenceMatrix(): array
    {
        return FuzzyRule::all(['lcd', 'keyboard', 'ram', 'baterai', 'processor', 'output'])->toArray();
    }

    private function formatDefuzzifikasiConfigs(): array
    {
        $configs = FuzzyConfig::where('variable', 'Kelayakan')->get();
        $formatted = [];

        foreach ($configs as $config) {
            $formatted[$config->category] = $config->parameters;
        }

        return $formatted;
    }

    private function formatThresholds(): array
    {
        return FuzzyThreshold::pluck('value', 'name')->toArray();
    }
}