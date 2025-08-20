<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EncryptionController;



Route::post('/encrypt-data', [EncryptionController::class, 'encrypt']);
Route::post('/decrypt-data', [EncryptionController::class, 'decrypt']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
