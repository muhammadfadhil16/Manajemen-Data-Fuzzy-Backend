# Product Requirements Document (PRD)
## Sistem Penilaian Kelayakan Laptop Bekas (BackendService)

| Status | Draft (Revisi Skripsi Pratiwi) |
| :--- | :--- |
| **Versi** | 1.1 |
| **Tanggal** | 4 Juni 2026 |
| **Pemilik** | Muhammad Fadhil |

---

## 1. Pendahuluan
### 1.1 Ringkasan Proyek
**BackendService** adalah API gateway dan *Orchestrator* utama dalam ekosistem *Sistem Penilaian Kelayakan Laptop Bekas*. Sistem ini bertujuan untuk memberikan penilaian objektif terhadap kondisi laptop bekas dengan menggabungkan perhitungan matematika (Fuzzy Logic Mamdani via EvaluatorService) dan kecerdasan buatan (Gemini AI).

### 1.2 Tujuan Utama
1.  **Standardisasi Penilaian:** Menghilangkan subjektivitas dalam mengevaluasi 5 komponen fisik dan performa laptop.
2.  **Pusat Pengetahuan (Knowledge Base):** Menyimpan master data benchmark prosesor dan matriks ratusan aturan fuzzy secara dinamis.
3.  **Transparansi Harga:** Memberikan estimasi harga beli/jual yang adil berdasarkan skor kelayakan teknis.
4.  **Rekomendasi Pintar:** Memberikan narasi saran yang mudah dipahami pengguna melalui Gemini AI.

---

## 2. Arsitektur Sistem
Sistem dirancang dengan pendekatan **Service-Oriented Architecture (SOA)** menggunakan Docker:

* **Frontend (Vue 3):** Antarmuka input dan visualisasi hasil.
* **BackendService (Laravel 12):** Mengelola database, logika bisnis, orchestrator HTTP client, dan integrasi AI.
* **EvaluatorService (Port 8001):** Microservice *stateless* (Laravel 12) yang murni berfungsi sebagai *Fuzzy Engine*.
* **Database (MySQL 8):** Penyimpanan data master, matriks aturan fuzzy, dan riwayat penilaian.
* **Gemini AI API:** Memberikan analisis deskriptif.

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
    * `Description`: Deskripsi fisik tambahan (opsional).
* **Proses:** Mengumpulkan parameter dan aturan, lalu mengirimkannya ke `EvaluatorService`.
* **Output:** Skor kelayakan (0-100), Status (**Tidak Layak, Cukup Layak, Layak**), dan Estimasi Harga.

### 3.2 Manajemen Basis Pengetahuan (Knowledge Base)
* **Master Processor:** Menyimpan skor PassMark CPU untuk mengonversi model CPU menjadi input numerik fuzzy.
* **Aturan Fuzzy Dinamis:** Menyimpan parameter kurva (`turun`, `segitiga`, `trapesium`, `naik`) dan **243 matriks kombinasi aturan IF-THEN** di database agar dapat dieksekusi secara dinamis tanpa mengubah *hardcode*.

### 3.3 Analisis Naratif AI
* Mengintegrasikan Google Gemini AI (`gemini-2.5-flash`) untuk menghasilkan `ai_conclusion` berdasarkan skor teknis, kondisi masing-masing part, dan deskripsi keluhan pengguna.

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
| `laptop_name` | String | Model/Seri laptop |
| `final_score` | Float | Hasil perhitungan fuzzy (0-100) |
| `status` | String | Tidak Layak / Cukup Layak / Layak |
| `market_price` | BigInt | Input harga user |
| `estimated_price` | BigInt | `market_price * (final_score/100)` |
| `ai_conclusion` | Text | Output dari Gemini AI |

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
6.  **Kalkulasi & AI:** Hitung `estimated_price` dan kirim *prompt* ke Gemini AI untuk mendapatkan kesimpulan naratif.
7.  **Simpan & Return:** Simpan seluruh data ke `assessments` dan kembalikan *response* ke Frontend.

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
* **Sistem Fallback:** Jika Gemini AI gagal diakses (kuota habis/RTO), sistem akan memberikan teks rekomendasi statis (*local fallback*) berdasarkan status kelayakan agar aplikasi tidak crash.

### 7.3 Target Lingkungan
* Proyek dioptimalkan dalam lingkungan **Docker**.
* Komunikasi antar kontainer menggunakan *internal network* Docker.

---
*Dokumen ini merupakan referensi hidup dan akan diperbarui seiring perkembangan fitur.*