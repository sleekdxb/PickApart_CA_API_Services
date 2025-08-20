<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Services\EncryptionServiceConnections;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\SubVendor;
use App\Models\Access;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
class SubVendorHelper
{
    public static function addSubVendorProfile(Request $request): JsonResponse
    {
        // Step 1: Validate incoming data


        // Step 2: Decode 'access_protocol' JSON

        $accessProtocolArray = json_decode($request->input('access_protocol'), true);



        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid JSON format for access_protocol',
            ], 400);
        }

        // Step 3: Hash the unique identifier for SubVendor
        $hashedValue = Hash::make($request->input('vend_id') . $request->input('email') . now());

        // Step 4: Prepare the access data first, before encryption
        $accessData = [
            'edit_price' => $accessProtocolArray['edit_price'],
            'edit_Inventory' => $accessProtocolArray['edit_Inventory'],
            'edit_Part' => $accessProtocolArray['edit_Part'],
            'create_sub_vendor' => $accessProtocolArray['create_sub_vendor'],
            'change_emails_phone' => $accessProtocolArray['change_emails_phone'],
            'delete_parts' => $accessProtocolArray['delete_parts'],
            'different_accounts' => $accessProtocolArray['different_accounts'],
            'subscription_payments' => $accessProtocolArray['subscription_payments'],
        ];

        // Step 5: Begin database transaction
        DB::beginTransaction();

        try {
            // Step 6: Prepare data for SubVendor creation (but don't encrypt yet)
            $subVendorData = [
                'vend_id' => $request->input('vend_id'),
                'acc_id' => $request->input('acc_id'),
                'sub_ven_id' => $hashedValue,
                'email' => $request->input('email'),
                'job_title' => $request->input('job_title'),
                'password' => Hash::make($request->input('password')), // Store hashed password
                'phone' => $request->input('phone'),
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'created_at' => now(),
            ];

            // Step 7: Store access protocol data in Access model
            $access = [];
            foreach ($accessData as $privilege => $privilegeState) {
                $access[] = Access::create([
                    'priv_id' => Hash::make(now()),  // Unique privilege ID using hashed current time
                    'vend_id' => $request->input('vend_id'),
                    'acc_id' => $request->input('acc_id'),
                    'sub_ven_id' => $hashedValue,
                    'privilege' => $privilege,
                    'state' => $privilegeState,
                    'created_at' => now(),
                ]);
            }

            // Step 8: Encrypt SubVendor sensitive data after Access creation
            $EncryptionData = [
                //'email' => $subVendorData['email'],
                'job_title' => $subVendorData['job_title'],
                'phone' => $subVendorData['phone'],
                'first_name' => $subVendorData['first_name'],
                'last_name' => $subVendorData['last_name'],
            ];

            // Encrypt sensitive SubVendor data
            $onEncryptionSuccess = EncryptionServiceConnections::encryptData($EncryptionData);

            // Step 9: Complete SubVendor data with encrypted values
            //
            $subVendorData['job_title'] = $onEncryptionSuccess['data']['job_title'];
            $subVendorData['phone'] = $onEncryptionSuccess['data']['phone'];
            $subVendorData['first_name'] = $onEncryptionSuccess['data']['first_name'];
            $subVendorData['last_name'] = $onEncryptionSuccess['data']['last_name'];

            // Step 10: Create SubVendor profile
            $subVendor = SubVendor::create($subVendorData);

            // Step 11: Commit the transaction
            DB::commit();

            // Step 12: Return success response with Access data before encryption
            return response()->json([
                'status' => true,
                'message' => 'SubVendor profile created successfully',
                'data' => [
                    'sub_ven_id' => $subVendorData['sub_ven_id'],
                    // 'access' => $access,  // Include the Access data in the response
                ],
            ], 200);

        } catch (Exception $e) {
            // Rollback the transaction if any operation fails
            DB::rollBack();

            // Log the error for further investigation
            Log::error('Error creating SubVendor profile: ' . $e->getMessage());

            // Step 13: Return a failure response
            return response()->json([
                'status' => false,
                'message' => 'Error creating SubVendor profile. Please try again later.',
            ], 500);
        }
    }
    //----------------Update Supvendor-------------------------------------------------------------------------------
    public static function updateSubVendorProfile(Request $request): JsonResponse
    {
        // Step 1: Validate incoming data (optional depending on your validation setup)
        // Assuming validation rules are already applied elsewhere

        // Step 2: Decode 'update_access_protocol' JSON if provided
        $updateAccessprotocolArray = $request->has('update_access_protocol')
            ? json_decode($request->input('update_access_protocol'), true)
            : null;

        Log::info('access array', $updateAccessprotocolArray);

        if ($updateAccessprotocolArray && json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid JSON format for access_protocol',
            ], 400);
        }

        // Step 4: Begin database transaction
        DB::beginTransaction();

        try {
            // Step 5: Fetch existing SubVendor data
            $subVendor = SubVendor::where('sub_ven_id', $request->input('sub_ven_id'))->first();

            if (!$subVendor) {
                return response()->json([
                    'status' => false,
                    'message' => 'SubVendor not found',
                ], 404);
            }

            // Step 6: Initialize an array to store updated fields
            $subVendorData = [];



            if ($request->has('job_title') && !is_null($request->input('job_title'))) {
                $subVendorData['job_title'] = $request->input('job_title');
            }
            if ($request->has('phone') && !is_null($request->input('phone'))) {
                $subVendorData['phone'] = $request->input('phone');
            }
            if ($request->has('first_name') && !is_null($request->input('first_name'))) {
                $subVendorData['first_name'] = $request->input('first_name');
            }
            if ($request->has('last_name') && !is_null($request->input('last_name'))) {
                $subVendorData['last_name'] = $request->input('last_name');
            }
            if ($request->has('password') && !is_null($request->input('password'))) {
                $subVendorData['password'] = Hash::make($request->input('password'));
            }

            // Check for 'is_blocked' field and update if it's set and not null
            if ($request->has('is_blocked') && !is_null($request->input('is_blocked'))) {
                $subVendorData['is_blocked'] = $request->input('is_blocked');
            }

            if ($request->has('email') && !is_null($request->input('email'))) {
                $subVendorData['email'] = $request->input('email');
            }

            $onEncryptionSuccess = EncryptionServiceConnections::encryptData($subVendorData);

            // Step 9: Complete SubVendor data with encrypted values
            //$subVendorData['email'] = $onEncryptionSuccess['data']['email'] ?? '';
            $subVendorData['job_title'] = $onEncryptionSuccess['data']['job_title'] ?? '';
            $subVendorData['phone'] = $onEncryptionSuccess['data']['phone'] ?? '';
            $subVendorData['first_name'] = $onEncryptionSuccess['data']['first_name'] ?? '';
            $subVendorData['last_name'] = $onEncryptionSuccess['data']['last_name'] ?? '';

            // Update the subVendor in the database if there are fields to update
            if (!empty($subVendorData)) {
                $subVendor->update($subVendorData);
            }

            // Step 7: Handle the update for Access model with array data
            if ($updateAccessprotocolArray && is_array($updateAccessprotocolArray)) {
                foreach ($updateAccessprotocolArray as $accessItem) {
                    // Ensure each $accessItem contains a valid priv_id and state
                    if (isset($accessItem['priv_id']) && !empty($accessItem['priv_id'])) {
                        // Find the corresponding Access record by priv_id
                        $access = Access::where('priv_id', $accessItem['priv_id'])->first();

                        // Only update if the Access record exists for the corresponding priv_id
                        if ($access) {
                            // Update the 'state' field with the provided state value
                            $access->state = $accessItem['state'];

                            // Save the updated Access record
                            $access->save();
                        } else {
                            // Log a warning if no Access record is found for the given priv_id
                            Log::warning('No Access record found for priv_id: ' . $accessItem['priv_id']);
                        }
                    } else {
                        // Log a warning if priv_id is missing or empty
                        Log::warning('priv_id is missing or empty in access item.');
                    }
                }
            }

            // Step 9: Commit the transaction
            DB::commit();
            $updatedAccessState = Access::where('sub_ven_id', $request->input('sub_ven_id'))
                ->get(['privilege', 'state']);
            // Step 10: Return success response with updated data and Access data
            return response()->json([
                'status' => true,
                'message' => 'SubVendor profile and access data updated successfully',
                'data' => array_merge(
                    [
                        'sub_ven_id' => $subVendor->sub_ven_id,
                        'access state' => $updatedAccessState,

                    ], // Include updated SubVendor fields in the response
                ),
            ], 200);

        } catch (Exception $e) {
            // Step 11: Rollback the transaction if any operation fails
            DB::rollBack();

            // Log the error for further investigation
            Log::error('Error updating SubVendor profile: ' . $e->getMessage());

            // Return failure response
            return response()->json([
                'status' => false,
                'message' => 'Error updating SubVendor profile. Please try again later.',
            ], 500);
        }
    }






    //------Login--------------------------------------------------------------------------------------------------
    public static function login(Request $request, $subVendor): JsonResponse
    {



        try {
            // Step 2: Retrieve SubVendor by email



            // Step 3: Decrypt password (this assumes password was encrypted in SubVendor model)


            // Step 4: Verify the password entered by the user against the stored hashed password
            if (!Hash::check($request->input('password'), $subVendor->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid password.',
                ], 401);
            }

            // Step 5: Generate access token using Laravel Passport or Sanctum (if you have Passport or Sanctum configured)
            // Using the default Laravel authentication guard to generate the token (ensure the user model is correctly set up)
            $access_protocol = Access::where('sub_ven_id', $subVendor->sub_ven_id)->get();
            // Generate the access token
            $accessToken = JWTAuth::fromUser($subVendor);

            // Step 6: Return success response with access token
            return response()->json([
                'status' => true,
                'message' => 'Login successful.',
                'data' => [
                    'account' => [
                        'sub_ven_id' => $subVendor->sub_ven_id,
                        'token' => $accessToken
                    ],
                    'memberships' => [
                        'memb_id' => '$2y$10$QZEy2.RdgS47YB1f08n5N.UAUfEoJTY4quAxikeNRfLbA0hTeOpaa',
                        'type' => 'Premium',
                        'status' => 'Active',
                        'start_date' => '2025-01-01',
                        'end_date' => '2026-01-01',
                        'created_at' => '2025-01-01 10:00:00',
                        'updated_at' => '2025-01-15 12:00:00',
                    ],
                    'access_protocol' => [
                        $access_protocol
                    ]



                ]
                // Include the access token in the response
            ], 200);

        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Login error: ' . $e->getMessage());

            // Step 7: Return error response in case of an exception
            return response()->json([
                'status' => false,
                'message' => 'An error occurred. Please try again later.',
            ], 500);
        }
    }
}
