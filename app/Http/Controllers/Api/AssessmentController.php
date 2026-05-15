<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\External\FuzzyIntegrationService;
use App\Models\Assessment;

class AssessmentController extends Controller
{
    public function __construct(
        private FuzzyIntegrationService $fuzzyIntegration
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
            $fuzzyResult = $this->fuzzyIntegration->getAssessment($input);

            // 2. Simpan ke database Lokal (Core Service)
            $assessment = Assessment::create([
                'laptop_name' => $request->laptop_name,
                'lcd_input' => $request->lcd,
                'battery_input' => $request->battery,
                'ram_input' => $request->ram,
                'keyboard_input' => $request->keyboard,
                'final_score' => $fuzzyResult['nilaiKelayakan'],
                'status' => $fuzzyResult['statusKelayakan'],
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