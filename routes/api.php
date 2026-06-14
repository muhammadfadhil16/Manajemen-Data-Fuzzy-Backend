<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\ProcessorController;

Route::get('/processors', [ProcessorController::class, 'index']);
Route::post('/processors', [ProcessorController::class, 'store']);

Route::get('/assessments', [AssessmentController::class, 'index']);
Route::post('/assessments', [AssessmentController::class, 'store']);
Route::get('/assessments/{id}', [AssessmentController::class, 'show']);
Route::delete('/assessments/{id}', [AssessmentController::class, 'destroy']);
