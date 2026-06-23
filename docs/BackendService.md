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

1. User mengirim data kondisi laptop (LCD, Keyboard, RAM, Baterai, Processor) + deskripsi + harga pasar melalui API.
2. BackendService mengambil parameter fuzzifikasi & defuzzifikasi dari tabel `fuzzy_configs`, 243 aturan dari `fuzzy_rules`, dan threshold dari `fuzzy_thresholds`.
3. BackendService mengirim input kondisi + rules + threshold ke **EvaluatorService** (`POST /api/evaluator`).
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
│   ├── FuzzyConfig.php              # Model konfigurasi fuzzifikasi & defuzzifikasi
│   ├── FuzzyRule.php                # Model aturan inferensi fuzzy (243 rules)
│   ├── FuzzyThreshold.php           # Model threshold batas kelayakan dinamis
│   └── User.php                     # Model user (default Laravel)
├── Providers/
│   └── AppServiceProvider.php
└── Services/External/
    └── EvaluatorService.php         # HTTP client ke EvaluatorService
database/
├── migrations/ (15 file)
└── seeders/
    ├── DatabaseSeeder.php
    ├── FuzzyConfigSeeder.php         # Data kurva fuzzifikasi & defuzzifikasi
    ├── FuzzyRuleSeeder.php           # 243 aturan inferensi fuzzy
    └── FuzzyThresholdSeeder.php      # Threshold batas kelayakan dinamis
