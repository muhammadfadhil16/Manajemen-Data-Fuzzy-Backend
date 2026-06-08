# Dokumentasi Sistem: BackendService (Core API)

**BackendService** adalah API gateway utama dalam ekosistem *Sistem Penilaian Kelayakan Laptop Bekas*. Bertugas menerima input penilaian dari Frontend, berkomunikasi dengan **EvaluatorService** untuk perhitungan fuzzy, menangani logika Gemini AI untuk rekomendasi, dan mengelola seluruh data persistif (assessment, fuzzy rules) di database MySQL.

## 1. Ikhtisar Arsitektur

Sistem dirancang dengan pendekatan *Service-Oriented Architecture* (SOA) yang di-deploy menggunakan ekosistem **Docker**.

```
Frontend (Vue 3)
    ↓ HTTP (8000)
BackendService ←→ MySQL (db:3306)
    ↓ HTTP (8001)
EvaluatorService (Fuzzy Engine)
```

### Alur Kerja (Workflow)

1. User mengirim data kondisi laptop + deskripsi + harga pasar melalui API.
2. BackendService mengambil aturan fuzzy terbaru dari tabel `fuzzy_rules`.
3. BackendService mengirim input kondisi (LCD, Baterai, Processor, Keyboard) + rules ke **EvaluatorService** (`POST /api/evaluator`).
4. EvaluatorService mengembalikan skor kelayakan, status, dan detail fuzzifikasi/inferensi.
5. BackendService menghitung **estimated_price** = `floor(market_price × (final_score / 100))`.
6. Jika `description` diisi, BackendService memanggil **Gemini AI** untuk rekomendasi naratif (fallback ke teks default jika gagal).
7. Hasil akhir (skor, status, harga estimasi, kesimpulan AI) disimpan ke tabel `assessments` dan dikembalikan ke Frontend.

## 2. Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| Framework | Laravel 12 |
| PHP Version | 8.x |
| Database | MySQL (via Docker, host: `db:3306`) |
| HTTP Client | Laravel Http Facade (Guzzle) |
| AI Service | Google Gemini AI (`gemini-2.5-flash`) |
| Container | Docker + docker-compose |

## 3. Struktur Folder

```
app/
├── Http/Controllers/Api/
│   ├── AssessmentController.php    # CRUD assessment + orchestrator
│   └── Controller.php              # Base controller
├── Models/
│   ├── Assessment.php               # Model penilaian
│   ├── FuzzyRule.php                # Model aturan fuzzy
│   └── User.php                     # Model user (default Laravel)
├── Providers/
│   └── AppServiceProvider.php
└── Services/External/
    └── EvaluatorService.php         # HTTP client ke EvaluatorService
database/
├── migrations/ (5 file)
└── seeders/
    ├── DatabaseSeeder.php
    └── FuzzyRuleSeeder.php          # Data awal aturan fuzzy
routes/
└── api.php                          # 4 endpoint assessment
```

## 4. Database & Migrations

### 4.1 Tabel `assessments`

Menyimpan riwayat penilaian laptop beserta hasil perhitungan dan rekomendasi AI.

| Kolom | Tipe Data | Keterangan |
|-------|-----------|-----------|
| `id` | bigint (PK) | Auto increment |
| `laptop_name` | string | Nama/model laptop yang dinilai |
| `lcd_input` | float | Kondisi LCD (0–100) |
| `battery_input` | float | Kesehatan baterai (0–100) |
| `processor_input` | float | Skor benchmark processor |
| `keyboard_input` | float | Kondisi keyboard (0–100) |
| `final_score` | float | Hasil perhitungan nilai kelayakan (0–100) |
| `status` | string | Label: "Tidak Bagus" / "Normal" / "Bagus" |
| `market_price` | bigint | Harga pasar (input user) |
| `estimated_price` | bigint | `floor(market_price × (final_score / 100))` |
| `description` | text | Deskripsi kondisi fisik tambahan (opsional) |
| `ai_conclusion` | text | Rekomendasi naratif dari Gemini AI |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Migration History

