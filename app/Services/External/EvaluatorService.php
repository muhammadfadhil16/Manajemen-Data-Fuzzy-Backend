<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Http;
use App\Models\FuzzyConfig;
use App\Models\FuzzyRule;

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

        $payload = [
            'input' => $input,
            'rules' => [
                'fuzzifikasi' => $configs,
                'matrix_aturan' => $matrix,
                'defuzzifikasi' => [
                    'tidak_layak' => [40, 60],
                    'cukup_layak' => [40, 60, 70, 90],
                    'layak' => [70, 90]
                ]
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
        $allConfigs = FuzzyConfig::all();
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
}