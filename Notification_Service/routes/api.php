<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorNotificationController;
use App\Http\Controllers\GarageNotificationController;
use App\Http\Controllers\PartNotificationController;
use App\Http\Controllers\AccountNotificationController;


Route::post('/notifications/vendor', [VendorNotificationController::class, 'sendVendor']);
Route::post('/notifications/garage', [GarageNotificationController::class, 'sendGarage']);
Route::post('/notifications/part', [PartNotificationController::class, 'sendPart']);
Route::post('/notifications/account', [AccountNotificationController::class, 'sendAccount']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
