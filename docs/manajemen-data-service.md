# Dokumentasi Sistem: Manajemen Data Service (Core)

Sistem **Manajemen Data Service** adalah layanan inti dalam ekosistem *Sistem Penilaian Kelayakan Laptop Bekas*. Layanan ini bertanggung jawab untuk mengelola data master aturan fuzzy, menyimpan hasil penilaian, dan berintegrasi dengan layanan perhitungan fuzzy eksternal.

## 1. Ikhtisar Arsitektur

Sistem ini dirancang menggunakan pendekatan *Service-Oriented Architecture* (SOA). 

- **Core Service (Layanan Ini)**: Menyimpan data penilaian dan konfigurasi aturan.
- **Fuzzy Service (Eksternal)**: Melakukan perhitungan logika fuzzy (fuzzifikasi, inferensi, defuzzifikasi).

### Alur Kerja (Workflow)
1. User mengirim data kondisi laptop melalui API.
2. Core Service mengambil aturan fuzzy terbaru dari database.
3. Core Service mengirim data input dan aturan ke **Fuzzy Service**.
4. Fuzzy Service mengembalikan skor kelayakan dan status.
5. Core Service menyimpan hasil akhir dan merespon user.

## 2. Struktur Database

Sistem ini memiliki dua tabel utama untuk mendukung proses penilaian:

### a. `assessments`
Menyimpan riwayat penilaian laptop.

| Kolom | Tipe Data | Deskripsi |
|-------|-----------|-----------|
| `id` | BigInt (PK) | Identifier unik. |
| `laptop_name` | String | Nama/Model laptop yang dinilai. |
| `lcd_input` | Float | Nilai kondisi layar (input). |
| `battery_input` | Float | Nilai kesehatan baterai (input). |
| `ram_input` | Float | Kapasitas/Skor RAM (input). |
| `keyboard_input`| Float | Skor kondisi keyboard (input). |
| `final_score` | Float | Hasil perhitungan nilai kelayakan (0-100). |
| `status` | String | Status kelayakan (e.g., Layak, Tidak Layak). |

### b. `fuzzy_rules`
Menyimpan parameter kurva untuk variabel fuzzy.

| Kolom | Tipe Data | Deskripsi |
|-------|-----------|-----------|
| `variable` | String | Nama variabel (LCD, Baterai, dll). |
| `category` | String | Kategori (Rendah, Normal, Tinggi). |
| `curve_type` | String | Tipe kurva (Turun, Naik, Segitiga). |
| `parameters` | JSON | Parameter kurva (titik-titik koordinat). |

## 3. Komponen Utama

### `AssessmentController`
Terletak di `app/Http/Controllers/Api/AssessmentController.php`.
Menangani permintaan HTTP untuk pembuatan penilaian baru. Melakukan validasi input sebelum memanggil service integrasi.

### `FuzzyIntegrationService`
Terletak di `app/Services/External/FuzzyIntegrationService.php`.
Komponen ini adalah jembatan (bridge) ke Fuzzy Service.
- **Tugas**: Memformat data aturan dari database ke format JSON yang dimengerti oleh microservice eksternal.
- **Endpoint**: Mengirim permintaan ke `POST /api/penilaian`.

## 4. Dokumentasi API

### Simpan Penilaian Baru
Digunakan untuk menghitung kelayakan laptop dan menyimpannya ke database.

**Endpoint:** `POST /api/assessments`

**Request Body:**
```json
{
    "laptop_name": "ThinkPad X1 Carbon",
    "lcd": 90,
    "battery": 85,
    "ram": 8,
    "keyboard": 95
}
```

**Response (Success):**
```json
{
    "status": "success",
    "message": "Penilaian berhasil dihitung dan disimpan.",
    "result": {
        "id": 1,
        "laptop_name": "ThinkPad X1 Carbon",
        "lcd_input": 90,
        "battery_input": 85,
        "ram_input": 8,
        "keyboard_input": 95,
        "final_score": 88.5,
        "status": "Bagus",
        "created_at": "2026-05-12T02:27:55.000000Z",
        "updated_at": "2026-05-12T02:27:55.000000Z"
    }
}
```

## 5. Konfigurasi Lingkungan

Pastikan variabel berikut diatur di file `.env` untuk integrasi service:

```env
# URL Layanan Perhitungan Fuzzy (Default Port: 8000)
FUZZY_SERVICE_URL=http://127.0.0.1:8000
```

Konfigurasi ini dibaca melalui `config/services.php`:
```php
'fuzzy' => [
    'url' => env('FUZZY_SERVICE_URL', 'http://127.0.0.1:8000'),
],
```

## 6. Integrasi & Troubleshooting

### Port Mapping (Lokal)
| Service | Port | Command |
|---------|------|---------|
| **ManajemenDataService** (Core) | 8001 | `php artisan serve --port=8001` |
| **PenilaianService** (Fuzzy) | 8000 | `php artisan serve --port=8000` |
| **ClientApp** (Frontend) | 5173 | `npm run dev` |

### Masalah Umum
1. **"Fuzzy Service Error: ...":** 
   - Terjadi jika `PenilaianService` mengembalikan error internal (500). Cek log di `PenilaianService/storage/logs/laravel.log`.
   - Pastikan key kategori pada aturan fuzzy (e.g., `normal`) sudah sesuai antara database Core dan logika di Service Fuzzy.
2. **"Fuzzy Service Error: Tidak merespon":**
   - Periksa apakah `PenilaianService` sudah dijalankan di port 8000.
   - Pastikan URL di `.env` Core Service sudah benar.

---
*Dokumentasi ini diperbarui pada 12 Mei 2026 setelah integrasi berhasil.*
