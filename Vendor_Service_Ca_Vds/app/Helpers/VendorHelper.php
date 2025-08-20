<?php

namespace App\Helpers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Services\EncryptionServiceConnections;
use App\Models\Account;
use App\Models\AccountsMedia;
use App\Models\Part;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Models\Vendor_State;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
class VendorHelper
{

    public static function getVendorProfile(Request $request): JsonResponse
    {
        // Retrieve the acc_id from the request (GET or POST)
        $acc_id = $request->input('acc_id');

        try {
            // Query the Vendor model by acc_id
            $vendor = Vendor::where('acc_id', $acc_id)->with('vendor_state')->first();

            // If vendor not found
            if (!$vendor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vendor Profile not found with the given account id',
                    'data' => []
                ], 404);
            }



            return response()->json([
                'status' => true,
                'message' => 'Vendor Profile data retrieved successfully',
                'data' => $vendor
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public static function setVendorProfile(Request $request): JsonResponse
    {
        // Retrieve the acc_id from the request (GET or POST)
        $acc_id = $request->input('acc_id');
        $main = null;
        if ($request->has('main') && $request->input('main') !== null) {
            $main = $request->input('main');
        }
        // Ensure that the acc_id is provided
        if (!$acc_id) {
            return response()->json([
                'status' => false,
                'message' => 'Account ID is required',
                'data' => []
            ], 400);
        }

        try {
            // Check if a vendor with the same acc_id already exists
            $existingVendor = Vendor::where('acc_id', $acc_id)->first();

            if ($existingVendor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vendor Profile with this Account ID already exists',
                    'data' => []
                ], 409); // 409 Conflict status code
            }

            // Hash the acc_id to generate the vend_id
            $vend_id = Hash::make($acc_id . now()->format('Y-m-d H:i:s'));

            // Create a new vendor record with acc_id, vend_id, m, and s
            $vendor = Vendor::create([
                'acc_id' => $acc_id,
                'vend_id' => $vend_id,
                'main' => $main,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Vendor Profile created successfully',
                'data' => [
                    'vend_id' => $vendor->vend_id
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
    public static function createProfileById(Request $request)
    {
        try {
            // Step 2: Validate that acc_id exists in the vendors table
            $acc_id = $request->input('acc_id');  // Get acc_id from query parameter (GET request)
            $inventoryArray = [
                "inve_class1" => "Original/Manufacturer",
                "inve_class2" => "After Market",
                "inve_class3" => "Used/Scrap"
            ];

            // Step 2: Validate that acc_id is present in the request
            if (empty($acc_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Account ID is required.',
                ], 400); // Bad Request
            }

            // Step 5: Find the vendor by acc_id
            $vendor = null;

            $vendor = Vendor::where('acc_id', $acc_id)->get();




            // Step 6: Prepare the data to be updated, including only non-null values
            $updateData = [];


            if ($request->has('firstName') && $request->input('firstName') !== null) {
                $updateData['firstName'] = $request->input('firstName');
            }

            if ($request->has('lastName') && $request->input('lastName') !== null) {
                $updateData['lastName'] = $request->input('lastName');
            }

            if ($request->has('phone') && $request->input('phone') !== null) {
                $updateData['phone'] = $request->input('phone');
            }

            if ($request->has('business_name') && $request->input('business_name') !== null) {
                $updateData['business_name'] = $request->input('business_name');
            }

            if ($request->has('location') && $request->input('location') !== null) {
                $updateData['location'] = $request->input('location');
            }

            if ($request->has('country') && $request->input('country') !== null) {
                $updateData['country'] = $request->input('country');
            }
            if ($request->has('account_type') && $request->input('account_type') !== null) {
                $updateData['account_type'] = $request->input('account_type');
            }

            if ($request->has('lat') && $request->input('lat') !== null) {
                $updateData['lat'] = $request->input('lat');
            }

            if ($request->has('long') && $request->input('long') !== null) {
                $updateData['long'] = $request->input('long');
            }

            if ($request->has('address') && $request->input('address') !== null) {
                $updateData['address'] = $request->input('address');
            }



            if ($request->has('official_email') && $request->input('official_email') !== null) {
                $updateData['official_email'] = $request->input('official_email');
            }

            if ($request->has('official_phone') && $request->input('official_phone') !== null) {
                $updateData['official_phone'] = $request->input('official_phone');
            }


            if ($request->has('owner_id_number') && $request->input('owner_id_number') !== null) {
                $updateData['owner_id_number'] = $request->input('owner_id_number');
            }

            if ($request->has('owner_id_full_name') && $request->input('owner_id_full_name') !== null) {
                $updateData['owner_id_full_name'] = $request->input('owner_id_full_name');
            }

            if ($request->has('state_position') && $request->input('state_position') !== null) {
                $updateData['state_position'] = $request->input('state_position');
            }

            if ($request->has('isOwner') && $request->input('isOwner') !== null) {
                $isOwner = $request->input('isOwner');
            }

            if ($request->has('i_admit_not_owner') && $request->input('i_admit_not_owner') !== null) {
                $i_admit_not_owner = $request->input('i_admit_not_owner');
            }


            // Only update the vendor if there's anything to update
            if (!empty($updateData)) {

                // Create an instance of the EncryptionServiceConnections class
                // Encrypt the update data
                $onEncryptionSuccess = EncryptionServiceConnections::encryptData($updateData);

                $encryptionUpdateDataAccount = [
                    'firstName' => $request->input('firstName') ?? null,
                    'lastName' => $request->input('lastName') ?? null,
                    'account_type' => $onEncryptionSuccess['data']['account_type'] ?? null,
                    'phone' => $onEncryptionSuccess['data']['phone'] ?? null,
                    'updated_at' => now()
                ];

                $encryptionUpdateData = [
                    'acc_id' => $acc_id,
                    'vend_id' => Hash::make(now()),
                    'business_name' => $updateData['business_name'] ?? null,
                    'location' => $updateData['location'] ?? '',
                    'long' => $updateData['long'] ?? null,
                    'lat' => $updateData['lat'] ?? null,
                    'address' => $updateData['address'] ?? null,
                    'country' => $updateData['country'] ?? null,
                    'official_email' => $updateData['official_email'] ?? null,
                    'official_phone' => $updateData['official_phone'] ?? null,
                    'owner_id_number' => $updateData['owner_id_number'] ?? null,
                    'owner_id_full_name' => $updateData['owner_id_full_name'] ?? null,
                    'state_position' => $updateData['state_position'] ?? null,
                    'isOwner' => $isOwner ?? false,
                    'i_admit_not_owner' => $i_admit_not_owner ?? false,
                    'updated_at' => now(),
                    'created_at' => now()
                ];

                // Update the vendor record with the encrypted data
                Account::where('acc_id', $acc_id)->update($encryptionUpdateDataAccount);
                if ($request->input('account_type')) {
                    Vendor::create($encryptionUpdateData);
                    InventoryHelper::setInventory($inventoryArray, $encryptionUpdateData['vend_id']);
                }
                if ($vendor) {
                    $vendor_state = Vendor_State::create([
                        'state_id' => Str::uuid(), // Random unique ID for the state
                        'acc_id' => $acc_id,          // Vendor account ID
                        'doer_acc_id' => 'SYSTEM',              // Action done by the system
                        'vend_id' => $encryptionUpdateData['vend_id'],         // Vendor ID
                        'note' => 'Vendor profile created, and set state automatically by the system.',
                        'reason' => 'All required information verified. Profile Under approval process.',
                        'state_code' => 'SYS412APPROVED',
                        'state_name' => 'To Be Approved',
                    ]);
                }

            }

            // Return success response with the updated vend_id
            return response()->json([
                'status' => true,
                'message' => 'Vendor profile Created successfully',
                'data' => [
                    'vend_id' => $encryptionUpdateData['vend_id'],// Return the vend_id after update
                    'profile_state' => [
                        'state_id' => $vendor_state->state_id,
                        'doer_acc_id' => $vendor_state->doer_acc_id,
                        'note' => $vendor_state->note,
                        'reason' => $vendor_state->reason,
                        'state_code' => $vendor_state->state_code,
                        'state_name' => $vendor_state->state_name,


                    ] ?? []
                ]
            ], 200); // OK    

        } catch (\Exception $e) {
            // Log the exception message for debugging (optional)
            \Log::error('Error creating vendor profile: ' . $e->getMessage());

            // Return 500 Internal Server Error
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the profile. Please try again later.',
                'data' => []
            ], 500); // Internal Server Error
        }
    }



    //--------------------------------------------------------------------------------------------------------------
    public static function updateProfileById(Request $request)
    {
        try {
            // Step 2: Validate that acc_id exists in the vendors table
            $vend_id = $request->input('vend_id');  // Get acc_id from query parameter (GET request)





            $vendor = Vendor::where('vend_id', $vend_id)->first();




            // Step 6: Prepare the data to be updated, including only non-null values
            $updateData = [];


            if ($request->has('firstName') && $request->input('firstName') !== null) {
                $updateData['firstName'] = $request->input('firstName');
            }

            if ($request->has('lastName') && $request->input('lastName') !== null) {
                $updateData['lastName'] = $request->input('lastName');
            }

            if ($request->has('phone') && $request->input('phone') !== null) {
                $updateData['phone'] = $request->input('phone');
            }

            if ($request->has('business_name') && $request->input('business_name') !== null) {
                $updateData['business_name'] = $request->input('business_name');
            }

            if ($request->has('location') && $request->input('location') !== null) {
                $updateData['location'] = $request->input('location');
            }

            if ($request->has('country') && $request->input('country') !== null) {
                $updateData['country'] = $request->input('country');
            }
            if ($request->has('account_type') && $request->input('account_type') !== null) {
                $updateData['account_type'] = $request->input('account_type');
            }

            if ($request->has('lat') && $request->input('lat') !== null) {
                $updateData['lat'] = $request->input('lat');
            }

            if ($request->has('long') && $request->input('long') !== null) {
                $updateData['long'] = $request->input('long');
            }
            if ($request->has('address') && $request->input('address') !== null) {
                $updateData['address'] = $request->input('address');
            }





            if ($request->has('official_email') && $request->input('official_email') !== null) {
                $updateData['official_email'] = $request->input('official_email');
            }

            if ($request->has('official_phone') && $request->input('official_phone') !== null) {
                $updateData['official_phone'] = $request->input('official_phone');
            }


            if ($request->has('owner_id_number') && $request->input('owner_id_number') !== null) {
                $updateData['owner_id_number'] = $request->input('owner_id_number');
            }

            if ($request->has('owner_id_full_name') && $request->input('owner_id_full_name') !== null) {
                $updateData['owner_id_full_name'] = $request->input('owner_id_full_name');
            }

            if ($request->has('state_position') && $request->input('state_position') !== null) {
                $updateData['state_position'] = $request->input('state_position');
            }

            if ($request->has('isOwner') && $request->input('isOwner') !== null) {
                $isOwner = $request->input('isOwner');
            }

            if ($request->has('i_admit_not_owner') && $request->input('i_admit_not_owner') !== null) {
                $i_admit_not_owner = $request->input('i_admit_not_owner');
            }


            // Only update the vendor if there's anything to update
            if (!empty($updateData)) {

                // Create an instance of the EncryptionServiceConnections class
                // Encrypt the update data
                $onEncryptionSuccess = EncryptionServiceConnections::encryptData($updateData);


                $encryptionUpdateDataAccount = [
                    'firstName' => $request->input('firstName') ?? null,
                    'lastName' => $request->input('lastName') ?? null,
                    'account_type' => $onEncryptionSuccess['data']['account_type'] ?? null,
                    'phone' => $onEncryptionSuccess['data']['phone'] ?? null,
                    'updated_at' => now()
                ];

                $encryptionUpdateData = [
                    'business_name' => $updateData['business_name'] ?? null,
                    'location' => $updateData['location'] ?? '',
                    'address' => $updateData['address'] ?? null,
                    'long' => $updateData['long'] ?? null,
                    'lat' => $updateData['lat'] ?? null,
                    'country' => $updateData['country'] ?? null,
                    'official_email' => $updateData['official_email'] ?? null,
                    'official_phone' => $updateData['official_phone'] ?? null,
                    'owner_id_number' => $updateData['owner_id_number'] ?? null,
                    'owner_id_full_name' => $updateData['owner_id_full_name'] ?? null,
                    'state_position' => $updateData['state_position'] ?? null,
                    'isOwner' => $isOwner ?? false,
                    'i_admit_not_owner' => $i_admit_not_owner ?? false,
                    'updated_at' => now(),
                    'created_at' => now()
                ];

                // Update the vendor record with the encrypted data
                Account::where('acc_id', $vendor->acc_id)->update($encryptionUpdateDataAccount);
                if ($request->input('account_type')) {
                    Vendor::where('vend_id', $vend_id)->update($encryptionUpdateData);
                }

            }
            $vendor_state = Vendor_State::where('vend_id', $vend_id)->first();
            // Return success response with the updated vend_id
            return response()->json([
                'status' => true,
                'message' => 'Vendor profile updated successfully',
                'data' => [
                    'vend_id' => $vendor->acc_id, // Return the vend_id after update
                    'vendor_state' => $vendor_state ?? []
                ]
            ], 200); // OK    

        } catch (\Exception $e) {
            // Log the exception message for debugging (optional)
            \Log::error('Error creating vendor profile: ' . $e->getMessage());

            // Return 500 Internal Server Error
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the profile. Please try again later.',
                'data' => []
            ], 500); // Internal Server Error
        }
    }





