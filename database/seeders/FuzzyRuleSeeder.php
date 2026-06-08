<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FuzzyRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan tabel terlebih dahulu mencegah duplikasi
        DB::table('fuzzy_rules')->truncate();

        // 1. Definisikan urutan domain keanggotaan sesuai pola tabel skripsi
        $lcdOptions       = ['buruk', 'sedang', 'baik'];
        $keyboardOptions  = ['buruk', 'sedang', 'baik'];
        $ramOptions       = ['rendah', 'sedang', 'tinggi'];
        $bateraiOptions   = ['rendah', 'sedang', 'tinggi'];
        $benchmarkOptions = ['rendah', 'sedang', 'tinggi'];

        $rulesPayload = [];
        $counter = 1;

        // 2. Lakukan nested loop untuk menghasilkan total kombinasi: 3 x 3 x 3 x 3 x 3 = 243 aturan
        foreach ($lcdOptions as $lcd) {
            foreach ($keyboardOptions as $keyboard) {
                foreach ($ramOptions as $ram) {
                    foreach ($bateraiOptions as $baterai) {
                        foreach ($benchmarkOptions as $benchmark) {
                            
                            // 3. Tentukan output keputusan berdasarkan aturan skripsi Pratiwi
                            $output = $this->determineOutput($lcd, $keyboard, $ram, $baterai, $benchmark);

                            $rulesPayload[] = [
                                'lcd'        => $lcd,
                                'keyboard'   => $keyboard,
                                'ram'        => $ram,
                                'baterai'    => $baterai,
                                'processor'  => $benchmark, // Disamakan dengan nama kolom skema DB Anda
                                'output'     => $output,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            // Menggunakan chunk insert setiap 50 data agar meringankan beban query MySQL
                            if (count($rulesPayload) >= 50) {
                                DB::table('fuzzy_rules')->insert($rulesPayload);
                                $rulesPayload = [];
                            }

                            $counter++;
                        }
                    }
                }
            }
        }

        // Jalankan sisa payload yang belum ter-insert
        if (count($rulesPayload) > 0) {
            DB::table('fuzzy_rules')->insert($rulesPayload);
        }

        $this->command->info("Berhasil men-seed " . ($counter - 1) . " basis aturan fuzzy ke dalam database.");
    }

    /**
     * Helper Logika Penentu Keputusan Sesuai Matriks Skripsi
     */
    private function determineOutput(string $lcd, string $keyboard, string $ram, string $baterai, string $benchmark): string
    {
        // --- KATEGORI 1: TIDAK LAYAK ---
        // R1 - R9: LCD Buruk, Keyboard Buruk, RAM Rendah
        if ($lcd === 'buruk' && $keyboard === 'buruk' && $ram === 'rendah') {
            return 'tidak_layak';
        }

        // R10 - R12: LCD Buruk, Keyboard Buruk, RAM Sedang, Baterai Rendah
        if ($lcd === 'buruk' && $keyboard === 'buruk' && $ram === 'sedang' && $baterai === 'rendah') {
            return 'tidak_layak';
        }

        // R19 - R21: LCD Buruk, Keyboard Buruk, RAM Tinggi, Baterai Rendah
        if ($lcd === 'buruk' && $keyboard === 'buruk' && $ram === 'tinggi' && $baterai === 'rendah') {
            return 'tidak_layak';
        }

        // R28 - R30: LCD Buruk, Keyboard Sedang, RAM Rendah, Baterai Rendah
        if ($lcd === 'buruk' && $keyboard === 'sedang' && $ram === 'rendah' && $baterai === 'rendah') {
            return 'tidak_layak';
        }

        // R55 - R57: LCD Buruk, Keyboard Baik, RAM Rendah, Baterai Rendah
        if ($lcd === 'buruk' && $keyboard === 'baik' && $ram === 'rendah' && $baterai === 'rendah') {
            return 'tidak_layak';
        }

        // R82 - R84: LCD Sedang, Keyboard Buruk, RAM Rendah, Baterai Rendah
        if ($lcd === 'sedang' && $keyboard === 'buruk' && $ram === 'rendah' && $baterai === 'rendah') {
            return 'tidak_layak';
        }

        // R163 - R165: LCD Baik, Keyboard Buruk, RAM Rendah, Baterai Rendah
        if ($lcd === 'baik' && $keyboard === 'buruk' && $ram === 'rendah' && $baterai === 'rendah') {
            return 'tidak_layak';
        }


        // --- KATEGORI 2: LAYAK ---
        // R151 - R153: LCD Sedang, Keyboard Baik, RAM Sedang, Baterai Tinggi
        if ($lcd === 'sedang' && $keyboard === 'baik' && $ram === 'sedang' && $baterai === 'tinggi') {
            return 'layak';
        }

        // R160 - R162: LCD Sedang, Keyboard Baik, RAM Tinggi, Baterai Tinggi
        if ($lcd === 'sedang' && $keyboard === 'baik' && $ram === 'tinggi' && $baterai === 'tinggi') {
            return 'layak';
        }

        // R205 - R207: LCD Baik, Keyboard Sedang, RAM Sedang, Baterai Tinggi
        if ($lcd === 'baik' && $keyboard === 'sedang' && $ram === 'sedang' && $baterai === 'tinggi') {
            return 'layak';
        }

        // R214 - R216: LCD Baik, Keyboard Sedang, RAM Tinggi, Baterai Tinggi
        if ($lcd === 'baik' && $keyboard === 'sedang' && $ram === 'tinggi' && $baterai === 'tinggi') {
            return 'layak';
        }

        // R229 - R231: LCD Baik, Keyboard Baik, RAM Sedang, Baterai Sedang
        if ($lcd === 'baik' && $keyboard === 'baik' && $ram === 'sedang' && $baterai === 'sedang') {
            return 'layak';
        }

        // R232 - R234: LCD Baik, Keyboard Baik, RAM Sedang, Baterai Tinggi
        if ($lcd === 'baik' && $keyboard === 'baik' && $ram === 'sedang' && $baterai === 'tinggi') {
            return 'layak';
        }

        // R238 - R240: LCD Baik, Keyboard Baik, RAM Tinggi, Baterai Sedang
        if ($lcd === 'baik' && $keyboard === 'baik' && $ram === 'tinggi' && $baterai === 'sedang') {
            return 'layak';
        }

        // R241 - R243: LCD Baik, Keyboard Baik, RAM Tinggi, Baterai Tinggi
        if ($lcd === 'baik' && $keyboard === 'baik' && $ram === 'tinggi' && $baterai === 'tinggi') {
            return 'layak';
        }


        // --- KATEGORI 3: SISA ATURAN LAINNYA ADALAH CUKUP LAYAK ---
        // Mencakup R13-R18, R22-R27, R31-R54, R58-R81, R85-R150, R154-R159, R166-R204, R208-R213, R220-R228, R235-R237
        return 'cukup_layak';
    }
}
