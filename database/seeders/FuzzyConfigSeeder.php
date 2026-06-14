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
            ['variable' => 'LCD', 'category' => 'buruk', 'curve_type' => 'turun', 'parameters' => [55, 65]],
            ['variable' => 'LCD', 'category' => 'sedang', 'curve_type' => 'trapesium', 'parameters' => [55, 65, 75, 85]],
            ['variable' => 'LCD', 'category' => 'baik', 'curve_type' => 'naik', 'parameters' => [75, 85]],

            // Kesehatan Baterai (Rendah, Sedang, Tinggi)
            ['variable' => 'KesehatanBaterai', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [60, 70]],
            ['variable' => 'KesehatanBaterai', 'category' => 'sedang', 'curve_type' => 'segitiga', 'parameters' => [60, 70, 85]],
            ['variable' => 'KesehatanBaterai', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [70, 85]],

            // Processor (Rendah, Sedang, Tinggi)
            ['variable' => 'Processor', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [8000, 10000]],
            ['variable' => 'Processor', 'category' => 'sedang', 'curve_type' => 'trapesium', 'parameters' => [8000, 10000, 18000, 20000]],
            ['variable' => 'Processor', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [18000, 20000]],

            // Kondisi Keyboard (Buruk, Sedang, Baik)
            ['variable' => 'KondisiKeyboard', 'category' => 'buruk', 'curve_type' => 'turun', 'parameters' => [55, 65]],
            ['variable' => 'KondisiKeyboard', 'category' => 'sedang', 'curve_type' => 'trapesium', 'parameters' => [55, 65, 75, 85]],
            ['variable' => 'KondisiKeyboard', 'category' => 'baik', 'curve_type' => 'naik', 'parameters' => [75, 85]],

            // RAM (Rendah, Sedang, Tinggi)
            ['variable' => 'RAM', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [6, 8]],
            ['variable' => 'RAM', 'category' => 'sedang', 'curve_type' => 'segitiga', 'parameters' => [6, 8, 12]],
            ['variable' => 'RAM', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [8, 12]],
        ];

        foreach ($configs as $config) {
            FuzzyConfig::create($config);
        }
    }
}