    //--------------------------------------------------------------------------------------------------------------
    public static function setFileStateById(Request $request)
    {
        // Getting the basic inputs
        $acc_id = $request->input('acc_id');
        $id = $request->input('id');
        $upload_date = $request->input('upload_date');
        // Retrieving the 'account', 'profile' and 'file_expiry_data' arrays from the request
        $account = $request->input('account');
        $profile = $request->input('profile');
        $file_expiry_data = $request->input('file_expiry_data');

        // Arrays to hold processed data
        $accountFiles = [];
        $profileFiles = [];
        $allAccMediaIds = []; // New array to store all acc_media_id

        // Process account files
        if ($account && is_array($account)) {
            foreach ($account as $key => $value) {
                // Ensure we only process entries that have 'uploaded' and 'url'
                if (isset($value['uploaded']) && isset($value['url'])) {
                    // Extract the file name from the key
                    $fileName = $key;
                    $fileUrl = $value['url'];
                    $isUploaded = $value['uploaded'];
                    $media_type = $value['media_type'];
                    $file_size = $value['file_size'];

                    // Initialize expiry_date to null by default
                    $expiry_date = null;

                    // Check if file_expiry_data is set and not null
                    if ($file_expiry_data) {
                        // Add auth_person_expiry_date if the file name contains 'Auth_Person_Front_' or 'Auth_Person_Back_'
                        if (strpos($fileName, 'Auth_Person_Front_') === 0 || strpos($fileName, 'Auth_Person_Back_') === 0) {
                            if (isset($file_expiry_data['auth_person_expiry_date'])) {
                                $expiry_date = $file_expiry_data['auth_person_expiry_date'];
                            }
                        }

                        // Add em_expiry_date if the file name contains 'Em_Front_' or 'Em_Back_'
                        elseif (strpos($fileName, 'Em_Front_') === 0 || strpos($fileName, 'Em_Back_') === 0) {
                            if (isset($file_expiry_data['em_expiry_date'])) {
                                $expiry_date = $file_expiry_data['em_expiry_date'];
                            }
                        }
                        // Add passport_expiry_date if the file name contains 'Passport_'
                        elseif (strpos($fileName, 'Passport_') === 0) {
                            if (isset($file_expiry_data['passport_expiry_date'])) {
                                $expiry_date = $file_expiry_data['passport_expiry_date'];
                            }
                        }
                    }

                    // Generate a 5-digit random number
                    $randomFiveDigit = rand(10000, 99999);

                    // Generate a unique acc_media_id by including acc_id, current time, file name, and a random number
                    $acc_media_id = Hash::make($acc_id . now() . 'vendor' . $fileName . $randomFiveDigit . uniqid('', true));

                    // Store the processed data in the accountFiles array
                    $accountFiles[] = [
                        'vend_id' => $id,
                        'acc_id' => $acc_id,
                        'acc_media_id' => $acc_media_id,
                        'file_name' => $fileName,
                        'file_path' => $fileUrl,
                        'uploaded' => $isUploaded,
                        'media_type' => $media_type,
                        'file_size' => $file_size,
                        'upload_date' => $upload_date,
                        'expiry_date' => $expiry_date,
                        'created_at' => now(),
                        'updated_at' => now(),
                        // Add expiry_date to the account file data
                    ];

                    // Add the acc_media_id to the allAccMediaIds array
                    $allAccMediaIds[] = $acc_media_id;
                }
            }
        }

        // Process profile files
        if ($profile && is_array($profile)) {
            foreach ($profile as $key => $value) {
                // Ensure we only process entries that have 'uploaded' and 'url'
                if (isset($value['uploaded']) && isset($value['url'])) {
                    // Extract the file name from the key
                    $fileName = $key;
                    $fileUrl = $value['url'];
                    $isUploaded = $value['uploaded'];
                    $media_type = $value['media_type'];
                    $file_size = $value['file_size'];

                    // Initialize expiry_date to null by default
                    $expiry_date = null;

                    // Check if file_expiry_data is set and not null
                    if ($file_expiry_data) {
                        // Add proof_expiry_date if the file name contains 'Proof_'
                        if (strpos($fileName, 'Proof_') === 0) {
                            if (isset($file_expiry_data['proof_expiry_date'])) {
                                $expiry_date = $file_expiry_data['proof_expiry_date'];
                            }
                        }
                        // Add tax_expiry_date if the file name contains 'Tax_'
                        elseif (strpos($fileName, 'Tax_') === 0) {
                            if (isset($file_expiry_data['tax_expiry_date'])) {
                                $expiry_date = $file_expiry_data['tax_expiry_date'];
                            }
                        }
                        // Add trad_expiry_date if the file name contains 'Trad_'
                        elseif (strpos($fileName, 'Trad_') === 0) {
                            if (isset($file_expiry_data['trad_expiry_date'])) {
                                $expiry_date = $file_expiry_data['trad_expiry_date'];
                            }
                        }
                    }

                    // Generate a 5-digit random number
                    $randomFiveDigit = rand(10000, 99999);

                    // Generate a unique acc_media_id by including acc_id, current time, file name, and a random number
                    $acc_media_id = Hash::make($acc_id . now() . 'vendor' . $fileName . $randomFiveDigit . uniqid('', true));

                    // Store the processed data in the profileFiles array
                    $profileFiles[] = [
                        'vend_id' => $id,
                        'acc_id' => $acc_id,
                        'acc_media_id' => $acc_media_id,
                        'file_name' => $fileName,
                        'file_path' => $fileUrl,
                        'uploaded' => $isUploaded,
                        'media_type' => $media_type,
                        'file_size' => $file_size,
                        'upload_date' => $upload_date,
                        'expiry_date' => $expiry_date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Add the acc_media_id to the allAccMediaIds array
                    $allAccMediaIds[] = $acc_media_id;
                }
            }
        }

        try {
            // Save all account and profile files into the database using AccountsMedia model
            foreach ($accountFiles as $fileData) {
                $accountMedia = AccountsMedia::where('file_name', $fileData['file_name'])
                    ->where('vend_id', $id)->first();

                // Initialize $deletedFile as false
                $deletedFile = false;

                if ($accountMedia) {
                    $deletedFile = self::deleteFileByUrl($accountMedia->file_path);
                }

                if ($deletedFile) {
                    $accountMedia->delete();
                }

                AccountsMedia::create($fileData);
            }

            foreach ($profileFiles as $fileData) {
                $accountMedia = AccountsMedia::where('file_name', $fileData['file_name'])
                    ->where('vend_id', $id)->first();

                // Initialize $deletedFile as false
                $deletedFile = false;

                if ($accountMedia) {
                    $deletedFile = self::deleteFileByUrl($accountMedia->file_path);
                }

                if ($deletedFile) {
                    $accountMedia->delete();
                }

                AccountsMedia::create($fileData);
            }



            // Log the array of acc_media_ids
            //Log::info('Generated acc_media_ids: ' . json_encode($allAccMediaIds));
            Vendor::where('vend_id', $id)->update(['files_id_array' => json_encode($allAccMediaIds)]);
            return response()->json([
                'message' => 'Files successfully saved',
                'status' => true,
                'data' => []
            ], 200);

        } catch (\Exception $e) {
            // Log the error and return a failure response
            Log::error('Error saving files: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save files',
                'status' => false,
                'data' => []
            ], 500);
        }
    }
    public static function deleteFileByUrl($url)
    {
        // Extract the file path from the URL
        $filePath = parse_url($url, PHP_URL_PATH);

        // In case your files are stored in storage/app/public, you may need to prepend the correct path.
        $filePath = public_path() . $filePath;  // Use public_path() for public files

        // Check if the file exists
        if (File::exists($filePath)) {
            // Delete the file
            File::delete($filePath);
            return response()->json(['message' => 'File deleted successfully.'], 200);
        } else {
            return response()->json(['message' => 'File not found.'], 404);
        }
    }

