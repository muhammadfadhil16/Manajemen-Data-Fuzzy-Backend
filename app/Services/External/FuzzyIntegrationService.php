<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Http;
use App\Models\FuzzyRule;

class FuzzyIntegrationService
{
    private string $baseUrl;

    public function __construct()
    {
        // Alamat URL Fuzzy Service (bisa diatur di .env)
        $this->baseUrl = config('services.fuzzy.url', 'http://fuzzy-service.test');
    }

    public function getAssessment(array $input)
    {
        // Ambil aturan terbaru dari database Core Service
        $rules = $this->formatRulesForFuzzyService();

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
        $response = Http::post("{$this->baseUrl}/api/perhitungan", $payload);

        if ($response->failed()) {
            throw new \Exception("Fuzzy Service tidak merespon.");
        }

        return $response->json()['data'];
    }

    private function formatRulesForFuzzyService(): array
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