1. **`2026_05_12_014303_create_fuzzy_rules.php`** — Membuat tabel `fuzzy_rules`.
2. **`2026_05_12_014343_create_assessments.php`** — Membuat tabel `assessments` dengan kolom awal: `lcd_input`, `battery_input`, `ram_input`, `keyboard_input`.
3. **`2026_05_20_023450_add_ai_columns_to_assessments_table.php`** — Menambah kolom `description` dan `ai_conclusion`.
4. **`2026_05_22_100500_rename_ram_input_to_processor_input_on_assessments_table.php`** — Mengganti nama kolom `ram_input` → `processor_input`.
5. **`2026_05_23_000000_add_price_columns_to_assessments_table.php`** — Menambah kolom `market_price` dan `estimated_price`.

### 4.2 Tabel `fuzzy_rules`

Menyimpan parameter kurva untuk setiap variabel fuzzy. Digunakan oleh BackendService untuk dikirim ke EvaluatorService.

| Kolom | Tipe Data | Keterangan |
|-------|-----------|-----------|
| `id` | bigint (PK) | Auto increment |
| `variable` | string | Nama variabel: `LCD`, `KesehatanBaterai`, `Processor`, `KondisiKeyboard` |
| `category` | string | Kategori: `rendah`, `normal`, `tinggi` |
| `curve_type` | string | Tipe kurva: `turun`, `segitiga`, `naik` |
| `parameters` | json | Array parameter kurva (2 angka untuk turun/naik, 3 untuk segitiga) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Seed Data (FuzzyRuleSeeder)

| Variable | Category | Curve | Parameters |
|---|---|---|---|
| LCD | rendah | turun | [40, 60] |
| LCD | normal | segitiga | [40, 60, 80] |
| LCD | tinggi | naik | [60, 80] |
| KesehatanBaterai | rendah | turun | [30, 50] |
| KesehatanBaterai | normal | segitiga | [30, 60, 85] |
| KesehatanBaterai | tinggi | naik | [70, 90] |
| Processor | rendah | turun | [500, 10000] |
| Processor | normal | segitiga | [500, 10000, 15000] |
| Processor | tinggi | naik | [10000, 15000] |
| KondisiKeyboard | rendah | turun | [40, 70] |
| KondisiKeyboard | normal | segitiga | [40, 70, 90] |
| KondisiKeyboard | tinggi | naik | [70, 90] |

## 5. Komponen Utama

### `AssessmentController`

**Lokasi:** `app/Http/Controllers/Api/AssessmentController.php`

Menangani seluruh permintaan HTTP untuk CRUD assessment. Method utama:

- **`index()`** — Menampilkan daftar assessment (paginated, 10 per halaman, urut `created_at` ASC).
- **`store(Request)`** — Membuat assessment baru. Melakukan validasi, mengambil fuzzy rules, memanggil EvaluatorService, menghitung estimated_price, memanggil Gemini AI (jika ada description), dan menyimpan ke database.
- **`show($id)`** — Menampilkan detail satu assessment.
- **`destroy($id)`** — Menghapus assessment berdasarkan ID.

### `EvaluatorService`

**Lokasi:** `app/Services/External/EvaluatorService.php`

HTTP client ke EvaluatorService (microservice fuzzy). Method utama:

```php
$evaluatorService->evaluate(array $input, array $rules): array
```

- **Source config:** `config('services.evaluator.url', 'http://evaluator')`
- **Endpoint tujuan:** `{baseUrl}/api/evaluator`
- **Method:** HTTP POST dengan JSON body berisi `input` + `rules`.

### Gemini AI Integration

**Lokasi:** Method `store()` di `AssessmentController.php`

- **Model:** `gemini-2.5-flash`
- **Dipanggil** hanya jika `$request->filled('description')`.
- **Prompt:** Mengirim skor, status, dan deskripsi untuk mendapat rekomendasi naratif.
- **Fallback:** Jika gagal (timeout/error), `ai_conclusion` diisi `"tidak ada catatan tambahan"`.

## 6. Dokumentasi API

### 6.1 Daftar Penilaian (Index)

Menampilkan seluruh riwayat penilaian dengan pagination.

**Endpoint:** `GET /api/assessments`

**Response (200):**

