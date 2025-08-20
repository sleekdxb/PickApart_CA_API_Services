<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\RequestController;





Route::post('/create-request', [RequestController::class, 'createRequest']);
Route::post('/update-profile-by-id', [GarageController::class, 'updateProfileById']);
Route::post('/create-profile-by-id', [GarageController::class, 'createProfileById']);
Route::post('/set-profileOrAccount-fileState-by-id', [GarageController::class, 'setFileStateById']);
Route::post('/garage-profiles', [GarageController::class, 'getAccountGarageProfileById']);




Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
