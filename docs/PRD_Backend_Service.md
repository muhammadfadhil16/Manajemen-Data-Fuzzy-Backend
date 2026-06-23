# Product Requirements Document (PRD)
## Sistem Penilaian Kelayakan Laptop Bekas (BackendService)

| Status | Revised |
| :--- | :--- |
| **Versi** | 1.5 |
| **Tanggal** | 21 Juni 2026 |
| **Pemilik** | Muhammad Fadhil |

---

## 1. Pendahuluan
### 1.1 Ringkasan Proyek
**BackendService** adalah API gateway dan *Orchestrator* utama dalam ekosistem *Sistem Penilaian Kelayakan Laptop Bekas*. Sistem ini bertujuan untuk memberikan penilaian objektif terhadap kondisi laptop bekas dengan menggabungkan perhitungan matematika (Fuzzy Logic Mamdani via EvaluatorService) dan kecerdasan buatan (Gemini AI).

### 1.2 Tujuan Utama
1.  **Standardisasi Penilaian:** Menghilangkan subjektivitas dalam mengevaluasi 5 komponen fisik dan performa laptop.
2.  **Pusat Pengetahuan (Knowledge Base):** Menyimpan master data benchmark prosesor dan matriks ratusan aturan fuzzy secara dinamis.
3.  **Transparansi Harga:** Memberikan estimasi harga beli/jual yang adil berdasarkan skor kelayakan teknis.
4.  **Rekomendasi Pintar:** Memberikan narasi saran yang mudah dipahami pengguna melalui Gemini AI (100% dari AI, tanpa fallback PHP).

---

## 1.3 Perubahan Terbaru

### v1.5 (21 Juni 2026)
- **Dynamic Thresholds**: Menambahkan tabel `fuzzy_thresholds` untuk menyimpan batas status kelayakan (`tidak_layak_batas`, `layak_batas`) yang dapat diubah langsung dari database tanpa deploy ulang.
- **Defuzzifikasi dari Database**: Kurva output defuzzifikasi (Kelayakan) dipindahkan dari hardcode ke tabel `fuzzy_configs`, sehingga parameter kurva juga dapat diubah dinamis.
- **Bug Fix Kurva Turun**: Memperbaiki index parameter kurva `kurvaTurun()` dari [0][1] menjadi [2][3] agar sesuai dengan bentuk kurva trapesium scikit-fuzzy. Sebelumnya kategori buruk/rendah selalu menghasilkan nilai 0 untuk input positif.

### v1.4 (20 Juni 2026)
- **CORS Configuration**: Menambahkan dan mendokumentasikan konfigurasi CORS (`config/cors.php`) dengan `allowed_origins => ['*']` untuk memungkinkan integrasi dari klien eksternal (pihak ketiga).
- **Dual Content-Type Support**: Endpoint `POST /api/assessments` kini mendukung **JSON** (`Content-Type: application/json`) maupun **multipart/form-data** untuk fleksibilitas integrasi.
- **Dokumentasi Field API**: Memperbaiki dokumentasi nama field yang benar: `lcd`, `battery`, `keyboard`, `ram`, `processor_id` (bukan `lcd_input`, `battery_input`, dsb).
- **External Integration Guide**: Menambahkan panduan reusability untuk pengembang pihak ketiga yang ingin mengintegrasikan sistem tanpa melalui frontend asli.

### v1.3 (13 Juni 2026)
- **Bug Fix**: Memperbaiki typo pada `AssessmentController.php` line 89: `'benchmark_scorre'` → `'benchmark_score'` yang menyebabkan error `SQLSTATE[HY000]: General error: 1364 Field 'benchmark_score' doesn't have a default value` saat membuat processor baru.
- **Testing Endpoint**: Menambahkan endpoint `POST /api/processors` untuk membuat data processor secara manual. **Catatan: Endpoint ini dibuat hanya untuk keperluan testing/fleksibilitas pengujian backend, bukan bagian dari alur sistem produksi.** Pada sistem produksi, data processor dikelola melalui seeder/migrasi atau admin panel terpisah.

