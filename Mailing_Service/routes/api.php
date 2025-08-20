<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;

// Route to send an email for account verification
Route::post('/send-email', [EmailController::class, 'sendEmail']);
Route::post('/set-emails-subscription-mail', [EmailController::class, 'setEmailsSubscriptionMail']);
Route::post('/set-emails-registration-success-mail', [EmailController::class, 'setEmailsRegistrationSuccessMail']);
Route::post('/set-emails-password-mail', [EmailController::class, 'setEmailsPasswordMail']);
Route::post('/set-maintenance-mail', [EmailController::class, 'setMaintenanceMail']);
Route::post('/set-garage_Vendor-cearation-created-mail', [EmailController::class, 'setGarage_VendorCearationCreatedMail']);
Route::post('/set-account-stated-mail', [EmailController::class, 'setAccountStatedMail']);
Route::post('/reset-password-email', [EmailController::class, 'resetPasswordEmail']);

// Route to fetch emails by a specific user ID
Route::get('/fetch-emails-by-id/{id}', [EmailController::class, 'fetchEmailsById']);

// Route to fetch emails by their status (e.g., verified, pending, etc.)
Route::get('/fetch-emails-by-status/{status}', [EmailController::class, 'fetchEmailsByStatus']);

// Route to fetch emails by a specific date
Route::get('/fetch-emails-by-date/{date}', [EmailController::class, 'fetchEmailsByDate']);

// Route to delete an email by its ID
Route::delete('/delete-email/{id}', [EmailController::class, 'deleteEmail']);




//-----------------------------------------------------------------------------------------------------------------

// Protected route that returns the authenticated user's information
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');  // Only accessible to users authenticated with Sanctum
