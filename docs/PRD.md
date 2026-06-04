# Product Requirements Document (PRD)
## Sistem Penilaian Kelayakan Laptop Bekas (BackendService)

| Status | Draft |
| :--- | :--- |
| **Versi** | 1.0 |
| **Tanggal** | 4 Juni 2026 |
| **Pemilik** | Muhammad Fadhil |

---

## 1. Pendahuluan
### 1.1 Ringkasan Proyek
**BackendService** adalah API gateway utama dalam ekosistem *Sistem Penilaian Kelayakan Laptop Bekas*. Sistem ini bertujuan untuk memberikan penilaian objektif terhadap kondisi laptop bekas dengan menggabungkan perhitungan matematika (Fuzzy Logic) dan kecerdasan buatan (Gemini AI).

### 1.2 Tujuan Utama
1.  **Standardisasi Penilaian:** Menghilangkan subjektivitas dalam mengevaluasi kondisi fisik dan performa laptop.
2.  **Transparansi Harga:** Memberikan estimasi harga beli/jual yang adil berdasarkan skor kelayakan teknis.
3.  **Rekomendasi Pintar:** Memberikan narasi saran yang mudah dipahami pengguna mengenai kelayakan unit yang dinilai.

---

## 2. Arsitektur Sistem
Sistem dirancang dengan pendekatan **Service-Oriented Architecture (SOA)** menggunakan Docker:

*   **Frontend (Vue 3):** Antarmuka input dan visualisasi hasil.
*   **BackendService (Laravel 12):** Mengelola database, logika bisnis, orchestrator antar service, dan integrasi AI.
*   **EvaluatorService:** Microservice (Python/Go/Node) yang berfungsi sebagai *Fuzzy Engine*.
*   **Database (MySQL):** Penyimpanan data permanen untuk penilaian dan aturan fuzzy.
*   **Gemini AI API:** Memberikan analisis deskriptif.

---

## 3. Fitur Utama (Functional Requirements)

### 3.1 Manajemen Penilaian (Assessment)
*   **Input Variabel:**
    *   `LCD`: Kondisi layar (0-100).
    *   `Kesehatan Baterai`: (0-100).
    *   `Processor`: Skor benchmark/performa.
    *   `Keyboard`: Kondisi tombol (0-100).
    *   `Market Price`: Harga pasaran saat ini.
    *   `Description`: Deskripsi fisik tambahan (opsional).
*   **Proses:** Menghitung skor akhir melalui `EvaluatorService`.
*   **Output:** Skor kelayakan (0-100), Status (Bagus, Normal, Tidak Bagus), dan Estimasi Harga.

### 3.2 Manajemen Aturan Fuzzy (Fuzzy Rules)
*   Aturan fuzzy (parameter kurva) disimpan di database agar dapat disesuaikan tanpa mengubah kode program.
*   Mendukung tipe kurva: `turun`, `segitiga`, dan `naik`.

### 3.3 Analisis Naratif AI
*   Mengintegrasikan Google Gemini AI (`gemini-2.5-flash`) untuk menghasilkan `ai_conclusion` berdasarkan skor teknis dan deskripsi user.

---

## 4. Persyaratan Teknis

### 4.1 Tech Stack
*   **Framework:** Laravel 12.
*   **Language:** PHP 8.2+.
*   **Database:** MySQL 8.
*   **HTTP Client:** Guzzle (Laravel Http Facade).
*   **Testing:** PHPUnit.

### 4.2 Data Model (Schema Utama)
#### Tabel `assessments`
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `laptop_name` | String | Model/Seri laptop |
| `final_score` | Float | Hasil perhitungan fuzzy |
| `status` | String | Bagus/Normal/Tidak Bagus |
| `market_price` | BigInt | Input harga user |
| `estimated_price` | BigInt | `market_price * (final_score/100)` |
| `ai_conclusion` | Text | Output dari Gemini AI |

---

## 5. Logika Bisnis (Business Logic)

### 5.1 Perhitungan Harga
Sistem menggunakan metode depresiasi berbasis skor:
`Harga Estimasi = Floor(Harga Pasar * (Skor Akhir / 100))`

### 5.2 Alur Eksekusi API `POST /api/assessments`:
1.  Validasi input user.
2.  Ambil `fuzzy_rules` dari database.
3.  Kirim data ke `EvaluatorService` via HTTP POST.
4.  Terima skor dan status kelayakan.
5.  Hitung `estimated_price`.
6.  (Opsional) Kirim data ke Gemini AI jika deskripsi tersedia.
7.  Simpan ke database dan kembalikan response JSON.

---

## 6. Rencana Pengujian
*   **Unit Test:** Pengujian logika perhitungan harga dan validasi input.
*   **Feature Test:** Mocking `EvaluatorService` dan `Gemini API` untuk memastikan flow penyimpanan data berhasil tanpa ketergantungan service eksternal saat testing.
*   **Integration Test:** Memastikan koneksi antar container (Backend -> Evaluator -> DB) berjalan lancar.

---

## 7. Batasan dan Ruang Lingkup (Project Boundaries)

### 7.1 Batasan Fungsional (Out of Scope)
*   **Pencarian Harga Otomatis:** Sistem tidak melakukan *web scraping* atau integrasi ke e-commerce untuk mencari harga pasar secara otomatis. Harga pasar (`market_price`) murni berdasarkan input manual pengguna.
*   **Otentikasi Pengguna:** Versi saat ini tidak memiliki sistem login/registrasi. API bersifat publik (atau diasumsikan berada dalam jaringan internal yang aman).
*   **Manajemen Gambar:** Sistem tidak mendukung pengunggahan atau penyimpanan foto fisik laptop.
*   **Variabel Penilaian Terbatas:** Penilaian saat ini hanya terbatas pada 4 komponen utama (LCD, Baterai, Processor, Keyboard). Komponen lain seperti kondisi engsel, port USB, atau webcam tidak masuk dalam hitungan skor fuzzy secara matematis.

### 7.2 Batasan Teknis & Dependensi
*   **Dependensi Microservice:** Keakuratan skor kelayakan sepenuhnya bergantung pada ketersediaan dan logika di `EvaluatorService`. Jika service tersebut mati, `BackendService` tidak dapat menghitung skor baru.
*   **Koneksi Internet:** Diperlukan koneksi internet aktif untuk memanggil API Gemini AI. Jika koneksi terputus, sistem akan menggunakan *local fallback recommendation* (Simulasi AI).
*   **Skalabilitas Database:** Database MySQL dirancang untuk penyimpanan riwayat penilaian, bukan untuk database spesifikasi laptop (katalog) yang sangat besar.

### 7.3 Target Lingkungan
*   Proyek ini dioptimalkan untuk berjalan di lingkungan **Docker**.
*   Backend menggunakan standar REST API untuk berkomunikasi dengan Frontend.

---
*Dokumen ini merupakan referensi hidup dan akan diperbarui seiring perkembangan fitur.*
