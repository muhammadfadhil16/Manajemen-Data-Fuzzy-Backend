<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FuzzyRule;

class FuzzyRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            // LCD
            ['variable' => 'LCD', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [0, 40, 60]],
            ['variable' => 'LCD', 'category' => 'normal', 'curve_type' => 'segitiga', 'parameters' => [40, 60, 80]],
            ['variable' => 'LCD', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [60, 80, 100]],

            // Kesehatan Baterai
            ['variable' => 'KesehatanBaterai', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [0, 30, 50]],
            ['variable' => 'KesehatanBaterai', 'category' => 'normal', 'curve_type' => 'segitiga', 'parameters' => [30, 60, 85]],
            ['variable' => 'KesehatanBaterai', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [70, 90, 100]],

            // RAM (Skor atau GB?) - Berdasarkan controller ini dikali 1? 
            // Biasanya di fuzzy RAM 2-4 rendah, 8 normal, 16+ tinggi
            ['variable' => 'RAM', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [0, 4, 8]],
            ['variable' => 'RAM', 'category' => 'normal', 'curve_type' => 'segitiga', 'parameters' => [4, 8, 16]],
            ['variable' => 'RAM', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [8, 16, 32]],

            // Kondisi Keyboard
            ['variable' => 'KondisiKeyboard', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [0, 40, 60]],
            ['variable' => 'KondisiKeyboard', 'category' => 'normal', 'curve_type' => 'segitiga', 'parameters' => [40, 70, 90]],
            ['variable' => 'KondisiKeyboard', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [70, 90, 100]],
        ];

        foreach ($rules as $rule) {
            FuzzyRule::create($rule);
        }
    }
}