---

## 2. Arsitektur Sistem
Sistem dirancang dengan pendekatan **Service-Oriented Architecture (SOA)** menggunakan Docker:

* **Frontend (Vue 3):** Antarmuka input dan visualisasi hasil.
* **BackendService (Laravel 12):** Mengelola database, logika bisnis, orchestrator HTTP client, dan integrasi AI.
* **EvaluatorService (Port 8001):** Microservice *stateless* (Laravel 12) yang murni berfungsi sebagai *Fuzzy Engine*.
* **Database (MySQL 8):** Penyimpanan data master, matriks aturan fuzzy, dan riwayat penilaian.
* **Gemini AI API:** Memberikan analisis deskriptif (model `gemini-2.5-flash` via REST API).

---

## 3. Fitur Utama (Functional Requirements)

### 3.1 Manajemen Penilaian (Assessment)
* **Input Variabel (7 Parameter + Opsional):**
    * `customer_name` (string, required): Nama customer/pemilik laptop.
    * `laptop_name` (string, required): Nama/model laptop.
    * `lcd` (integer, 0-100, required): Kondisi layar.
    * `battery` (integer, 0-100, required): Kesehatan baterai.
    * `keyboard` (integer, 0-100, required): Kondisi keyboard.
    * `ram` (numeric, >0, required): Kapasitas RAM dalam GB.
    * `market_price` (integer, >0, required): Harga pasaran saat ini.
    * `processor_id` (integer, nullable): ID processor dari master data (`/api/processors`). Alternatif: `processor_name` + `processor_input` untuk membuat processor baru.
    * `description` (string, nullable): Deskripsi fisik tambahan (digunakan untuk analisis AI jika relevan).
    * `use_ai` (boolean, nullable): Aktifkan/nonaktifkan AI Gemini untuk penilaian ini.
    * `images` (array of files, max 3, nullable): File gambar laptop (jpeg/png, max 2MB per file).
* **Proses:** Mengumpulkan parameter dan aturan, lalu mengirimkannya ke `EvaluatorService`.
* **Output:** Skor kelayakan (0-100), Status (**Tidak Layak** / **Cukup Layak** / **Layak**), Estimasi Harga, dan Kesimpulan AI (opsional).

### 3.2 Manajemen Basis Pengetahuan (Knowledge Base)
* **Master Processor:** Menyimpan skor PassMark CPU untuk mengonversi model CPU menjadi input numerik fuzzy.
* **Aturan Fuzzy Dinamis:** Menyimpan parameter kurva (`turun`, `segitiga`, `trapesium`, `naik`) dan **243 matriks kombinasi aturan IF-THEN** di database agar dapat dieksekusi secara dinamis tanpa mengubah *hardcode*.

### 3.3 Analisis Naratif AI
* Mengintegrasikan Google Gemini AI (`gemini-2.5-flash`) untuk menghasilkan `ai_conclusion` berdasarkan skor teknis, kondisi masing-masing part, dan deskripsi pengguna (jika relevan).
* **Prompt bersifat objektif dan faktual:** Menggunakan sudut pandang ketiga, tanpa kata sifat subjektif (`bagus`, `oke`, `jelek`, `worth it`), dan dalam bahasa Indonesia.
* **Output dibersihkan:** Hasil Gemini di-*sanitize* untuk menghapus markdown, simbol berulang, dan format berlebihan.
* **Deskripsi tidak relevan:** Jika deskripsi tidak mengandung kata kunci terkait laptop, deskripsi dikeluarkan dari prompt (tidak dikirim ke Gemini) dan flag `description_ignored` dikembalikan ke frontend.
* **Fallback terbatas:** Jika Gemini gagal (API key kosong, timeout, error), sistem mengembalikan `'tidak ada catatan tambahan'` — tidak ada fallback conclusion statis dari PHP.

