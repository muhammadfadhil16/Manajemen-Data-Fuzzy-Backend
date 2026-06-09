<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\External\EvaluatorService;
use App\Models\Assessment;
use App\Models\Processor;
use App\Models\AssessmentImage; 
use App\Services\AI\AgentAIService;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AssessmentController extends Controller
{
    public function __construct(
        private EvaluatorService $evaluatorService,
        private AgentAIService $aiService
    ) {}

    public function index(Request $request)
    {
        $query = Assessment::with(['processor', 'images']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('laptop_name', 'like', "%{$search}%")
                  ->orWhere('id', (int) $search);
            });
        }

        if ($request->filled('start_date')) {
            $startUtc = Carbon::parse($request->start_date, 'Asia/Jakarta')
                ->startOfDay()
                ->setTimezone('UTC');
            $query->where('created_at', '>=', $startUtc);
        }

        if ($request->filled('end_date')) {
            $endUtc = Carbon::parse($request->end_date, 'Asia/Jakarta')
                ->endOfDay()
                ->setTimezone('UTC');
            $query->where('created_at', '<=', $endUtc);
        }

        $assessments = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $assessments
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name'  => 'required|string|max:255',
            'laptop_name'    => 'required|string',
            'images'         => 'nullable|array|max:3',
            'images.*'       => 'image|mimes:jpeg,png,jpg|max:2048',
            'lcd'            => 'required|numeric|between:0,100',
            'battery'        => 'required|numeric|between:0,100',
            'processor_id'   => 'nullable|exists:processors,id',
            'processor_name' => 'required_without:processor_id|string|max:255',
            'processor_input'=> 'required_without:processor_id|numeric|min:0',
            'keyboard'       => 'required|numeric|between:0,100',
            'ram'            => 'required|numeric|min:0',
            'market_price'   => 'required|integer|min:0',
            'description'    => 'nullable|string',
        ]);

        try {
            // 1. Tentukan Processor (dari ID atau buat baru)
            if ($request->processor_id) {
                $processor = Processor::findOrFail($request->processor_id);
            } else {
                $score = (int) $request->processor_input;
                $category = match(true) {
                    $score <= 7999    => 'Rendah',
                    $score <= 18000   => 'Sedang',
                    default           => 'Tinggi',
                };
                $processor = Processor::create([
                    'name'            => $request->processor_name,
                    'benchmark_scorre' => $score,
                    'category'        => $category,
                ]);
            }

            $input = [
                'LCD'              => $request->lcd,
                'KesehatanBaterai' => $request->battery,
                'Processor'        => $processor->benchmark_score,
                'KondisiKeyboard'  => $request->keyboard,
                'RAM'              => $request->ram,
            ];

            // 2. Panggil Service Evaluator (Fuzzy Engine)
            $evaluationResult = $this->evaluatorService->evaluate($input);
            $score            = $evaluationResult['nilaiKelayakan'];
            $status           = $evaluationResult['statusKelayakan'];

            // 3. Hitung Harga Estimasi (Depresiasi Berbasis Skor)
            $estimatedPrice = (int) floor($request->market_price * ($score / 100));

            // 4. Deteksi deskripsi tidak relevan (tidak mengandung kata terkait laptop)
            $descriptionIgnored = false;
            $descriptionForAi = $request->description;
            if (!empty(trim($request->description ?? ''))) {
                $lower = strtolower($request->description);
                $laptopKeywords = [
                    'laptop', 'keyboard', 'baterai', 'battery', 'lcd', 'layar',
                    'ram', 'processor', 'cpu', 'hardisk', 'ssd', 'charge',
                    'bodi', 'casing', 'port', 'usb', 'fan', 'kipas',
                    'key', 'touchpad', 'trackpad', 'webcam', 'speaker',
                    'windows', 'linux', 'macos', 'bios', 'os',
                    'lecet', 'baret', 'penyok', 'retak', 'rusak',
                    'mulus', 'normal', 'berfungsi', 'menyala',
                    'upgrade', 'servis', 'service', 'perbaiki', 'ganti',
                    'harga', 'beli', 'jual', 'second', 'bekas',
                ];
                $hasLaptopContext = false;
                foreach ($laptopKeywords as $keyword) {
                    if (str_contains($lower, $keyword)) {
                        $hasLaptopContext = true;
                        break;
                    }
                }
                if (!$hasLaptopContext) {
                    $descriptionIgnored = true;
                    $descriptionForAi = null;
                }
            }

            // 5. Dapatkan Kesimpulan Naratif dari AI Service
            try {
                $aiConclusion = $this->aiService->getConclusion(
                    $request->laptop_name,
                    $score,
                    $status,
                    $descriptionForAi,
                    $request->lcd,
                    $request->keyboard,
                    $request->ram,
                    $request->battery,
                    $processor->name,
                    $processor->benchmark_score
                );
            } catch (\Exception $e) {
                \Log::error('AI Service error: ' . $e->getMessage());
                $aiConclusion = 'tidak ada catatan tambahan';
            }

            // 5. Simpan Data Evaluasi Utama Terlebih Dahulu
            $assessment = Assessment::create([
                'customer_name'   => $request->customer_name,
                'laptop_name'     => $request->laptop_name,
                'lcd_input'       => $request->lcd,
                'battery_input'   => $request->battery,
                'processor_input' => $processor->benchmark_score,
                'keyboard_input'  => $request->keyboard,
                'ram_input'       => $request->ram,
                'processor_id'    => $processor->id,
                'final_score'     => $score,
                'status'          => $status,
                'market_price'    => $request->market_price,
                'estimated_price' => $estimatedPrice,
                'description'     => $request->description,
                'ai_conclusion'   => $aiConclusion,
            ]);

            // 6. Alur Baru: Simpan Berkas Gambar ke Storage dan Insert ke Tabel assessment_images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    // Simpan file ke storage/app/public/evaluations
                    $path = $image->store('evaluations', 'public');
                    
                    // Masukkan ke tabel assessment_images lewat relasi Eloquent
                    $assessment->images()->create([
                        'image_path' => $path
                    ]);
                }
            }

            $data = $assessment->load(['processor', 'images'])->toArray();
            $data['description_ignored'] = $descriptionIgnored;

            return response()->json([
                'status'  => 'success',
                'message' => 'Penilaian dan gambar berhasil disimpan.',
                'data'    => $data
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error', 
                'message' => 'Gagal memproses penilaian: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        // Sertakan juga relasi 'images' di fungsi detail (show)
        $assessment = Assessment::with(['processor', 'images'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $assessment
        ]);
    }

    public function destroy($id)
    {
        $assessment = Assessment::with('images')->findOrFail($id);

        $images = $assessment->images ?? collect();
        foreach ($images as $image) {
            try {
                $path = $image->getRawOriginal('image_path');
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to delete image file: ' . $e->getMessage());
            }
        }

        $assessment->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data penilaian beserta file gambar berhasil dihapus.'
        ]);
    }
}