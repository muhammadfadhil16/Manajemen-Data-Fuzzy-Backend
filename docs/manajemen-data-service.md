# Dokumentasi Sistem: Backend Service (Core)

Sistem **Backend Service** adalah layanan inti dalam ekosistem *Sistem Penilaian Kelayakan Laptop Bekas*. Layanan ini bertanggung jawab untuk mengelola data master aturan fuzzy, menyimpan hasil penilaian, berintegrasi dengan layanan perhitungan fuzzy eksternal, serta menghasilkan rekomendasi cerdas menggunakan **Gemini AI**.

## 1. Ikhtisar Arsitektur

Sistem ini dirancang menggunakan pendekatan *Service-Oriented Architecture* (SOA) yang di-deploy menggunakan ekosistem **Docker**.

- **Backend-Service (Layanan Ini)**: Menyimpan data penilaian, konfigurasi aturan, dan melakukan interaksi dengan Gemini AI.
- **Evaluator-Service (Fuzzy Eksternal)**: Melakukan perhitungan logika fuzzy (fuzzifikasi, inferensi, defuzzifikasi).
- **mysql-database**: Database relasional (MySQL) yang diakses oleh Backend-Service.

### Alur Kerja (Workflow)
1. User mengirim data kondisi fisik dan deskripsi laptop melalui Frontend API.
2. Backend-Service mengambil aturan fuzzy terbaru dari database.
3. Backend-Service mengirim data input (tanpa deskripsi) dan aturan ke **Evaluator-Service**.
4. Evaluator-Service mengembalikan skor kelayakan dan status berdasarkan inferensi Mamdani.
5. Backend-Service mengirimkan skor, status, dan deskripsi ke **Gemini AI** untuk mendapatkan rekomendasi naratif (atau menggunakan simulasi AI lokal jika gagal).
6. Backend-Service menyimpan hasil akhir (termasuk kesimpulan AI) ke database dan merespon ke Frontend.

## 2. Struktur Database

Sistem ini memiliki dua tabel utama untuk mendukung proses penilaian:

### a. `assessments`
Menyimpan riwayat penilaian laptop beserta rekomendasi AI.

| Kolom | Tipe Data | Deskripsi |
|-------|-----------|-----------|
| `id` | BigInt (PK) | Identifier unik. |
| `laptop_name` | String | Nama/Model laptop yang dinilai. |
| `lcd_input` | Float | Nilai kondisi layar (input). |
| `battery_input` | Float | Nilai kesehatan baterai (input). |
| `ram_input` | Float | Kapasitas/Skor RAM (input). |
| `keyboard_input`| Float | Skor kondisi keyboard (input). |
| `final_score` | Float | Hasil perhitungan nilai kelayakan (0-100). |
| `status` | String | Status kelayakan (e.g., Bagus, Sedang, Kurang Layak). |
| `description` | Text | (Opsional) Deskripsi catatan kondisi fisik tambahan. |
| `ai_conclusion` | Text | Kesimpulan dan rekomendasi yang dihasilkan oleh Gemini AI. |

### b. `fuzzy_rules`
Menyimpan parameter kurva untuk variabel fuzzy.

| Kolom | Tipe Data | Deskripsi |
|-------|-----------|-----------|
| `variable` | String | Nama variabel (LCD, Baterai, dll). |
| `category` | String | Kategori (rendah, normal, tinggi). |
| `curve_type` | String | Tipe kurva (turun, naik, segitiga). |
| `parameters` | JSON | Parameter kurva (titik-titik koordinat). |

## 3. Komponen Utama

### `AssessmentController`
Terletak di `app/Http/Controllers/Api/AssessmentController.php`.
Menangani permintaan HTTP untuk pembuatan penilaian baru. Melakukan validasi input, memanggil service integrasi, melakukan pemanggilan HTTP POST ke Gemini AI, serta menyediakan mekanisme *fallback* Simulasi AI.

### `FuzzyIntegrationService`
Terletak di `app/Services/External/FuzzyIntegrationService.php`.
Komponen ini adalah jembatan (bridge) ke Evaluator-Service (Microservice Fuzzy).
- **Tugas**: Memformat data aturan dari database ke format JSON yang dimengerti oleh microservice eksternal.
- **Endpoint**: Mengirim permintaan ke `POST /api/penilaian`.

## 4. Dokumentasi API

### Daftar Penilaian (Pagination)
Mengambil daftar riwayat penilaian yang sudah dilakukan.

**Endpoint:** `GET /api/assessments?page=1`

