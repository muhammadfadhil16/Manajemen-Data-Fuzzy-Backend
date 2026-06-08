<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ProcessorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan tabel terlebih dahulu sebelum di-seed (opsional)
        Schema::disableForeignKeyConstraints();
        DB::table('processors')->truncate();
        Schema::enableForeignKeyConstraints();

        $now = Carbon::now();

        $data = [
            // --- KATEGORI: RENDAH (Skor < 5.000) ---
            ['name' => 'Intel Celeron N4000', 'benchmark_score' => 1420, 'category' => 'Rendah'],
            ['name' => 'Intel Celeron N4020', 'benchmark_score' => 1550, 'category' => 'Rendah'],
            ['name' => 'Intel Celeron N4500', 'benchmark_score' => 1980, 'category' => 'Rendah'],
            ['name' => 'Intel Celeron N5100', 'benchmark_score' => 3250, 'category' => 'Rendah'],
            ['name' => 'Intel Pentium Silver N5000', 'benchmark_score' => 2550, 'category' => 'Rendah'],
            ['name' => 'Intel Pentium Silver N5030', 'benchmark_score' => 2680, 'category' => 'Rendah'],
            ['name' => 'Intel Pentium Silver N6000', 'benchmark_score' => 3100, 'category' => 'Rendah'],
            ['name' => 'Intel Core i3-6006U', 'benchmark_score' => 2250, 'category' => 'Rendah'],
            ['name' => 'Intel Core i3-7020U', 'benchmark_score' => 2560, 'category' => 'Rendah'],
            ['name' => 'Intel Core i3-8130U', 'benchmark_score' => 3580, 'category' => 'Rendah'],
            ['name' => 'Intel Core i3-1005G1', 'benchmark_score' => 4750, 'category' => 'Rendah'],
            ['name' => 'AMD A9-9425', 'benchmark_score' => 1500, 'category' => 'Rendah'],
            ['name' => 'AMD Athlon Silver 3050U', 'benchmark_score' => 2980, 'category' => 'Rendah'],
            ['name' => 'AMD Ryzen 3 3200U', 'benchmark_score' => 3850, 'category' => 'Rendah'],
            ['name' => 'AMD Ryzen 3 3250U', 'benchmark_score' => 3880, 'category' => 'Rendah'],

            // --- KATEGORI: SEDANG (Skor 5.000 - 14.999) ---
            ['name' => 'Intel Core i5-8250U', 'benchmark_score' => 5900, 'category' => 'Sedang'],
            ['name' => 'Intel Core i5-8265U', 'benchmark_score' => 6100, 'category' => 'Sedang'],
            ['name' => 'Intel Core i7-8550U', 'benchmark_score' => 5950, 'category' => 'Sedang'],
            ['name' => 'Intel Core i7-8565U', 'benchmark_score' => 6250, 'category' => 'Sedang'],
            ['name' => 'Intel Core i5-10210U', 'benchmark_score' => 6250, 'category' => 'Sedang'],
            ['name' => 'Intel Core i5-1035G1', 'benchmark_score' => 7400, 'category' => 'Sedang'],
            ['name' => 'Intel Core i7-1065G7', 'benchmark_score' => 8450, 'category' => 'Sedang'],
            ['name' => 'Intel Core i3-1115G4', 'benchmark_score' => 6100, 'category' => 'Sedang'],
            ['name' => 'Intel Core i5-1135G7', 'benchmark_score' => 9850, 'category' => 'Sedang'],
            ['name' => 'Intel Core i7-1165G7', 'benchmark_score' => 10400, 'category' => 'Sedang'],
            ['name' => 'AMD Ryzen 3 4300U', 'benchmark_score' => 7500, 'category' => 'Sedang'],
            ['name' => 'AMD Ryzen 5 3500U', 'benchmark_score' => 7100, 'category' => 'Sedang'],
            ['name' => 'AMD Ryzen 5 4500U', 'benchmark_score' => 11050, 'category' => 'Sedang'],
            ['name' => 'AMD Ryzen 7 4700U', 'benchmark_score' => 13400, 'category' => 'Sedang'],
            ['name' => 'AMD Ryzen 5 5500U', 'benchmark_score' => 13100, 'category' => 'Sedang'],
            ['name' => 'AMD Ryzen 5 7520U', 'benchmark_score' => 9800, 'category' => 'Sedang'],
            ['name' => 'AMD Ryzen 3 7320U', 'benchmark_score' => 7100, 'category' => 'Sedang'],

            // --- KATEGORI: TINGGI (Skor >= 15.000) ---
            ['name' => 'Intel Core i5-1235U', 'benchmark_score' => 13500, 'category' => 'Tinggi'],
            ['name' => 'Intel Core i7-1255U', 'benchmark_score' => 13800, 'category' => 'Tinggi'],
            ['name' => 'Intel Core i5-12500H', 'benchmark_score' => 21200, 'category' => 'Tinggi'],
            ['name' => 'Intel Core i7-12700H', 'benchmark_score' => 26400, 'category' => 'Tinggi'],
            ['name' => 'Intel Core i5-1335U', 'benchmark_score' => 14800, 'category' => 'Tinggi'],
            ['name' => 'Intel Core i7-1355U', 'benchmark_score' => 15200, 'category' => 'Tinggi'],
            ['name' => 'Intel Core i5-13500H', 'benchmark_score' => 23100, 'category' => 'Tinggi'],
            ['name' => 'Intel Core i7-13700H', 'benchmark_score' => 27500, 'category' => 'Tinggi'],
            ['name' => 'Intel Core Ultra 5 125H', 'benchmark_score' => 21500, 'category' => 'Tinggi'],
            ['name' => 'Intel Core Ultra 7 155H', 'benchmark_score' => 25200, 'category' => 'Tinggi'],
            ['name' => 'AMD Ryzen 7 5700U', 'benchmark_score' => 15800, 'category' => 'Tinggi'],
            ['name' => 'AMD Ryzen 5 5600H', 'benchmark_score' => 17100, 'category' => 'Tinggi'],
            ['name' => 'AMD Ryzen 7 5800H', 'benchmark_score' => 21000, 'category' => 'Tinggi'],
            ['name' => 'AMD Ryzen 7 7730U', 'benchmark_score' => 18900, 'category' => 'Tinggi'],
            ['name' => 'Apple M1 8-Core', 'benchmark_score' => 14100, 'category' => 'Tinggi'],
            ['name' => 'Apple M2 8-Core', 'benchmark_score' => 15400, 'category' => 'Tinggi'],
            ['name' => 'Apple M3 8-Core', 'benchmark_score' => 19200, 'category' => 'Tinggi'],
            ['name' => 'Apple M1 Pro 10-Core', 'benchmark_score' => 23500, 'category' => 'Tinggi'],
        ];

        // Tambahkan timestamp created_at dan updated_at pada tiap data
        $insertData = array_map(function ($item) use ($now) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
            return $item;
        }, $data);

        // Masukkan data ke database menggunakan chunking (agar lebih aman & performan)
        foreach (array_chunk($insertData, 20) as $chunk) {
            DB::table('processors')->insert($chunk);
        }
    }
}
