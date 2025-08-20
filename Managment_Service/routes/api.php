<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::get('/edit-staff', [VendorController::class, 'getVendorMembership']);
Route::get('/delete-staff', [VendorController::class, 'getVendorMembership']);
Route::get('/get-staff', [VendorController::class, 'getVendorMembership']);

//-------------------------Auth-------------------------------------------------------------------
Route::post('/reset-staff-password', [AuthController::class, 'resetStaffPassword']);
Route::post('/staff-login', [AuthController::class, 'staffLogin']);
Route::post('/staff-logout', [AuthController::class, 'staffLogout']);
Route::post('/add-staff', [AuthController::class, 'addStaff']);
//-------------------------------------------------------------------------------------------------


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
