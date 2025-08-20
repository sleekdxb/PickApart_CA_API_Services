<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImagesDocController;
use App\Http\Controllers\PartsImageController;





Route::post('/upload-imagesOrDoc-by-id', [ImagesDocController::class, 'uploadImageOrDoc']);
Route::post('/uploadPartsImage', [PartsImageController::class, 'uploadPartsImage']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
