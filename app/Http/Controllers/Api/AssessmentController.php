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

class AssessmentController extends Controller
{
    public function __construct(
        private EvaluatorService $evaluatorService,
        private AgentAIService $aiService
    ) {}

    public function index()
    {
        // Menyertakan relasi 'images' agar frontend bisa membaca daftar gambar
        $assessments = Assessment::with(['processor', 'images'])->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'data' => $assessments
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
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
                    'benchmark_score' => $score,
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

            // 4. Dapatkan Kesimpulan Naratif dari AI Service
            $aiConclusion = $this->aiService->getConclusion(
                $request->laptop_name,
                $score,
                $status,
                $request->description,
                $request->lcd,
                $request->keyboard,
                $request->ram,
                $request->battery,
                $processor->name,
                $processor->benchmark_score
            );

            // 5. Simpan Data Evaluasi Utama Terlebih Dahulu
            $assessment = Assessment::create([
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

            return response()->json([
                'status'  => 'success',
                'message' => 'Penilaian dan gambar berhasil disimpan.',
                'data'    => $assessment->load(['processor', 'images'])
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