<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\SubVendorController;
use App\Http\Controllers\PartsController;
use App\Http\Controllers\InventroyController;
use App\Http\Controllers\InventoryTypeController;
use App\Http\Controllers\VendorDashboardStreamController;
use App\Models\ChannelModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;


Route::post('/get-vendor-profiles', [VendorController::class, 'getAccountVendorProfileById']);


Route::post('/garage-profiles', [VendorController::class, 'getAccountGarageProfileById']);
Route::get('/get-profile-by-id', [VendorController::class, 'getVendorProfileById']);
Route::post('/set-profile-by-id', [VendorController::class, 'setProfileById']);
Route::post('/create-profile-by-id', [VendorController::class, 'createProfileById']);
Route::post('/update-profile-by-id', [VendorController::class, 'updateProfileById']);
Route::match(['GET', 'POST'], '/overview', [VendorController::class, 'overview']);
Route::match(['GET', 'POST'], '/partlisting', [VendorController::class, 'partListing']);
Route::put('/set-profileOrAccount-fileState-by-id', [VendorController::class, 'setFileStateById']);
Route::post('/decodeVin', [VendorController::class, 'decodeVin']);
Route::post('/add-subVendor-profile', [SubVendorController::class, 'addSubVendorProfile']);
Route::post('/sub-vendor-login', [SubVendorController::class, 'login']);
Route::delete('/sub-vendor-logout', [SubVendorController::class, 'logout']);
Route::put('/update-sub-vendor-by-id', [SubVendorController::class, 'updateSubVendorProfile']);
Route::post('/addPartsLiting', [PartsController::class, 'addPartsLiting']);
Route::get('/getVendorInventory', [InventroyController::class, 'getInventory']);
Route::get('/getPartsCategory', [PartsController::class, 'getPartsCategory']);
Route::get('/getCategory', [PartsController::class, 'getCategory']);
Route::post('/addPart', [PartsController::class, 'addPart']);
Route::delete('/deletePart', [PartsController::class, 'deletePart']);
Route::put('/updatePart', [PartsController::class, 'updatePart']);
Route::get('/inventory-types', [InventoryTypeController::class, 'getInventoryTypes']);
Route::post('/setInventory', [InventroyController::class, 'setInventory']);
Route::post('/live-stream', [VendorDashboardStreamController::class, 'handleVendorRequest']);

Route::post('/uploading', [PartsController::class, 'store']);

//---------------Channels routes ------------------------------------------------------------------
Route::get('/vendor/dashboard/update/{frequency}', function (Request $request, $frequency) {
    $channels = ChannelModel::where('channel_frequency', $frequency)->get();
    $response = [];

    // Default per_page = 10
    $perPage = $request->filled('per_page') ? max(1, (int) $request->query('per_page')) : 10;
    $page = $request->filled('page') ? max(1, (int) $request->query('page')) : 1;

    foreach ($channels as $channel) {
        if (!$channel->latest_data) {
            continue;
        }

        $decoded = json_decode($channel->latest_data, true);

        $items = is_array($decoded)
            ? (array_key_exists('data', $decoded) && is_array($decoded['data']) ? $decoded['data'] : $decoded)
            : [];

        $collection = collect($items);

        // Always paginate using per_page and page values
        $paginator = new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $response[$channel->vendor_id] = $paginator;
    }

    return response()->json($response);
})->name('vendor.dashboard.update');
Route::group(['middleware' => ['set.guard:sellers-guard'], 'prefix' => 'sellers'], function ($router) {
    //Route::post('/filter-cars-for-sell', [SellerController::class, 'filterCarsForSell']);
});

Route::group(['middleware' => ['set.guard:vendors-guard'], 'prefix' => 'vendors-service'], function ($router) {


    //Route::post('/update-profile', [SellerController::class, 'getProfileById']);
//Route::post('/get-sellers-profile', [SellerController::class, 'getProfileById']);

    //Route::post('/add-seller-by-id', [SellerController::class, 'getProfileById']);
////Route::post('/delete-seller-by-id', [SellerController::class, 'getProfileById']);
//Route::post('/block-seller-by-id', [SellerController::class, 'getProfileById']);

});





Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