**Response (Success):**
```json
{
    "status": "success",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "laptop_name": "Lenovo Legion 5 Pro",
                "lcd_input": 100,
                "battery_input": 80,
                "ram_input": 16,
                "keyboard_input": 100,
                "final_score": 84.42,
                "status": "Bagus",
                "description": "Bodi mulus 98%, charger original lengkap, port aman",
                "ai_conclusion": "Dengan kondisi fisik yang mulus sekali tanpa goresan dan status 'Bagus', laptop ini sangat menjanjikan. Pastikan untuk memverifikasi performa secara keseluruhan untuk memastikan kepuasan Anda.",
                "created_at": "2026-05-20T03:32:25.000000Z",
                "updated_at": "2026-05-20T03:32:25.000000Z"
            }
        ],
        "first_page_url": "...",
        "from": 1,
        "last_page": 1,
        "last_page_url": "...",
        "links": [ ],
        "next_page_url": null,
        "path": "...",
        "per_page": 10,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
```

### Simpan Penilaian Baru
Digunakan untuk menghitung kelayakan laptop, mendapatkan kesimpulan AI, dan menyimpannya ke database.

**Endpoint:** `POST /api/assessments`

**Request Body:**
```json
{
    "laptop_name": "Lenovo Legion 5 Pro",
    "lcd": 100,
    "battery": 80,
    "ram": 16,
    "keyboard": 100,
    "description": "Bodi mulus 98%, charger original lengkap, port aman"
}
```

**Response (Success):**
```json
{
    "status": "success",
    "message": "Penilaian berhasil dihitung dan disimpan.",
    "result": {
        "id": 1,
        "laptop_name": "Lenovo Legion 5 Pro",
        "lcd_input": 100,
        "battery_input": 80,
        "ram_input": 16,
        "keyboard_input": 100,
        "final_score": 84.42,
        "status": "Bagus",
        "description": "Bodi mulus 98%, charger original lengkap, port aman",
        "ai_conclusion": "Dengan kondisi fisik yang mulus sekali tanpa goresan dan status 'Bagus', laptop ini sangat menjanjikan.",
        "created_at": "2026-05-20T03:32:25.000000Z",
        "updated_at": "2026-05-20T03:32:25.000000Z"
    }
}
```

### Hapus Data Penilaian
Digunakan untuk menghapus riwayat penilaian berdasarkan ID.

**Endpoint:** `DELETE /api/assessments/{id}`

**Response (Success):**
```json
{
    "status": "success",
    "message": "Data penilaian berhasil dihapus."
}
```

## 5. Konfigurasi Lingkungan (Environment Variables)

Pastikan variabel berikut diatur di file `.env` untuk integrasi microservice dan Google AI:

```env
# URL Layanan Perhitungan Fuzzy (Microservice di Docker)
FUZZY_SERVICE_URL=http://fuzzy

# Kunci API untuk Agen Google Gemini AI
GEMINI_API_KEY=AIzaSy...
```

Konfigurasi ini dimuat melalui file `config/services.php`.

## 6. Integrasi Docker & Troubleshooting

### Port Mapping (Docker Environment)
Seluruh sistem dijalankan melalui `docker-compose`.

| Container | Port (Host) | Deskripsi |
|-----------|-------------|-----------|
| **Backend-Service** | 8000 | Core API (Laravel), melayani endpoint aplikasi utama. |
| **Evaluator-Service** | 8001 | Fuzzy Microservice, menangani matematis dan rules fuzzy. |
| **mysql-database** | 3307 | Basis data MySQL utama. |
| **FrontendService** | 5173 | Vue.js UI dijalankan via node lokal (`npm run dev`). |

### Masalah Umum & Cara Debugging
1. **"Fuzzy Service Error: Invalid JSON response...":** 
   - Ini biasanya terjadi jika ada kesalahan konfigurasi web server (Apache 403 Forbidden) di Evaluator-Service atau karakter nyasar di kode PHP.
   - Cek konfigurasi container dengan `docker compose up -d` atau cek logs dengan `docker logs Evaluator-Service`.
2. **AI Selalu Menampilkan "(Simulasi AI)":**
   - Periksa file `.env`. Pastikan penulisan kunci `GEMINI_API_KEY` benar (jangan sampai ada tanda sama dengan ganda `==`).
   - Prefix resmi Google API Key yang sah selalu berawalan huruf kapital **`AIzaSy`**.
   - Cek file log backend dengan perintah `docker exec Backend-Service tail -n 50 /var/www/html/storage/logs/laravel.log`. Anda akan melihat respon error JSON asli dari Google di log (mis. status 400 Invalid Argument).

---
*Dokumentasi ini diperbarui pada 20 Mei 2026 dengan perbaikan arsitektur Docker, penambahan field deskripsi, dan fitur Agen Gemini AI.*
