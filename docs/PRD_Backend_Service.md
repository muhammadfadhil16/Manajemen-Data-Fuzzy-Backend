# Product Requirements Document (PRD)
## Sistem Penilaian Kelayakan Laptop Bekas (BackendService)

| Status | Revised |
| :--- | :--- |
| **Versi** | 1.2 |
| **Tanggal** | 9 Juni 2026 |
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
* **Input Variabel (5 Parameter):**
    * `LCD`: Kondisi layar (0-100).
    * `Keyboard`: Kondisi tombol (0-100).
    * `RAM`: Kapasitas memori dalam GB (Numerik, >0).
    * `Kesehatan Baterai`: (0-100).
    * `Processor`: ID dari master data prosesor.
    * `Market Price`: Harga pasaran saat ini.
    * `customer_name`: Nama customer/pemilik laptop (required).
    * `Description`: Deskripsi fisik tambahan (opsional, digunakan untuk analisis AI jika relevan).
* **Proses:** Mengumpulkan parameter dan aturan, lalu mengirimkannya ke `EvaluatorService`.
* **Output:** Skor kelayakan (0-100), Status (**Tidak Layak, Cukup Layak, Layak**), dan Estimasi Harga.

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

#### Tabel `fuzzy_rules` (Matriks 243 Aturan)
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `lcd` | Enum | buruk, sedang, baik |
| `keyboard` | Enum | buruk, sedang, baik |
| `ram` | Enum | rendah, sedang, tinggi |
| `baterai` | Enum | rendah, sedang, tinggi |
| `processor`| Enum | rendah, sedang, tinggi |
| `output` | Enum | tidak_layak, cukup_layak, layak |

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

## 5. Logika Bisnis (Business Logic)

### 5.1 Perhitungan Harga
Sistem menggunakan metode depresiasi berbasis skor kelayakan:
`Harga Estimasi = Floor(Harga Pasar * (Skor Akhir / 100))`

### 5.2 Alur Eksekusi API `POST /api/assessments`:
1.  **Validasi Input:** Mengecek kelengkapan 5 variabel input dan keberadaan `processor_id`.
2.  **Query Master Data:** Ambil `benchmark_score` dari tabel `processors`.
3.  **Query Knowledge Base:** Ambil seluruh 243 aturan dari tabel `fuzzy_rules`.
4.  **Orkestrasi:** Susun JSON berukuran besar dan tembak ke `EvaluatorService` via HTTP POST.
5.  **Terima Hasil:** Ekstrak skor dan status dari *response* Evaluator.
6.  **Deteksi Relevansi Deskripsi:** Periksa apakah deskripsi mengandung kata kunci terkait laptop. Jika tidak, deskripsi tidak dikirim ke Gemini.
7.  **AI Naratif:** Kirim *prompt* ke Gemini AI untuk mendapatkan kesimpulan naratif objektif. Hasil di-*sanitize*.
8.  **Simpan & Return:** Simpan seluruh data ke `assessments` dan kembalikan *response* (termasuk flag `description_ignored`) ke Frontend.

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

*Dokumen ini merupakan referensi hidup dan akan diperbarui seiring perkembangan fitur.*