### 3.4 Filter & Pencarian Riwayat
* **Pencarian (`search`):** Mencocokkan `customer_name`, `laptop_name`, atau `id`.
* **Filter Periode (`start_date`, `end_date`):** Filter berdasarkan rentang tanggal. Input tanggal diterima dalam zona waktu **Asia/Jakarta (WIB)** dan dikonversi ke **UTC** untuk query database.
  * `start_date` → `Carbon::parse(tanggal, 'Asia/Jakarta')->startOfDay()->setTimezone('UTC')`
  * `end_date` → `Carbon::parse(tanggal, 'Asia/Jakarta')->endOfDay()->setTimezone('UTC')`

---

## 4. Persyaratan Teknis

### 4.1 Tech Stack
* **Framework:** Laravel 12.
* **Language:** PHP 8.2+.
* **Database:** MySQL 8.
* **HTTP Client:** Guzzle (Laravel Http Facade).
* **Testing:** PHPUnit.

### 4.2 Data Model (Schema Utama)

#### Tabel `processors`
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `id` | PK | Auto Increment |
| `name` | String | Nama model (ex: Intel Core i5-1135G7) |
| `benchmark_score` | Integer| Skor benchmark (PassMark) |
| `category` | Enum | Rendah, Sedang, Tinggi |

#### Tabel `fuzzy_configs` (Kurva Fuzzifikasi & Defuzzifikasi)
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `variable` | String | LCD, KesehatanBaterai, Processor, KondisiKeyboard, RAM, Kelayakan |
| `category` | String | buruk/sedang/baik/rendah/tinggi/tidak_layak/cukup_layak/layak |
| `curve_type` | String | trapesium, segitiga |
| `parameters` | JSON | [a, b, c, d] untuk trapesium, [a, b, c] untuk segitiga |

#### Tabel `fuzzy_rules` (Matriks 243 Aturan)
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `lcd` | Enum | buruk, sedang, baik |
| `keyboard` | Enum | buruk, sedang, baik |
| `ram` | Enum | rendah, sedang, tinggi |
| `baterai` | Enum | rendah, sedang, tinggi |
| `processor`| Enum | rendah, sedang, tinggi |
| `output` | Enum | tidak_layak, cukup_layak, layak |

#### Tabel `fuzzy_thresholds` (Batas Kelayakan Dinamis)
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `name` | String (unique) | tidak_layak_batas, layak_batas |
| `value` | Decimal(5,2) | Nilai batas (default: 65.00, 85.00) |

#### Tabel `assessments` (Riwayat Penilaian)
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `customer_name` | String | Nama customer/pemilik laptop |
| `laptop_name` | String | Model/Seri laptop |
| `final_score` | Float | Hasil perhitungan fuzzy (0-100) |
| `status` | String | Tidak Layak / Cukup Layak / Layak |
| `market_price` | BigInt | Input harga user |
| `estimated_price` | BigInt | `market_price * (final_score/100)` |
| `ai_conclusion` | Text | Output dari Gemini AI (atau 'tidak ada catatan tambahan' jika gagal) |
| `description` | Text | Catatan tambahan user (mentah, sebelum filter relevansi) |
| `created_at` | Timestamp | Waktu penilaian (disimpan dalam UTC) |

---

## 4.3 CORS Configuration

BackendService mengizinkan akses dari origin mana pun untuk memudahkan integrasi eksternal.

**Konfigurasi (`config/cors.php`):**
```php
'paths'            => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods'  => ['*'],
'allowed_origins'  => ['*'],     // Izinkan semua origin (termasuk file://, null)
'allowed_headers'  => ['*'],     // Izinkan semua header (termasuk Content-Type: application/json)
```

**Catatan:**
- Middleware `HandleCors` terdaftar secara **global otomatis** oleh Laravel 12 — tidak perlu konfigurasi tambahan.
- Preflight `OPTIONS` request ditangani langsung oleh middleware.
- Endpoint `POST /api/assessments` mendukung **JSON** (`application/json`) maupun **multipart/form-data**, sehingga klien pihak ketiga cukup mengirim JSON tanpa perlu mengelola file upload.

