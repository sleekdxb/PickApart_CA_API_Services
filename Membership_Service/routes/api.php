<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\PackageController;


Route::get('/get-membership-for-vendor', [VendorController::class, 'getVendorMembership']);
Route::get('/get-package', [PackageController::class, 'getPackage']);
Route::post('/add-package', [PackageController::class, 'addPackage']);

Route::group(['middleware' => ['set.guard:vendor-guard'], 'prefix' => 'vendors'], function ($router) {
});



Route::group(['middleware' => ['set.guard:seller-guard'], 'prefix' => 'sellers'], function ($router) {
    Route::get('/get-membership-for-seller', [SellerController::class, 'getSellerMembership']);
});






Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});