```json
{
    "status": "success",
    "data": {
        "data": [
            {
                "id": 1,
                "laptop_name": "Lenovo Legion 5 Pro",
                "lcd_input": 100,
                "battery_input": 80,
                "processor_input": 12000,
                "keyboard_input": 100,
                "final_score": 84.42,
                "status": "Bagus",
                "market_price": 8000000,
                "estimated_price": 6753600,
                "description": "Bodi mulus 98%",
                "ai_conclusion": "Laptop ini sangat layak dibeli...",
                "created_at": "2026-05-23T10:00:00.000000Z",
                "updated_at": "2026-05-23T10:00:00.000000Z"
            }
        ],
        "current_page": 1,
        "last_page": 3,
        "per_page": 10,
        "total": 25
    }
}
```

### 6.2 Simpan Penilaian Baru (Store)

Menghitung kelayakan, menghasilkan estimasi harga + rekomendasi AI, dan menyimpannya ke database.

**Endpoint:** `POST /api/assessments`

**Request Body:**

```json
{
    "laptop_name": "Lenovo Legion 5 Pro",
    "lcd": 100,
    "battery": 80,
    "processor": 12000,
    "keyboard": 100,
    "market_price": 8000000,
    "description": "Bodi mulus 98%, charger original"
}
```

**Aturan Validasi:**
- `laptop_name` — required, string, max 255 karakter
- `lcd` — required, numeric, between 0–100
- `battery` — required, numeric, between 0–100
- `processor` — required, numeric
- `keyboard` — required, numeric, between 0–100
- `market_price` — required, numeric, min 0
- `description` — optional, string, nullable

**Response (201):**

```json
{
    "status": "success",
    "message": "Penilaian berhasil disimpan",
    "data": {
        "id": 1,
        "laptop_name": "Lenovo Legion 5 Pro",
        "final_score": 84.42,
        "status": "Bagus",
        "market_price": 8000000,
        "estimated_price": 6753600,
        "ai_conclusion": "Dengan skor 84.42 (Bagus), laptop ini sangat layak dipertimbangkan. Kondisi fisik yang disebutkan ('Bodi mulus 98%, charger original') menambah nilai jual. Harga estimasi Rp6.753.600 dari harga pasar Rp8.000.000 menunjukkan nilai yang kompetitif.",
        "description": "Bodi mulus 98%, charger original",
        "created_at": "2026-05-23T10:00:00.000000Z",
        "updated_at": "2026-05-23T10:00:00.000000Z"
    }
}
```

**Error (422 — Validasi):**

```json
{
    "status": "error",
    "message": "LCD harus diisi (0-100)."
}
```

**Error (500 — EvaluatorService Error):**

```json
{
    "error": "Evaluator Service Error (500): Connection refused"
}
```

### 6.3 Detail Penilaian (Show)

Menampilkan satu assessment berdasarkan ID.

**Endpoint:** `GET /api/assessments/{id}`

**Response (200):**

```json
{
    "status": "success",
    "data": {
        "id": 1,
        "laptop_name": "Lenovo Legion 5 Pro",
        "lcd_input": 100,
        "battery_input": 80,
        "processor_input": 12000,
        "keyboard_input": 100,
        "final_score": 84.42,
        "status": "Bagus",
        "market_price": 8000000,
        "estimated_price": 6753600,
        "description": "Bodi mulus 98%",
        "ai_conclusion": "Laptop ini sangat layak dibeli...",
        "created_at": "2026-05-23T10:00:00.000000Z",
        "updated_at": "2026-05-23T10:00:00.000000Z"
    }
}
```

### 6.4 Hapus Penilaian (Destroy)

Menghapus riwayat penilaian berdasarkan ID.

**Endpoint:** `DELETE /api/assessments/{id}`

**Response (200):**

```json
{
    "status": "success",
    "message": "Data penilaian berhasil dihapus."
}
```

## 7. Integrasi EvaluatorService

BackendService berkomunikasi dengan EvaluatorService melalui HTTP POST. Berikut format request yang dikirim:

**Endpoint:** `POST {EVALUATOR_SERVICE_URL}/api/evaluator`

**Payload yang dikirim:**