    public static function getAccountGarageProfileById(Request $request)
    {
        // Validate that acc_id is present and is a valid integer

        // Fetch garages based on the account ID and status (true/false)
        $vendors = Vendor::query()->with([
            'files',
        ])->where('acc_id', $request->acc_id);


        // Check if any records were found
        if ($vendors->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No garage profiles found for this account.',
                'data' => [],
            ], 200);
        }

        // Return the response with status 200
        return response()->json([
            'status' => true,
            'message' => 'Garage profiles retrieved successfully.',
            'data' => $vendors,
        ], 200);
    }

    //--------------------------------------------------------------------------------------

    //--------------------------------------------------------------------------------------

    public static function getAccountVendorProfileById(Request $request)
    {
        $vendorQuery = Vendor::query()->with('files')->where('acc_id', $request->acc_id);

        // $account = Account::where('acc_id', $request->acc_id)->first();
        // $accountInfo = [
        //  'email' => $account->email,
        //  'phone' => $account->phone,
        // ];
        // $decryptedAccountData = EncryptionServiceConnections::decryptData($accountInfo);

        // If gra_id is provided, order by gra_id matching the requested value (put matches at the top)
        if ($request->has('vend_id') && $request->input('vend_id') !== null) {
            $vendId = $request->input('vend_id');  // This is the hashed gra_id from the request
            // Sort first by whether gra_id matches the requested hashed value (true matches first)
            $vendorQuery->orderByRaw("IF(vend_id = ?, 0, 1)", [$vendId]);
        }

        $vendor = $vendorQuery->get(); // Get the results from the query builder


        // Check if the vendor collection is empty
        if (is_null($vendorQuery)) {
            return response()->json([
                'status' => false,
                'message' => 'No vendor profiles found for this account.',
                'data' => [],
            ], 200);
        }

        // Return the response with status 200
        return response()->json([
            'status' => true,
            'message' => 'Vendor profiles retrieved successfully.',
            'data' => $vendor,
            // You can include the account info here if needed
        ], 200);
    }



    public static function getPartVendorProfileById(Request $request)
    {
        try {
            // Normalize common alt keys from query strings
            if ($request->has('per-page')) {
                $request->merge(['per_page' => $request->query('per-page')]);
            }
            if ($request->has('vend-id')) {
                $request->merge(['vend_id' => $request->query('vend-id')]);
            }

            // Validate
            $validator = Validator::make($request->all(), [
                'vend_id' => ['required', 'string'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
                'page' => ['nullable', 'integer', 'min:1'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            $vendorId = (string) $data['vend_id'];
            $perPage = isset($data['per_page']) ? (int) $data['per_page'] : 15;
            $page = isset($data['page']) ? (int) $data['page'] : 1;

            // Clamp values
            $perPage = max(1, min($perPage, 200));
            $page = max(1, $page);

            $query = Part::query()
                ->where('vend_id', $vendorId)
                ->with([
                    'inventory',
                    'vendor',
                    'impressions',
                    'image',
                    'partCategory',
                    'partName',
                    'make',
                    'model',
                    'notification', // keep this; we'll split into 'account_notification' + filtered 'notification'
                    // 'account_notification' <-- do NOT eager-load; we'll return a computed array instead
                ])
                ->orderBy('id', 'asc');

            $parts = $query->paginate($perPage, ['*'], 'page', $page);

            // If requested page is beyond last page, serve the last page instead
            $last = $parts->lastPage();
            if ($last > 0 && $page > $last) {
                $page = $last;
                $parts = $query->paginate($perPage, ['*'], 'page', $page);
            }

            // Transform each item:
            // - account_notification: notifications whose type contains "Account"
            // - notification: all other notifications (exclude the above)
            $items = collect($parts->items())->map(function ($part) {
                $notifs = $part->relationLoaded('notification')
                    ? collect($part->getRelation('notification'))
                    : collect();

                $isAccount = function ($n) {
                    $type = (string) ($n->type ?? '');
                    return stripos($type, 'account') !== false; // case-insensitive "contains"
                };

                $accountNotifs = $notifs->filter($isAccount)
                    ->values()
                    ->map(fn($n) => $n->toArray())
                    ->all();

                $otherNotifs = $notifs->reject($isAccount)
                    ->values()
                    ->map(fn($n) => $n->toArray())
                    ->all();

                $arr = $part->toArray();
                $arr['account_notification'] = $accountNotifs; // computed array
                $arr['notification'] = $otherNotifs;   // filtered to exclude "Account" types

                return $arr;
            });

            // Page helpers
            $current = $parts->currentPage();
            $lastPage = $parts->lastPage();
            $nextPage = $current < $lastPage ? $current + 1 : null;
            $prevPage = $current > 1 ? $current - 1 : null;

            return response()->json([
                'status' => true,
                'vendor_id' => $vendorId,
                'per_page' => $perPage,
                'page' => $current,
                'next_page' => $nextPage,
                'previous_page' => $prevPage,
                'next_page_url' => $parts->nextPageUrl(),
                'previous_page_url' => $parts->previousPageUrl(),
                'last_page' => $lastPage,
                'total' => $parts->total(),
                'count' => $items->count(),
                'data' => $items->all(),
            ], 200);

        } catch (\Throwable $e) {
            Log::error('getPartVendorProfileById failed', ['err' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error.'], 500);
        }
    }



}



