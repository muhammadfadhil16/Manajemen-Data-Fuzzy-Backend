<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FuzzyRule;

class FuzzyRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Kosongkan tabel terlebih dahulu agar data tidak ganda (duplicate) 
        // saat kamu menjalankan php artisan db:seed berulang kali
        FuzzyRule::truncate();

        $rules = [
            // LCD
            // Kurva Turun/Naik HANYA butuh 2 parameter [a, b]
            // Kurva Segitiga butuh 3 parameter [a, b, c]
            ['variable' => 'LCD', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [40, 60]],
            ['variable' => 'LCD', 'category' => 'normal', 'curve_type' => 'segitiga', 'parameters' => [40, 60, 80]],
            ['variable' => 'LCD', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [60, 80]],

            // Kesehatan Baterai
            ['variable' => 'KesehatanBaterai', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [30, 50]],
            ['variable' => 'KesehatanBaterai', 'category' => 'normal', 'curve_type' => 'segitiga', 'parameters' => [30, 60, 85]],
            ['variable' => 'KesehatanBaterai', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [70, 90]],

            // Processor (Skala Benchmark PassMark)
            ['variable' => 'Processor', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [500, 10000]],
            ['variable' => 'Processor', 'category' => 'normal', 'curve_type' => 'segitiga', 'parameters' => [500, 10000, 15000]],
            ['variable' => 'Processor', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [10000, 15000]],

            // Kondisi Keyboard
            ['variable' => 'KondisiKeyboard', 'category' => 'rendah', 'curve_type' => 'turun', 'parameters' => [40, 70]],
            ['variable' => 'KondisiKeyboard', 'category' => 'normal', 'curve_type' => 'segitiga', 'parameters' => [40, 70, 90]],
            ['variable' => 'KondisiKeyboard', 'category' => 'tinggi', 'curve_type' => 'naik', 'parameters' => [70, 90]],
        ];

        foreach ($rules as $rule) {
            FuzzyRule::create($rule);
        }
    }
}