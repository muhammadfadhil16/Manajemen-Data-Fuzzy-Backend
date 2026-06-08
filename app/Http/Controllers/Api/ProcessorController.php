<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Processor;

class ProcessorController extends Controller
{
    public function index()
    {
        $processors = Processor::orderBy('name')->get(['id', 'name', 'benchmark_score', 'category']);

        return response()->json([
            'data' => $processors,
        ]);
    }
}
