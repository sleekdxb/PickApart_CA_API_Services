<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController; // Ensure your controller is imported here
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\AccountController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// Route to handle user registration. It maps a POST request to the '/registration' URL to the 'createAccount' method in the RegistrationController.
Route::post('/updateAccount', [AccountController::class, 'updateAccount']);

Route::post('/registration', [RegistrationController::class, 'createAccount']);

Route::post('/update-fcm-token', [RegistrationController::class, 'updateFcmToken']);

// Route to generate OTP (One-Time Password) for the user. This POST request will call the 'generateOtp' method in the OtpController.
Route::post('/generate-otp', [OtpController::class, 'generateOtp']);

// Route to verify the OTP entered by the user. This POST request will trigger the 'verifyOtp' method in the OtpController.
Route::post('/verify-otp', [OtpController::class, 'verifyOtp']);

// Route to handle user login. This POST request will call the 'login' method in the LoginController to authenticate the user.
Route::post('/login', [LoginController::class, 'login']);

// Route to log out the authenticated user. This POST request will invoke the 'logout' method in the LoginController to log the user out.
//Route::post('/logout', [LoginController::class, 'logout']);
Route::post('/logout', [LoginController::class, 'logout']);

Route::post('/restPassword', [ResetPasswordController::class, 'resetPassword']);


Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function () {   // Add more user-related routes here
    });
});
