<?php
namespace App\Helpers;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Package;
use Illuminate\Support\Facades\Log;
class PackageHelper
{
    public static function getPackage(Request $request)
    {
        // Perform logic to fetch vendor membership details based on the vendor_id
        $package = Package::all();

        // Return a response (can be JSON or other formats as per your requirement)
        if ($package) {
            return response()->json([
                'status' => true,
                'message' => null,
                'code' => 200,
                'data' => $package
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Package not found',
                'data' => []
            ], 404);
        }
    }

  public static function addPackage(Request $request)
    {
        // Check if the 'name' field is present in the request
        $name = $request->input('name');
     //   $pkg =  Package::where('name',$name)->first();
        
     //   if ($pkg) {
     //       return response()->json([
       //         'status' => 'error',
       //         'message' => 'Package is already exist',
       //         'data' => []
        //    ], 404);
     //   }

        // Assign other fields from the request
        $pak_id = Hash::make(Str::uuid()->toString());
        $currency = $request->input('currency');
        $payment_type = $request->input('payment_type');
        $price = $request->input('price');
        $features = $request->input('features');
        $description = $request->input('description');

        try {
            // Create the package
            $package = Package::create([
                'pak_id' => $pak_id,
                'name' => $name,
                'currency' => $currency,
                'payment_type' => $payment_type,
                'price' => $price,
                'features' => json_encode($features),
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // If package created successfully, return a response
            return response()->json([
                'status' => true,
                'message' => null,
                'code' => 200,
                'data' => $package
            ], 200);

        } catch (\Exception $e) {
            // Catch any exception that occurs during the creation process
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while creating the package: ' . $e->getMessage(),
                'data' => []
            ], 500);  // You can change the status code to 500 for internal server error
        }
    }
}

