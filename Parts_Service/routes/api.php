<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartController;
use App\Http\Controllers\ImpressionsController;





Route::post('/setPartFileState', [PartController::class, 'setFileStateById']);
Route::post('/filter-parts', [PartController::class, 'filterParts']);
Route::post('/set-part-impression', [ImpressionsController::class, 'setImpressions']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