routes/
└── api.php                          # 4 endpoint assessment
```

## 4. Database & Migrations

### 4.1 Tabel `assessments`

Menyimpan riwayat penilaian laptop beserta hasil perhitungan dan rekomendasi AI.

| Kolom | Tipe Data | Keterangan |
|-------|-----------|-----------|
| `id` | bigint (PK) | Auto increment |
| `customer_name` | string | Nama customer/pemilik laptop |
| `laptop_name` | string | Nama/model laptop yang dinilai |
| `lcd_input` | float | Kondisi LCD (0–100) |
| `battery_input` | float | Kesehatan baterai (0–100) |
| `processor_input` | float | Skor benchmark processor |
| `keyboard_input` | float | Kondisi keyboard (0–100) |
| `ram_input` | float | Kapasitas RAM (GB) |
| `final_score` | float | Hasil perhitungan nilai kelayakan (0–100) |
| `status` | string | Label: "Tidak Layak" / "Cukup Layak" / "Layak" |
| `market_price` | bigint | Harga pasar (input user) |
| `estimated_price` | bigint | `floor(market_price × (final_score / 100))` |
| `description` | text | Deskripsi kondisi fisik tambahan (opsional) |
| `ai_conclusion` | text | Rekomendasi naratif dari Gemini AI |
| `processor_id` | bigint (FK) | ID processor dari tabel `processors` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Migration History

1. **`2026_05_12_014303_create_fuzzy_rules.php`** — Membuat tabel `fuzzy_rules` (awal, kemudian di-rename jadi `fuzzy_configs`).
2. **`2026_05_12_014343_create_assessments.php`** — Membuat tabel `assessments` dengan kolom awal: `lcd_input`, `battery_input`, `ram_input`, `keyboard_input`.
3. **`2026_05_20_023450_add_ai_columns_to_assessments_table.php`** — Menambah kolom `description` dan `ai_conclusion`.
4. **`2026_05_22_100500_rename_ram_input_to_processor_input_on_assessments_table.php`** — Mengganti nama kolom `ram_input` → `processor_input`.
5. **`2026_05_23_000000_add_price_columns_to_assessments_table.php`** — Menambah kolom `market_price` dan `estimated_price`.
6. **`2026_06_07_111717_rename_fuzzy_rules_to_fuzzy_configs_table.php`** — Mengganti nama tabel `fuzzy_rules` menjadi `fuzzy_configs`.
7. **`2026_06_07_111745_create_fuzzy_rules_table.php`** — Membuat tabel `fuzzy_rules` baru untuk matriks 243 aturan inferensi.
8. **`2026_06_21_000001_create_fuzzy_thresholds_table.php`** — Membuat tabel `fuzzy_thresholds` untuk batas kelayakan dinamis.

### 4.2 Tabel `fuzzy_configs`

Menyimpan parameter kurva fungsi keanggotaan *fuzzifikasi* (5 variabel input) dan *defuzzifikasi* (variabel Kelayakan). Digunakan oleh BackendService untuk dikirim ke EvaluatorService.

| Kolom | Tipe Data | Keterangan |
|-------|-----------|-----------|
| `id` | bigint (PK) | Auto increment |
| `variable` | string | Nama variabel: `LCD`, `KesehatanBaterai`, `Processor`, `KondisiKeyboard`, `RAM`, `Kelayakan` |
| `category` | string | Kategori: `buruk`/`rendah`, `sedang`, `baik`/`tinggi`, `tidak_layak`, `cukup_layak`, `layak` |
| `curve_type` | string | Tipe kurva: `trapesium`, `segitiga` |
| `parameters` | json | Array parameter kurva [a, b, c, d] (4 angka trapesium, 3 untuk segitiga) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Seed Data (FuzzyConfigSeeder) — Fuzzifikasi

| Variable | Category | Curve | Parameters |
|---|---|---|---|
| LCD | buruk | trapesium | [0, 0, 55, 65] |
| LCD | sedang | trapesium | [55, 65, 75, 85] |
| LCD | baik | trapesium | [75, 85, 100, 100] |
| KondisiKeyboard | buruk | trapesium | [0, 0, 55, 65] |
| KondisiKeyboard | sedang | trapesium | [55, 65, 75, 85] |
| KondisiKeyboard | baik | trapesium | [75, 85, 100, 100] |
| RAM | rendah | trapesium | [4, 4, 6, 8] |
| RAM | sedang | segitiga | [6, 8, 12] |
| RAM | tinggi | trapesium | [8, 12, 64, 64] |
| KesehatanBaterai | rendah | trapesium | [0, 0, 60, 70] |
| KesehatanBaterai | sedang | segitiga | [60, 70, 85] |
| KesehatanBaterai | tinggi | trapesium | [70, 85, 100, 100] |
| Processor | rendah | trapesium | [0, 0, 8000, 10000] |
| Processor | sedang | trapesium | [8000, 10000, 18000, 20000] |
| Processor | tinggi | trapesium | [18000, 20000, 64946, 64946] |

#### Seed Data (FuzzyConfigSeeder) — Defuzzifikasi

| Variable | Category | Curve | Parameters |
|---|---|---|---|
| Kelayakan | tidak_layak | trapesium | [0, 0, 55, 65] |
| Kelayakan | cukup_layak | trapesium | [55, 65, 85, 90] |
| Kelayakan | layak | trapesium | [85, 90, 100, 100] |

### 4.3 Tabel `fuzzy_rules` (Matriks Inferensi)

Menyimpan 243 kombinasi aturan IF-THEN untuk inferensi Mamdani.

| Kolom | Tipe Data | Keterangan |
|-------|-----------|-----------|
| `id` | bigint (PK) | Auto increment |
| `lcd` | enum | `buruk`, `sedang`, `baik` |
| `keyboard` | enum | `buruk`, `sedang`, `baik` |
| `ram` | enum | `rendah`, `sedang`, `tinggi` |
| `baterai` | enum | `rendah`, `sedang`, `tinggi` |
| `processor` | enum | `rendah`, `sedang`, `tinggi` |
| `output` | enum | `tidak_layak`, `cukup_layak`, `layak` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 4.4 Tabel `fuzzy_thresholds`

Menyimpan batas status kelayakan yang dapat diubah secara dinamis tanpa deploy ulang.

| Kolom | Tipe Data | Keterangan |
|-------|-----------|-----------|
| `id` | bigint (PK) | Auto increment |
| `name` | string (unique) | Nama threshold: `tidak_layak_batas`, `layak_batas` |
| `value` | decimal(5,2) | Nilai batas (0–100) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Seed Data (FuzzyThresholdSeeder)

| Name | Value |
|------|-------|
| tidak_layak_batas | 65.00 |
| layak_batas | 85.00 |

## 5. Komponen Utama

### `AssessmentController`

**Lokasi:** `app/Http/Controllers/Api/AssessmentController.php`

Menangani seluruh permintaan HTTP untuk CRUD assessment. Method utama:

- **`index()`** — Menampilkan daftar assessment (paginated, 10 per halaman, urut `created_at` DESC). Mendukung filter `search`, `start_date`, dan `end_date` (konversi WIB → UTC).
- **`store(Request)`** — Membuat assessment baru. Melakukan validasi input, menentukan processor (dari ID atau buat baru), memanggil EvaluatorService untuk fuzzy logic, menghitung estimated_price, mendeteksi relevansi deskripsi, memanggil Gemini AI (jika use_ai=true dan deskripsi relevan), menyimpan gambar ke storage, dan menyimpan ke database.
- **`show($id)`** — Menampilkan detail satu assessment beserta relasi processor dan images.
- **`destroy($id)`** — Menghapus assessment beserta file gambar dari storage berdasarkan ID.

### `EvaluatorService`

**Lokasi:** `app/Services/External/EvaluatorService.php`

HTTP client ke EvaluatorService (microservice fuzzy). Method utama:

```php
$evaluatorService->evaluate(array $input): array
```

Alur internal:
1. `formatFuzzyConfigs()` — membaca kurva fuzzifikasi dari `fuzzy_configs` (5 variabel input)
2. `formatInferenceMatrix()` — membaca 243 aturan dari `fuzzy_rules`
3. `formatDefuzzifikasiConfigs()` — membaca kurva output dari `fuzzy_configs` (variable `Kelayakan`)
4. `formatThresholds()` — membaca threshold dari `fuzzy_thresholds`
5. HTTP POST ke EvaluatorService dengan payload `input` + `rules` (fuzzifikasi, matrix_aturan, defuzzifikasi, thresholds)

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
                "customer_name": "John Doe",
                "laptop_name": "Lenovo Legion 5 Pro",
                "lcd_input": 100,
                "battery_input": 80,
                "processor_input": 12000,
                "keyboard_input": 100,
                "ram_input": 16,
                "final_score": 84.42,
                "status": "Cukup Layak",
                "market_price": 8000000,
                "estimated_price": 6753600,
                "description": "Bodi mulus 98%",
                "ai_conclusion": "LCD dalam kondisi baik, keyboard berfungsi normal...",
                "processor_id": 1,
                "processor": { "id": 1, "name": "Intel Core i7-12700H", "benchmark_score": 12000, "category": "Tinggi" },
                "images": [],
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
**Content-Type:** `application/json` atau `multipart/form-data`

**Request Body (JSON):**

```json
{
    "customer_name": "John Doe",
    "laptop_name": "Lenovo Legion 5 Pro",
    "lcd": 100,
    "battery": 80,
    "keyboard": 100,
    "ram": 16,
    "market_price": 8000000,
    "processor_id": 1,
    "description": "Bodi mulus 98%, charger original",
    "use_ai": false
}
```

**Atau dengan processor baru (tanpa processor_id):**

```json
{
    "customer_name": "John Doe",
    "laptop_name": "Lenovo Legion 5 Pro",
    "lcd": 90,
    "battery": 85,
    "keyboard": 95,
    "ram": 8,
    "market_price": 5000000,
    "processor_name": "AMD Ryzen 5 5600H",
    "processor_input": 14000,
    "use_ai": true
}
```

**Aturan Validasi:**
| Field | Aturan |
|-------|--------|
| `customer_name` | required, string, max 255 |
| `laptop_name` | required, string |
| `lcd` | required, integer, between 0–100 |
| `battery` | required, integer, between 0–100 |
| `keyboard` | required, integer, between 0–100 |
| `ram` | required, numeric, min 0 |
| `market_price` | required, integer, min 0 |
| `processor_id` | nullable, exists:processors,id |
| `processor_name` | required_without:processor_id, string, max 255 |
| `processor_input` | required_without:processor_id, numeric, min 0 |
| `description` | nullable, string |
| `use_ai` | nullable, boolean |
| `images` | nullable, array, max 3 |
| `images.*` | image, mimes:jpeg,png,jpg, max:2048 |

**Response (201):**

```json
{
    "status": "success",
    "message": "Penilaian dan gambar berhasil disimpan.",
    "data": {
        "id": 1,
        "customer_name": "John Doe",
        "laptop_name": "Lenovo Legion 5 Pro",
        "lcd_input": 100,
        "battery_input": 80,
        "processor_input": 12000,
        "keyboard_input": 100,
        "ram_input": 16,
        "final_score": 84.42,
        "status": "Cukup Layak",
        "market_price": 8000000,
        "estimated_price": 6753600,
        "description": "Bodi mulus 98%, charger original",
        "ai_conclusion": "LCD dalam kondisi baik, keyboard berfungsi normal...",
        "processor_id": 1,
        "processor": { "id": 1, "name": "Intel Core i7-12700H", "benchmark_score": 12000, "category": "Tinggi" },
        "images": [],
        "description_ignored": false,
        "ai_used": false,
        "ai_warning": null,
        "created_at": "2026-05-23T10:00:00.000000Z",
        "updated_at": "2026-05-23T10:00:00.000000Z"
    }
}
```

**Error (422 — Validasi Laravel):**

```json
{
    "message": "lcd harus diisi.",
    "errors": {
        "lcd": ["lcd harus diisi."]
    }
}
```

**Error (500 — Internal Server Error):**

```json
{
    "status": "error",
    "message": "Gagal memproses penilaian: [detail error]"
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
        "customer_name": "John Doe",
        "laptop_name": "Lenovo Legion 5 Pro",
        "lcd_input": 100,
        "battery_input": 80,
        "processor_input": 12000,
        "keyboard_input": 100,
        "ram_input": 16,
        "final_score": 84.42,
        "status": "Cukup Layak",
        "market_price": 8000000,
        "estimated_price": 6753600,
        "description": "Bodi mulus 98%",
        "ai_conclusion": "LCD dalam kondisi baik, keyboard berfungsi normal...",
        "processor": { "id": 1, "name": "Intel Core i7-12700H", "benchmark_score": 12000, "category": "Tinggi" },
        "images": [],
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
        "KondisiKeyboard": 100,
        "RAM": 16
    },
    "rules": {
        "fuzzifikasi": {
            "LCD": {
                "buruk": [0, 0, 55, 65],
                "sedang": [55, 65, 75, 85],
                "baik": [75, 85, 100, 100]
            },
            "KondisiKeyboard": {
                "buruk": [0, 0, 55, 65],
                "sedang": [55, 65, 75, 85],
                "baik": [75, 85, 100, 100]
            },
            "RAM": {
                "rendah": [4, 4, 6, 8],
                "sedang": [6, 8, 12],
                "tinggi": [8, 12, 64, 64]
            },
            "KesehatanBaterai": {
                "rendah": [0, 0, 60, 70],
                "sedang": [60, 70, 85],
                "tinggi": [70, 85, 100, 100]
            },
            "Processor": {
                "rendah": [0, 0, 8000, 10000],
                "sedang": [8000, 10000, 18000, 20000],
                "tinggi": [18000, 20000, 64946, 64946]
            }
        },
        "matrix_aturan": [
            { "lcd": "buruk", "keyboard": "buruk", "ram": "rendah", "baterai": "rendah", "processor": "rendah", "output": "tidak_layak" },
            { "lcd": "baik", "keyboard": "baik", "ram": "tinggi", "baterai": "tinggi", "processor": "tinggi", "output": "layak" }
        ],
        "defuzzifikasi": {
            "tidak_layak": [0, 0, 55, 65],
            "cukup_layak": [55, 65, 85, 90],
            "layak": [85, 90, 100, 100]
        },
        "thresholds": {
            "tidak_layak_batas": 65.00,
            "layak_batas": 85.00
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
        "inferensi": {
            "tidak_layak": 0,
            "cukup_layak": 0.5,
            "layak": 0.8
        },
        "nilaiKelayakan": 84.42,
        "statusKelayakan": "Cukup Layak"
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

## 10. CORS Configuration

BackendService mengizinkan akses dari origin mana pun untuk mendukung integrasi eksternal.

**Konfigurasi (`config/cors.php`):**
```php
'paths'            => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods'  => ['*'],
'allowed_origins'  => ['*'],     // Izinkan semua origin
'allowed_headers'  => ['*'],     // Izinkan semua header
```

**Catatan:**
- Middleware `HandleCors` terdaftar secara otomatis oleh Laravel 12 sebagai global middleware — tidak perlu registrasi manual.
- Semua response API menyertakan header `Access-Control-Allow-Origin: *`.
- Preflight `OPTIONS` request ditangani dengan benar (response 204 + CORS headers).

---

## 12. Integrasi Docker & Troubleshooting

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

**4. Request dari klien eksternal gagal dengan "Failed to fetch"**
- CORS sudah dikonfigurasi (`Access-Control-Allow-Origin: *`). Cek apakah service berjalan: `curl http://localhost:8000/api/assessments`.
- Jika menggunakan `file://` protocol, coba jalankan HTML via web server: `python3 -m http.server` atau `npx serve`.
- Pastikan nama field API benar (`lcd`, `battery`, `keyboard`, `ram`, `battery` — bukan `lcd_input`, dll).

---

*Dokumentasi ini diperbarui pada 21 Juni 2026.*
