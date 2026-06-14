<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Processor;
use Illuminate\Http\Request;

class ProcessorController extends Controller
{
    public function index()
    {
        $processors = Processor::orderBy('name')->get(['id', 'name', 'benchmark_score', 'category']);

        return response()->json([
            'data' => $processors,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'benchmark_score' => 'required|integer|min:0',
            'category' => 'required|in:Rendah,Sedang,Tinggi',
        ]);

        $processor = Processor::create([
            'name' => $request->name,
            'benchmark_score' => $request->benchmark_score,
            'category' => $request->category,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Processor created successfully',
            'data' => $processor,
        ], 201);
    }
}
