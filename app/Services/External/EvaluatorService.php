<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Http;
use App\Models\FuzzyRule;

class EvaluatorService
{
    private string $baseUrl;

    public function __construct()
    {
        // Alamat URL Fuzzy Service (bisa diatur di .env)
        $this->baseUrl = rtrim(config('services.evaluator.url', 'http://evaluator'), '/');
    }

    public function evaluate(array $input)
    {
        // Ambil aturan terbaru dari database Core Service
        $rules = $this->formatRulesForEvaluatorService();

        $payload = [
            'input' => $input,
            'rules' => [
                'fuzzifikasi' => $rules,
                'defuzzifikasi' => [
                    'centroid' => ['tidak_layak' => 30, 'kurang_layak' => 60, 'layak' => 90],
                    'batas_status' => ['tidak_bagus' => 40, 'normal' => 65]
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
            $bodyPreview = $response->body();
            throw new \Exception("Evaluator Service Error: Invalid JSON response from {$url}. Body: " . ($bodyPreview ?: "<empty>"));
        }

        return $json['data'];
    }

    private function formatRulesForEvaluatorService(): array
    {
        // Mengubah data tabel fuzzy_rules menjadi format JSON yang dimengerti Fuzzy Service
        $allRules = FuzzyRule::all();
        $formatted = [];

        foreach ($allRules as $rule) {
            $formatted[$rule->variable][$rule->category] = $rule->parameters;
        }

        return $formatted;
    }
}