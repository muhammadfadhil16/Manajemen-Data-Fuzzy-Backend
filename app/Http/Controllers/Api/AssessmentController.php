<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\External\EvaluatorService;
use App\Models\Assessment;
use Illuminate\Support\Facades\Http;

class AssessmentController extends Controller
{
    public function __construct(
        private EvaluatorService $evaluatorService
    ) {}

    public function index()
    {
        $assessments = Assessment::orderBy('created_at', 'asc')->paginate(10);
        return response()->json([
            'status' => 'success',
            'data' => $assessments
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'laptop_name' => 'required|string',
            'lcd' => 'required|numeric',
            'battery' => 'required|numeric',
            'ram' => 'required|numeric',
            'keyboard' => 'required|numeric',
        ]);

        try {
            $input = [
                'LCD' => $request->lcd,
                'KesehatanBaterai' => $request->battery,
                'RAM' => $request->ram,
                'KondisiKeyboard' => $request->keyboard,
            ];

            // 1. Panggil Service Integrasi (Microservices Call)
            $evaluationResult = $this->evaluatorService->evaluator($input);
            $score = $evaluationResult['nilaiKelayakan'];
            $aiConclusion = 'tidak ada catatan tambahan';

            if($request->filled('description')) {
                $prompt = "Tugas Anda adalah memberikan penjelasan naratif singkat. " .
                      "Nama Laptop: {$request->laptop_name}. " .
                      "Skor Kelayakan (Hasil Hitung Evaluator): {$score}/100. " .
                      "Status: {$evaluationResult['statusKelayakan']}. " .
                      "Deskripsi: '{$request->description}'. " .
                      "Berdasarkan skor dan deskripsi tersebut, berikan saran singkat 1-2 kalimat kepada calon pembeli.";

                $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . config('services.gemini.key'), [
                    'contents' => [['parts' => [['text' => $prompt]]]]
                ]);

                if ($response->successful()) {
                    $aiConclusion = $response->json()['candidates'][0]['content']['parts'][0]['text'];
                } else {
                    // \Illuminate\Support\Facades\Log::warning("Gemini API call failed. Status: " . $response->status() . " Body: " . $response->body());
                    
                    // Fallback to locally generated expert recommendation based on fuzzy status/score
                    $status = $evaluationResult['statusKelayakan'];
                    $rec = "";
                    if ($status === 'Bagus') {
                        $rec = "Laptop {$request->laptop_name} memiliki tingkat kelayakan yang sangat baik ({$score}/100). Berdasarkan kondisi fisik/deskripsi, laptop ini sangat direkomendasikan untuk dibeli karena semua komponen utama berfungsi prima.";
                    } elseif ($status === 'Cukup' || $status === 'Sedang') {
                        $rec = "Laptop {$request->laptop_name} berada dalam kondisi cukup layak ({$score}/100). Sebaiknya perhatikan beberapa bagian yang kurang optimal (seperti baterai atau LCD) sebelum memutuskan membeli, serta pertimbangkan harganya.";
                    } else {
                        $rec = "Laptop {$request->laptop_name} memiliki tingkat kelayakan rendah ({$score}/100) and berstatus Kurang Layak. Sangat disarankan untuk mencari alternatif lain atau melakukan perbaikan menyeluruh jika tetap ingin membeli.";
                    }
                    if ($request->description) {
                        $rec .= " Catatan tambahan fisik: \"" . $request->description . "\".";
                    }
                    $aiConclusion = $rec . " (Simulasi AI)";
                }
            }

            // 2. Simpan ke database Lokal (Core Service)
            $assessment = Assessment::create([
                'laptop_name' => $request->laptop_name,
                'lcd_input' => $request->lcd,
                'battery_input' => $request->battery,
                'ram_input' => $request->ram,
                'keyboard_input' => $request->keyboard,
                'final_score' => $evaluationResult['nilaiKelayakan'],
                'status' => $evaluationResult['statusKelayakan'],
                'description' => $request->description,
                'ai_conclusion' => $aiConclusion,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Penilaian berhasil dihitung dan disimpan.',
                'result' => $assessment
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $assessment = Assessment::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $assessment
        ]);
    }

    public function destroy($id)
    {
        $assessment = Assessment::findOrFail($id);
        $assessment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data penilaian berhasil dihapus.'
        ]);
    }
}