```json
{
    "input": {
        "LCD": 100,
        "KesehatanBaterai": 80,
        "Processor": 12000,
        "KondisiKeyboard": 100
    },
    "rules": {
        "fuzzifikasi": {
            "LCD": {
                "rendah": [40, 60],
                "normal": [40, 60, 80],
                "tinggi": [60, 80]
            },
            "KesehatanBaterai": {
                "rendah": [30, 50],
                "normal": [30, 60, 85],
                "tinggi": [70, 90]
            },
            "Processor": {
                "rendah": [500, 10000],
                "normal": [500, 10000, 15000],
                "tinggi": [10000, 15000]
            },
            "KondisiKeyboard": {
                "rendah": [40, 70],
                "normal": [40, 70, 90],
                "tinggi": [70, 90]
            }
        },
        "defuzzifikasi": {
            "centroid": {
                "tidak_layak": 30,
                "kurang_layak": 60,
                "layak": 90
            },
            "batas_status": {
                "tidak_bagus": 40,
                "normal": 65
            }
        }
    }
}
```

**Response yang diharapkan:**

```json
{
    "status": "success",
    "data": {
        "input": { ... },
        "fuzzifikasi": { ... },
        "inferensi": { ... },
        "nilaiKelayakan": 84.42,
        "statusKelayakan": "Bagus"
    }
}
```

## 8. Perhitungan Harga Estimasi

Setelah mendapatkan `final_score` dari EvaluatorService, BackendService menghitung:

```
estimated_price = floor(market_price × (final_score / 100))
```

**Contoh:**
- `market_price = Rp8.000.000`
- `final_score = 84.42`
- `estimated_price = floor(8.000.000 × 0.8442) = Rp6.753.600`

## 9. Testing

**File:** `tests/Feature/AssessmentTest.php`

Menggunakan trait `RefreshDatabase` + seeder `FuzzyRuleSeeder`.

| Test | Deskripsi |
|---|---|
| `test_can_list_assessments` | GET /api/assessments → 200, struktur JSON valid |
| `test_can_create_assessment_with_mocked_services` | POST dengan mock HTTP → 201 |
| `test_can_show_single_assessment` | GET /api/assessments/{id} → 200 |
| `test_can_delete_assessment` | DELETE /api/assessments/{id} → 200 |
| `test_store_assessment_validation` | Input invalid → 422 |

**Menjalankan test:**

```bash
php artisan test
```

## 10. Konfigurasi Lingkungan (Environment Variables)

| Variable | Default | Keterangan |
|---|---|---|
| `DB_CONNECTION` | `mysql` | Koneksi database |
| `DB_HOST` | `db` | Host MySQL (Docker) |
| `DB_PORT` | `3306` | Port MySQL |
| `DB_DATABASE` | `laravel` | Nama database |
| `DB_USERNAME` | `laravel` | User database |
| `DB_PASSWORD` | `laravel` | Password database |
| `EVALUATOR_SERVICE_URL` | `http://evaluator` | URL EvaluatorService |
| `GEMINI_API_KEY` | — | API key Google Gemini AI |

Konfigurasi dimuat melalui file `config/services.php`:

```php
'evaluator' => [
    'url' => env('EVALUATOR_SERVICE_URL', 'http://evaluator'),
],
'gemini' => [
    'key' => env('GEMINI_API_KEY'),
],
```

## 11. Integrasi Docker & Troubleshooting

### Port Mapping

| Container | Port (Host) | Keterangan |
|-----------|-------------|-----------|
| **BackendService** | 8000 | Core API (Laravel) |
| **EvaluatorService** | 8001 | Fuzzy Engine |
| **mysql-database** | 3307 | MySQL (host port) |
| **FrontendService** | 5173 | Vue.js UI |

### Masalah Umum

**1. "Evaluator Service Error: Invalid JSON response..."**
- Cek container EvaluatorService: `docker logs <evaluator-container>`
- Pastikan route `/api/evaluator` terdefinisi di `routes/api.php`

**2. "AI Selalu Menampilkan 'tidak ada catatan tambahan'"**
- Periksa `GEMINI_API_KEY` di `.env`. Pastikan formatnya benar (prefix `AIzaSy`).
- Cek log Laravel: `docker exec BackendService tail -50 /var/www/html/storage/logs/laravel.log`

**3. Koneksi database ditolak**
- Pastikan container MySQL sudah siap sebelum BackendService starting.
- Gunakan `depends_on` dengan `condition: service_healthy` di docker-compose.

---

*Dokumentasi ini diperbarui pada 23 Mei 2026.*
