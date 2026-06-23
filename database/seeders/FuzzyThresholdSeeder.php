<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FuzzyThreshold;

class FuzzyThresholdSeeder extends Seeder
{
    public function run(): void
    {
        FuzzyThreshold::truncate();

        FuzzyThreshold::create([
            'name' => 'tidak_layak_batas',
            'value' => 65.00,
        ]);

        FuzzyThreshold::create([
            'name' => 'layak_batas',
            'value' => 85.00,
        ]);
    }
}
