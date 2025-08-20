<?php
namespace App\Helpers;

use Illuminate\Http\Request;
use App\Models\Membership;
class SellerHelper

{
public static function getSellerMembership(Request $request)
{
    // You can access query parameters through $request->input() or $request->query()
    $accId= $request->query('acc_id'); // Example of fetching query parameter

    // Perform logic to fetch vendor membership details based on the vendor_id
    $vendorMembership = Membership::where('acc_id', $accId)->first();

    // Return a response (can be JSON or other formats as per your requirement)
    if ($vendorMembership) {
        return response()->json([
            'status' => true,
            'message' => null,
            'code' => 200,
            'data' => $vendorMembership
        ],200);
    } else {
        return response()->json([
            'status' => 'error',
            'message' => 'Vendor membership not found', 
             'data' => []
        ], 404);
    }
}


}

