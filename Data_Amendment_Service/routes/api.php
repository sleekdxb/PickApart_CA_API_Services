<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorAmendmentController;




Route::post('/set-vendor-amendment', [VendorAmendmentController::class, 'setVendorAmendment']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
