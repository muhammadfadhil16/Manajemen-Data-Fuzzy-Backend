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
            // LCD (Buruk, Sedang, Baik)
            ['variable' => 'LCD', 'category' => 'buruk', 'curve_type' => 'turun', 'parameters' => [40, 60]],
            ['variable' => 'LCD', 'category' => 'sedang', 'curve_type' => 'trapesium', 'parameters' => [40, 60, 70, 90]],
            ['variable' => 'LCD', 'category' => 'baik', 'curve_type' => 'naik', 'parameters' => [70, 90]],

            // Kesehatan Baterai (Rendah, Sedang, Tinggi)
            ['variable' => 'KesehatanBaterai', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [30, 50]],
            ['variable' => 'KesehatanBaterai', 'category' => 'sedang', 'curve_type' => 'segitiga', 'parameters' => [30, 60, 85]],
            ['variable' => 'KesehatanBaterai', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [70, 90]],

            // Processor (Rendah, Sedang, Tinggi)
            ['variable' => 'Processor', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [500, 5000]],
            ['variable' => 'Processor', 'category' => 'sedang', 'curve_type' => 'segitiga', 'parameters' => [500, 10000, 15000]],
            ['variable' => 'Processor', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [10000, 15000]],

            // Kondisi Keyboard (Buruk, Sedang, Baik)
            ['variable' => 'KondisiKeyboard', 'category' => 'buruk', 'curve_type' => 'turun', 'parameters' => [40, 70]],
            ['variable' => 'KondisiKeyboard', 'category' => 'sedang', 'curve_type' => 'trapesium', 'parameters' => [40, 60, 70, 90]],
            ['variable' => 'KondisiKeyboard', 'category' => 'baik', 'curve_type' => 'naik', 'parameters' => [70, 90]],

            // RAM (Rendah, Sedang, Tinggi) - BARU
            ['variable' => 'RAM', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [2, 4]],
            ['variable' => 'RAM', 'category' => 'sedang', 'curve_type' => 'segitiga', 'parameters' => [2, 8, 16]],
            ['variable' => 'RAM', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [8, 16]],
        ];

        foreach ($configs as $config) {
            FuzzyConfig::create($config);
        }
    }
}