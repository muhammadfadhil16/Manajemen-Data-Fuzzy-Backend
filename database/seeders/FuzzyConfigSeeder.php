<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FuzzyConfig;

class FuzzyConfigSeeder extends Seeder
{
    public function run(): void
    {
        FuzzyConfig::truncate();

        $configs = [
            // LCD (Buruk, Sedang, Baik) - match fuzzy3.py trapezium
            ['variable' => 'LCD', 'category' => 'buruk', 'curve_type' => 'trapesium', 'parameters' => [0, 0, 55, 65]],
            ['variable' => 'LCD', 'category' => 'sedang', 'curve_type' => 'trapesium', 'parameters' => [55, 65, 75, 85]],
            ['variable' => 'LCD', 'category' => 'baik', 'curve_type' => 'trapesium', 'parameters' => [75, 85, 100, 100]],

            // Kesehatan Baterai (Rendah, Sedang, Tinggi) - match fuzzy3.py trapezium
            ['variable' => 'KesehatanBaterai', 'category' => 'rendah', 'curve_type' => 'trapesium', 'parameters' => [0, 0, 60, 70]],
            ['variable' => 'KesehatanBaterai', 'category' => 'sedang', 'curve_type' => 'segitiga', 'parameters' => [60, 70, 85]],
            ['variable' => 'KesehatanBaterai', 'category' => 'tinggi', 'curve_type' => 'trapesium', 'parameters' => [70, 85, 100, 100]],

            // Processor (Rendah, Sedang, Tinggi) - match fuzzy3.py trapezium
            ['variable' => 'Processor', 'category' => 'rendah', 'curve_type' => 'trapesium', 'parameters' => [0, 0, 8000, 10000]],
            ['variable' => 'Processor', 'category' => 'sedang', 'curve_type' => 'trapesium', 'parameters' => [8000, 10000, 18000, 20000]],
            ['variable' => 'Processor', 'category' => 'tinggi', 'curve_type' => 'trapesium', 'parameters' => [18000, 20000, 64946, 64946]],

            // Kondisi Keyboard (Buruk, Sedang, Baik) - match fuzzy3.py trapezium
            ['variable' => 'KondisiKeyboard', 'category' => 'buruk', 'curve_type' => 'trapesium', 'parameters' => [0, 0, 55, 65]],
            ['variable' => 'KondisiKeyboard', 'category' => 'sedang', 'curve_type' => 'trapesium', 'parameters' => [55, 65, 75, 85]],
            ['variable' => 'KondisiKeyboard', 'category' => 'baik', 'curve_type' => 'trapesium', 'parameters' => [75, 85, 100, 100]],

            // RAM (Rendah, Sedang, Tinggi) - match fuzzy3.py trapezium
            ['variable' => 'RAM', 'category' => 'rendah', 'curve_type' => 'trapesium', 'parameters' => [4, 4, 6, 8]],
            ['variable' => 'RAM', 'category' => 'sedang', 'curve_type' => 'segitiga', 'parameters' => [6, 8, 12]],
            ['variable' => 'RAM', 'category' => 'tinggi', 'curve_type' => 'trapesium', 'parameters' => [8, 12, 64, 64]],

            // Defuzzifikasi Output (Kelayakan) - Kurva Fungsi Keanggotaan Output
            ['variable' => 'Kelayakan', 'category' => 'tidak_layak', 'curve_type' => 'trapesium', 'parameters' => [0, 0, 55, 65]],
            ['variable' => 'Kelayakan', 'category' => 'cukup_layak', 'curve_type' => 'trapesium', 'parameters' => [55, 65, 85, 90]],
            ['variable' => 'Kelayakan', 'category' => 'layak', 'curve_type' => 'trapesium', 'parameters' => [85, 90, 100, 100]],
        ];

        foreach ($configs as $config) {
            FuzzyConfig::create($config);
        }
    }
}