---

## 4.4 API Endpoints

### Processor
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/processors` | List semua processor (produksi) |
| POST | `/api/processors` | **Testing only** — Membuat processor baru untuk keperluan pengujian. **Tidak digunakan dalam alur produksi.** |

### Assessment
| Method | Endpoint | Description | Content-Type |
|--------|----------|-------------|--------------|
| GET | `/api/assessments` | List riwayat penilaian dengan filter & pagination | - |
| POST | `/api/assessments` | Buat penilaian baru (alur utama produksi) | JSON atau multipart/form-data |
| GET | `/api/assessments/{id}` | Detail penilaian | - |
| DELETE | `/api/assessments/{id}` | Hapus penilaian | - |

**Field untuk `POST /api/assessments`:**

| Field | Tipe | Required | Deskripsi |
|-------|------|----------|-----------|
| `customer_name` | string | ✅ | Nama pelanggan |
| `laptop_name` | string | ✅ | Nama/model laptop |
| `lcd` | integer | ✅ | Skor LCD (0-100) |
| `battery` | integer | ✅ | Skor baterai (0-100) |
| `keyboard` | integer | ✅ | Skor keyboard (0-100) |
| `ram` | numeric | ✅ | Kapasitas RAM (GB) |
| `market_price` | integer | ✅ | Harga pasar (Rp) |
| `processor_id` | integer | kondisi | ID processor (master data). Wajib jika `processor_name` tidak diisi. |
| `processor_name` | string | kondisi | Nama processor baru. Wajib jika `processor_id` tidak diisi. |
| `processor_input` | numeric | kondisi | Skor benchmark processor baru. Wajib jika `processor_id` tidak diisi. |
| `description` | string | ❌ | Catatan tambahan untuk analisis AI |
| `use_ai` | boolean | ❌ | Aktifkan AI (default: false) |
| `images` | file[] | ❌ | Maks 3 file (jpeg/png, max 2MB) |

---

## 5. Logika Bisnis (Business Logic)

### 5.1 Perhitungan Harga
Sistem menggunakan metode depresiasi berbasis skor kelayakan:
`Harga Estimasi = Floor(Harga Pasar * (Skor Akhir / 100))`

### 5.2 Alur Eksekusi API `POST /api/assessments`:
1.  **Validasi Input:** Mengecek kelengkapan variabel (customer_name, laptop_name, lcd, battery, keyboard, ram, market_price, processor_id atau processor_name+input).
2.  **Query Master Data:** Ambil `benchmark_score` dari tabel `processors` (berdasarkan `processor_id`) atau buat processor baru (jika `processor_name` + `processor_input`).
3. **Query Knowledge Base:** Ambil kurva fuzzifikasi & defuzzifikasi dari `fuzzy_configs`, 243 aturan dari `fuzzy_rules`, dan threshold dari `fuzzy_thresholds`.
4. **Orkestrasi:** Susun JSON (fuzzifikasi, matrix_aturan, defuzzifikasi, thresholds) dan kirim ke `EvaluatorService` via HTTP POST.
5.  **Terima Hasil:** Ekstrak skor (`nilaiKelayakan`) dan status (`statusKelayakan`) dari *response* Evaluator.
6.  **Hitung Estimasi Harga:** `estimated_price = floor(market_price * (final_score / 100))`.
7.  **Simpan Gambar:** Jika ada file gambar, simpan ke `storage/app/public/evaluations` dan catat di tabel `assessment_images`.
8.  **Deteksi Relevansi Deskripsi:** Periksa apakah deskripsi mengandung kata kunci terkait laptop (minimal 3 kata, dan mengandung kata seperti "keyboard", "baterai", "lcd", dll). Jika tidak, deskripsi tidak dikirim ke Gemini.
9.  **AI Naratif:** Kirim *prompt* ke Gemini AI untuk mendapatkan kesimpulan naratif objektif. Hasil di-*sanitize* (hapus markdown, simbol berulang). Jika Gemini gagal, `ai_conclusion` diisi `'tidak ada catatan tambahan'`.
10. **Simpan & Return:** Simpan seluruh data ke `assessments` dan kembalikan *response* (termasuk flag `description_ignored`, `ai_used`, `ai_warning`) ke Frontend.

### 5.3 Alur Filter Riwayat `GET /api/assessments`:
1.  **Search:** Filter berdasarkan `customer_name`, `laptop_name`, atau `id`.
2.  **Date Filter:** Input tanggal WIB dikonversi ke UTC menggunakan `Carbon` untuk query yang akurat.
3.  **Pagination:** Hasil diurutkan `desc` berdasarkan `created_at`, 10 data per halaman.
4.  **Return:** Data `assessments` beserta relasi `processor` dan `images`.

---

## 6. Rencana Pengujian
* **Unit Test:** Pengujian logika perhitungan harga, validasi form request, dan seeder.
* **Feature Test:** Mocking respons dari `EvaluatorService` dan `Gemini API` untuk memastikan orkestrasi internal dan penyimpanan data berjalan lancar tanpa *hit* ke API asli.

---

## 7. Batasan dan Ruang Lingkup (Project Boundaries)

### 7.1 Batasan Fungsional (Out of Scope)
* **Pencarian Harga Otomatis:** Tidak melakukan *web scraping* harga pasar. Harga murni dari input manual pengguna.
* **Variabel Penilaian Terbatas:** Penilaian difokuskan secara matematis pada **5 komponen utama** (LCD, Baterai, Processor, Keyboard, RAM). Komponen minor (port USB, engsel lecet) tidak masuk dalam kalkulasi fuzzy.

### 7.2 Batasan Teknis & Dependensi
* **Ketergantungan Microservice:** BackendService tidak bisa menghitung skor sendiri. Kegagalan (timeout) pada `EvaluatorService` akan menghentikan proses penilaian.
* **AI Analysis 100% dari Gemini:** Tidak ada *fallback conclusion* dari PHP. Jika Gemini gagal, `ai_conclusion` bernilai `'tidak ada catatan tambahan'` (frontend menyembunyikan sesi AI).
* **Timezone Handling:** Database menyimpan `created_at` dalam UTC. Filter tanggal dari frontend (WIB) dikonversi ke UTC agar akurat.

### 7.3 Target Lingkungan
* Proyek dioptimalkan dalam lingkungan **Docker**.
* Komunikasi antar kontainer menggunakan *internal network* Docker.

---

## 8. Integrasi Pihak Ketiga (Reusability)

BackendService dirancang agar dapat diintegrasikan oleh sistem eksternal tanpa melalui frontend asli.

### 8.1 Contoh Integrasi (fetch JavaScript)

```javascript
const payload = {
  customer_name: "John Doe",
  laptop_name: "Lenovo Thinkpad X230",
  lcd: 80,
  battery: 75,
  keyboard: 80,
  ram: 8,
  market_price: 3000000,
  processor_id: 1,  // atau gunakan processor_name + processor_input
  description: "Kondisi fisik baik, hanya ada lecet kecil di bagian bodi"
};

const response = await fetch('http://localhost:8000/api/assessments', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(payload)
});

const result = await response.json();
console.log(result.data.final_score, result.data.status);
```

### 8.2 Catatan Penting
- **CORS** sudah dikonfigurasi untuk mengizinkan semua origin (`*`).
- API dapat diakses dari origin `null` (file:// protocol) jika browser mendukung.
- Untuk produksi, disarankan menjalankan klien dari web server (bukan file://) untuk kompatibilitas browser maksimal.
- Response selalu menyertakan header `Access-Control-Allow-Origin: *`.

---

*Dokumen ini merupakan referensi hidup dan akan diperbarui seiring perkembangan fitur.*
