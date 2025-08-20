<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Events\VendorDashboardLiveUpdate;
use App\Models\Vendor;
use App\Models\ChannelModel; // Import the Channel model
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VendorDashboardStreamController extends Controller
{
    protected $headerValidationController;
    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }
    public function handleVendorRequest(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        // Validate the request
        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|string',
            'operation' => 'required|boolean',
            'channel_name' => 'required|string'
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422); // 422 Unprocessable Entity is commonly used for validation errors
        }

        // Extract validated data
        $vendorId = $request->input('vendor_id');
        $operation = $request->input('operation');
        $channel_name = $request->input('channel_name');

        // Fetch the vendor data from the database
        $vendor = Vendor::where('vend_id', $vendorId)->first();

        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 404);
        }

        // Handle the streaming operation
        if ($operation == 1) {
            // Create a new channel entry for the vendor when streaming starts
            $channel = new ChannelModel();
            $channel->vendor_id = $vendorId;
            $channel->updated_at = now();
            $channel->created_at = now();
            $channel->channel_name = $channel_name . Str::random(10); // Unique channel name
            $channel->channel_frequency = mt_rand(100000, 999999); // Generate a unique frequency number
            $channel->save();

            $operationResult = 'Streaming Started Successfully';
            $randomSixDigitNumber = $channel->channel_frequency; // Return the frequency as the 6-digit number

        } elseif ($operation == 0) {
            // Delete the channel entry for the vendor when streaming ends
            $channel = ChannelModel::where('vendor_id', $vendorId)->first();

            if ($channel) {
                $channel->delete();
                $operationResult = 'Streaming Ended Successfully';
                $randomSixDigitNumber = null; // No frequency for the ended stream
            } else {
                return response()->json(['status' => false, 'message' => 'Channel not found for this vendor'], 404);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid operation'], 400);
        }

        // Return the response with the operation result and the generated channel frequency (if applicable)
        return response()->json([
            'status' => true,
            'message' => $operationResult,
            'channel_name' => $channel->channel_name,
            'channel_frequency' => $randomSixDigitNumber // Return the channel frequency as the random 6-digit number
        ], 200);
    }
}
