<?php

namespace App\Http\Controllers;
use App\Models\SubVendor;
use App\Models\Vendor;  // Import the Vendor model
use App\Models\Account; // Import the Account model
use App\Models\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\PartsHelper;
use Validator;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;

use App\Models\Manufacturer;
use App\Models\PartName;
use App\Models\CarModel;
class PartsController extends Controller
{
    protected $headerValidationController;
    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }

    public function addPartsLiting(Request $request): JsonResponse
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        // Validate the incoming request to ensure the 'name' is present
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'cat_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid input, inventory and vend_id are required.',
                'errors' => $validator->errors()
            ], 400);
        }
        // Get the 'name' from the request
        $name = $request->input('name');

        // Generate a hashed value for make_id using current timestamp
        $make_id = hash('sha256', now()->timestamp . $name);

        // Insert into the manufacturers table
        $manufacturer = DB::table('PartsName')->insertGetId([
            'model_part_id' => $make_id,
            'cat_id' => $request->input('cat_id'),
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Call the addPartsLiting method in the PartsHelper and pass the request
        // PartsHelper::addPartsLiting($request);

        // Return a JSON response with a 200 status and a success message
        return response()->json([
            'message' => 'add',
            'status' => true,
            'data' => $manufacturer,
        ], 200);
    }
    //-------------------------------------------------------------------------------
    public function getCategory(Request $request): JsonResponse
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        // Fetch the manufacturers with their linked models based on the `make_id`
        $makeAndModel = Manufacturer::query()->with('model')->get();

        // Return the response with grouped data
        return response()->json([
            'message' => 'Categories fetched successfully',
            'status' => true,
            'data' => $makeAndModel,
        ], 200);
    }
    //-------------------------------------------------------------------------
    public function getPartsCategory(Request $request): JsonResponse
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        $categoryData = DB::table('partscategory')->get();

        // Step 2: For each partscategory, retrieve a limited number of PartsName records
        $categoryData = $categoryData->map(function ($category) {
            $category->parts_names = DB::table('PartsName')
                ->where('cat_id', $category->cat_id)
                ->limit(3)  // Adjust the limit as needed
                ->get();

            return $category;
        });
        // Fetch the manufacturers with their linked models based on the `make_id`
        $categoryData = DB::table('partscategory')->select('partscategory.*', 'PartsName.*')
            // Join the models table with manufacturers on make_id
            ->join('PartsName', 'partscategory.cat_id', '=', 'PartsName.cat_id')
            // Select both manufacturers and models data
            ->get();

        // Group the data by manufacturers and map their models
        $groupedData = $categoryData->groupBy('cat_id')->map(function ($items) {
            $manufacturer = $items->first(); // The first item in each group will be the manufacturer
            $models = $items->pluck('name', 'part_name_id'); // Get all models names as a list

            return [
                'category' => $manufacturer, // The manufacturer data
                'parts' => $models, // The associated models
            ];
        });

        // Return the response with grouped data
        return response()->json([
            'message' => ' Parts Categories fetched successfully',
            'status' => true,
            'data' => $groupedData,
        ], 200);
    }
    //------------------------------------------------------------------------
    public function addPart(Request $request): JsonResponse
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        // Validate the incoming request to ensure the fields meet the specified criteria
        $validator = Validator::make($request->all(), [
            'vend_id' => 'required|string|max:255',
            'sub_ven_id' => 'nullable|string|max:255',
            'inve_id' => 'required|string|max:255',
            'make_id' => 'required|string|max:255',
            'model_id' => 'required|string|max:255',
            'cat_id' => 'required|string|max:255',
            'sub_cat_id' => 'required|string|max:255',
            'stock_id' => 'required|string|max:255',
            'quantity' => 'required|string|max:255',
            'year' => 'required|string',
            'description' => 'required|string|max:250',
            'sale_price' => 'required|string',
            'retail_price' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Return a JSON response with validation errors and a 422 status code
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Call the helper function to add the part if validation passes
            return PartsHelper::addPart($request);
        } catch (\Exception $e) {
            // Catch any exceptions and return a JSON response with the exception message
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while adding the part',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //--------------------------------------------------------------------------------------------
    public function deletePart(Request $request): JsonResponse
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        // Validate the incoming request to ensure the fields meet the specified criteria
        $validator = Validator::make($request->all(), [
            'part_id' => 'required|string|max:255',

        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Return a JSON response with validation errors and a 422 status code
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Call the helper function to add the part if validation passes
            return PartsHelper::deletePart($request);
        } catch (\Exception $e) {
            // Catch any exceptions and return a JSON response with the exception message
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the part',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //-----------------------------------------------------------------------------------
    public function updatePart(Request $request): JsonResponse
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        // Validate the incoming request to ensure the fields meet the specified criteria
        $validator = Validator::make($request->all(), [
            'part_id' => 'required|string|max:255',
            'vend_id' => 'nullable|string|max:255',
            'sub_ven_id' => 'nullable|string|max:255',
            'inve_id' => 'nullable|string|max:255',
            'make_id' => 'nullable|string|max:255',
            'model_id' => 'nullable|string|max:255',
            'cat_id' => 'nullable|string|max:255',
            'sub_cat_id' => 'nullable|string|max:255',
            'stock_id' => 'nullable|string|max:255',
            'quantity' => 'nullable|string|max:255',
            'year' => 'nullable|string',
            'description' => 'nullable|string|max:250',
            'sale_price' => 'nullable|string',
            'retail_price' => 'nullable|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Return a JSON response with validation errors and a 422 status code
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Call the helper function to add the part if validation passes
            return PartsHelper::updatePart($request);
        } catch (\Exception $e) {
            // Catch any exceptions and return a JSON response with the exception message
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while adding the part',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    public function store(Request $request)
    {
        // Validate the incoming request if necessary
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);
        if ($validator->fails()) {
            // Return a JSON response with validation errors and a 422 status code
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }


        // Get the uploaded CSV file
        $file = $request->file('csv_file');

        // Parse the CSV file
        $csvData = $this->parseCsv($file);

        // Prepare the current time for created_at and updated_at
        $currentTime = Carbon::now();

        // Iterate over CSV data and insert records
        foreach ($csvData as $row) {
            // Ensure 'make_id' and 'name' exist in each row before using them
            if (isset($row['cat_id '], $row['name'])) {
                // Assign values from the CSV row to variables
                $cat_id = trim($row['cat_id']);
                $name = trim($row['name']);

                // Generate model_id using hash (or any method you prefer)
                $part_name_id = hash('sha256', $name . now()); // Customize this based on your data

                // Create the car record
                PartName::create([
                    'cat_id' => $cat_id,
                    'name' => $name,
                    'part_name_id' => $part_name_id,
                    'created_at' => $currentTime,
                    'updated_at' => $currentTime,
                ]);
            } else {
                // If 'make_id' or 'name' are missing in a row, you can log or skip the row
                // Log::warning("Missing 'make_id' or 'name' for row: " . json_encode($row));
            }
        }

        return response()->json(['message' => 'Cars successfully added'], 200);
    }

    private function parseCsv($file)
    {
        // Open the file
        $csv = fopen($file->getRealPath(), 'r');

        // Read the CSV and return the data as an array
        $header = fgetcsv($csv); // Assume first row is the header
        $data = [];

        while ($row = fgetcsv($csv)) {
            $data[] = array_combine($header, $row); // Combine header with row data
        }

        fclose($csv);

        return $data;
    }

}
