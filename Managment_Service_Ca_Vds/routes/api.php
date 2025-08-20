<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TeamLeaderController;
use App\Http\Controllers\AdminMessageController;
use App\Http\Controllers\VendorManageController;
use App\Models\AdminChannel;


Route::get('/edit-staff', [VendorController::class, 'getVendorMembership']);
Route::get('/delete-staff', [VendorController::class, 'getVendorMembership']);
Route::get('/get-staff', [VendorController::class, 'getVendorMembership']);

//-------------------------Auth-------------------------------------------------------------------
Route::post('/reset-staff-password', [AuthController::class, 'resetStaffPassword']);
Route::post('/staff-login', [AuthController::class, 'staffLogin']);
Route::post('/staff-logout', [AuthController::class, 'staffLogout']);
Route::post('/add-staff', [AuthController::class, 'addStaff']);

//---------------------------------------VendorManageController----------------------------------------------------------
Route::post('/update-vendor-account-profile', [VendorManageController::class, 'UpdateVendorWithAccount']);
Route::post('/admin/send', [AdminMessageController::class, 'send']);

Route::post('/testAccount', [TeamLeaderController::class, 'getAccountsDataTest']);

//------------------------------------------------------------------------------------------------
Route::get('/admin-channel/data/{channel_frequency}', function ($channel_frequency) {
    // Retrieve page number and per_page value from request (default to 1 and 20 if not provided)
    $page = request()->get('page', 1);
    $perPage = request()->get('per_page', 20);

    // Query for the data, paginate it, and filter by channel_frequency
    $adminChannels = AdminChannel::where('channel_frequency', $channel_frequency)
        ->paginate($perPage, ['*'], 'page', $page);

    // Return the paginated result along with pagination data
    return response()->json([
        'data' => $adminChannels->items(), // Get the current page items
        'current_page' => $adminChannels->currentPage(), // Current page
        'total_pages' => $adminChannels->lastPage(), // Total number of pages
        'total_records' => $adminChannels->total(), // Total records available
        'next_page' => $adminChannels->hasMorePages() ? $adminChannels->currentPage() + 1 : null, // Next page, if available
        'prev_page' => $adminChannels->currentPage() > 1 ? $adminChannels->currentPage() - 1 : null, // Previous page, if available
    ]);
})->name('admin.channel.data');

//------------------------------------------------------------------------------------------------



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
