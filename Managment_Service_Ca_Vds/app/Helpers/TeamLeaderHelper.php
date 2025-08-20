<?php
namespace App\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\VendorState;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Exception;
class TeamLeaderHelper
{



    public static function setVendorAccountDocState(Request $request)
    {
        try {
            // Retrieve input values from the request
            $acc_id = $request->input('acc_id');
            $doer_acc_id = $request->input('doer_acc_id');
            $vend_id = $request->input('vend_id');
            $reason = $request->input('reason');
            $note = $request->input('note');
            $state_name = $request->input('state_name');
            $time_period = $request->input('time_period');
            $state_code = $request->input('state_code');
            // Create a new VendorState record
            $vendorState = VendorState::create([
                'state_id' => Str::uuid(), // Generate hashed unique ID
                'acc_id' => $acc_id,
                'doer_acc_id' => $doer_acc_id,
                'vend_id' => $vend_id,
                'reason' => $reason,
                'note' => $note,
                'state_code' => $state_code, // Optional: random state_code
                'state_name' => $state_name,
                'time_period' => $time_period,
            ]);
            if ($vendorState) {
                $vendorB = Vendor::where('vend_id', $vend_id); // ✅ Use vend_id from vendor_state

                if ($vendorB) {
                    $vendorB->update([
                        'state_id' => $vendorState->state_id
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Vendor state saved successfully.',
                'data' => $vendorState,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while saving vendor state.',
                'error' => $e->getMessage(), // Remove or log only in production
            ], 500);
        }
    }

    public static function setVendorState(Request $request)
    {
        try {
            // Retrieve input values from the request
            $acc_id = $request->input('acc_id');
            $doer_acc_id = $request->input('doer_acc_id');
            $vend_id = $request->input('vend_id');
            $reason = $request->input('reason');
            $note = $request->input('note');
            $state_name = $request->input('state_name');
            $time_period = $request->input('time_period');
            $state_code = $request->input('state_code');
            // Create a new VendorState record
            $vendorState = VendorState::create([
                'state_id' => Str::uuid(), // Generate hashed unique ID
                'acc_id' => $acc_id,
                'doer_acc_id' => $doer_acc_id,
                'vend_id' => $vend_id,
                'reason' => $reason,
                'note' => $note,
                'state_code' => $state_code, // Optional: random state_code
                'state_name' => $state_name,
                'time_period' => $time_period,
            ]);
            if ($vendorState) {
                $vendorB = Vendor::where('vend_id', $vend_id); // ✅ Use vend_id from vendor_state

                if ($vendorB) {
                    $vendorB->update([
                        'state_id' => $vendorState->state_id
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Vendor state saved successfully.',
                'data' => $vendorState,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while saving vendor state.',
                'error' => $e->getMessage(), // Remove or log only in production
            ], 500);
        }
    }
}