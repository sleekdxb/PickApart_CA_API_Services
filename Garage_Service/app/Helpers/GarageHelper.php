<?php

namespace App\Helpers;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Services\EncryptionServiceConnections;
use App\Models\Account;
use App\Models\AccountsMedia;
use App\Models\Garage;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Services\MailingServiceConnections;
use App\Services\NotificationServiceConnections;


define('PER_PAGE', 10);
class GarageHelper
{
    public static function createProfileById(Request $request)
    {
        try {
            // Step 2: Validate that acc_id exists in the vendors table
            $acc_id = $request->input('acc_id');  // Get acc_id from query parameter (GET request)

            // Step 2: Validate that acc_id is present in the request
            if (empty($acc_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Account ID is required.',
                ], 400); // Bad Request
            }

            // Step 5: Find the vendor by acc_id
            $garage = Garage::where('acc_id', $acc_id)->get();

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

            if ($request->has('account_type') && $request->input('account_type') !== null) {
                $updateData['account_type'] = $request->input('account_type');
            }

            if ($request->has('email') && $request->input('email') !== null) {
                $updateData['email'] = $request->input('email');
            }

            if ($request->has('garage_email') && $request->input('garage_email') !== null) {
                $updateData['garage_email'] = $request->input('garage_email');
            }

            if ($request->has('business_phone') && $request->input('business_phone') !== null) {
                $updateData['business_phone'] = $request->input('business_phone');
            }

            if ($request->has('garage_name') && $request->input('garage_name') !== null) {
                $updateData['garage_name'] = $request->input('garage_name');
            }

            if ($request->has('garage_location') && $request->input('garage_location') !== null) {
                $updateData['garage_location'] = $request->input('garage_location');
            }

            if ($request->has('lat') && $request->input('lat') !== null) {
                $updateData['lat'] = $request->input('lat');
            }

            if ($request->has('long') && $request->input('long') !== null) {
                $updateData['long'] = $request->input('long');
            }

            if ($request->has('country') && $request->input('country') !== null) {
                $updateData['country'] = $request->input('country');
            }

            if ($request->has('address') && $request->input('address') !== null) {
                $updateData['address'] = $request->input('address');
            }

            if ($request->has('location') && $request->input('location') !== null) {
                $updateData['location'] = $request->input('location');
            }

            if ($request->has('iAgreeToTerms') && $request->input('iAgreeToTerms') !== null) {
                $iAgreeToTerms = $request->input('iAgreeToTerms');
            }

            // Add these values to $updateData
            $updateData['gra_id'] = Hash::make(now());
            $updateData['acc_id'] = $acc_id;
            $updateData['updated_at'] = now();
            $updateData['created_at'] = now();
            $updateData['iAgreeToTerms'] = $iAgreeToTerms;

            // Only update the vendor if there's anything to update
            if (!empty($updateData)) {
                $accountDataEncryption =
                    [
                        'account_type' => $updateData['account_type'] ?? null,
                        'phone' => $updateData['phone'] ?? null
                    ];

                $onEncryptionSuccess = EncryptionServiceConnections::encryptData($accountDataEncryption);

                // Log::info($onEncryptionSuccess);


                $encryptionUpdateDataAccount = [
                    'firstName' => $request->input('firstName') ?? null,
                    'lastName' => $request->input('lastName') ?? null,
                    'account_type' => $onEncryptionSuccess['data']['account_type'] ?? null,
                    'phone' => $onEncryptionSuccess['data']['phone'] ?? null,
                    'updated_at' => now()
                ];



                // Update the vendor record with the encrypted data
                Account::where('acc_id', $acc_id)->update($encryptionUpdateDataAccount);

                // Create a new garage record with the updateData
                $success = Garage::create($updateData);
                if ($success) {
                    $mailingData = [
                        'sender_id' => 'SYSTEM',
                        'recipient_id' => $acc_id,
                        'email' => $request->input('email'),
                        'name' => $request->input('garage_name'),
                        'message' => 'Please ensure all required documents are uploaded to enjoy wholesale prices.',
                        'subject' => 'Your Garage Account is Set Up',
                        'upper_info' => 'Your garage profile has been successfully created on Pickapart.ae',
                        'but_info' => 'You can manage your garage here:',
                        'data' => '',
                        'account_type' => 'Garage'
                    ];

                    MailingServiceConnections::sendEmail($mailingData);
                }

            }

            // Return success response with the updated gra_id
            return response()->json([
                'status' => true,
                'message' => 'Garage profile updated successfully',
                'data' => [
                    'gra_id' => $updateData['gra_id'], // Return the gra_id after update
                ]
            ], 200); // OK    

        } catch (\Exception $e) {
            // Log the exception message for debugging (optional)
            \Log::error('Error creating Garage profile: ' . $e->getMessage());

            // Return 500 Internal Server Error
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the profile. Please try again later.',
                'data' => []
            ], 500); // Internal Server Error
        }
    }







    public static function updateProfileById(Request $request)
    {
        try {
            // Step 2: Validate that gra_id exists in the vendors table
            $gra_id = $request->input('gra_id');  // Get gra_id from query parameter (GET request)

            // Step 5: Find the garage by gra_id
            $garage = Garage::where('gra_id', $gra_id)->first();

            // Check if the garage was found
            if (!$garage) {
                return response()->json([
                    'status' => false,
                    'message' => 'Garage not found',
                    'data' => []
                ], 404); // Not Found
            }

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

            if ($request->has('account_type') && $request->input('account_type') !== null) {
                $updateData['account_type'] = $request->input('account_type');
            }

            if ($request->has('email') && $request->input('email') !== null) {
                $updateData['email'] = $request->input('email');
            }

            if ($request->has('garage_email') && $request->input('garage_email') !== null) {
                $updateData['garage_email'] = $request->input('garage_email');
            }

            if ($request->has('business_phone') && $request->input('business_phone') !== null) {
                $updateData['business_phone'] = $request->input('business_phone');
            }

            if ($request->has('garage_name') && $request->input('garage_name') !== null) {
                $updateData['garage_name'] = $request->input('garage_name');
            }

            if ($request->has('garage_location') && $request->input('garage_location') !== null) {
                $updateData['garage_location'] = $request->input('garage_location');
            }

            if ($request->has('lat') && $request->input('lat') !== null) {
                $updateData['lat'] = $request->input('lat');
            }

            if ($request->has('long') && $request->input('long') !== null) {
                $updateData['long'] = $request->input('long');
            }

            if ($request->has('country') && $request->input('country') !== null) {
                $updateData['country'] = $request->input('country');
            }

            if ($request->has('location') && $request->input('location') !== null) {
                $updateData['location'] = $request->input('location');
            }

            if ($request->has('iAgreeToTerms') && $request->input('iAgreeToTerms') !== null) {
                $iAgreeToTerms = $request->input('iAgreeToTerms');
            }

            // Only update the garage if there's anything to update
            if (!empty($updateData)) {
                // Create an instance of the EncryptionServiceConnections class
                // Encrypt the update data
                $onEncryptionSuccess = EncryptionServiceConnections::encryptData($updateData);

                // Log::info($onEncryptionSuccess);

                $encryptionUpdateDataAccount = [
                    'firstName' => $request->input('firstName') ?? null,
                    'lastName' => $request->input('lastName') ?? null,
                    'account_type' => $onEncryptionSuccess['data']['account_type'] ?? null,
                    'phone' => $onEncryptionSuccess['data']['phone'] ?? null,
                    'updated_at' => now()
                ];

                $encryptionUpdateData = [
                    'acc_id' => $garage->acc_id,
                    'garage_email' => $updateData['garage_email'] ?? null,
                    'business_phone' => $updateData['business_phone'] ?? '',
                    'long' => $updateData['long'] ?? null,
                    'lat' => $updateData['lat'] ?? null,
                    'garage_name' => $updateData['garage_name'] ?? null,
                    'garage_location' => $updateData['garage_location'] ?? null,
                    'country' => $updateData['country'] ?? null,
                    'location' => $updateData['location'] ?? null,
                    'iAgreeToTerms' => $iAgreeToTerms ?? false,
                    'updated_at' => now(),
                    'created_at' => now()
                ];

                Account::where('acc_id', $garage->acc_id)->update($encryptionUpdateDataAccount);
                Garage::where('gra_id', $garage->gra_id)->update($encryptionUpdateData);
            }

            $mailingData = [
                'sender_id' => 'SYSTEM',
                'recipient_id' => $garage->acc_id,
                'email' => $request->input('email'),
                'name' => $request->input('garage_name'),
                'message' => 'Your Pick-a-part.ca profile was recently updated. If you made these changes, no further action is required.',
                'subject' => 'Pick-a-part.ca Your Business Profile Information Was Updated',
                'upper_info' => 'Business Profile Update Notice',
                'but_info' => 'If you didn’t update your profile, please review your account activity and contact us immediately at support@pick-a-part.ca.',
                'data' => '',
                'account_type' => 'Garage'
            ];

            MailingServiceConnections::sendEmailGarage($mailingData);

            $notifyDataFiles = [
                'acc_id' => $garage->acc_id,
                'gra_id' => $garage->gra_id,
                'type' => 'Business Profile Update',
                'notifiable_id' => 'SYSTEM',
                'data' => [
                    'message' => 'Your Pick-a-part.ca profile was edited. If this wasn’t you, please contact support right away.',
                    'subject' => 'Pick-a-part.ca Your Business Profile Information Was Updated',
                    'name' => 'non',
                    'action' => 'non',
                ]
            ];
            NotificationServiceConnections::notifyGarage($notifyDataFiles);

            // Return success response with the updated gra_id
            return response()->json([
                'status' => true,
                'message' => 'Garage profile updated successfully',
                'data' => [
                    'gra_id' => $garage->gra_id, // Return the gra_id after update
                ]
            ], 200); // OK    

        } catch (\Exception $e) {
            // Log the exception message for debugging (optional)
            \Log::error('Error updating Garage profile: ' . $e->getMessage());

            // Return 500 Internal Server Error
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the profile. Please try again later.',
                'data' => []
            ], 500); // Internal Server Error
        }
    }


    //------------------------------------------------------------------------------------
    public static function setFileStateById(Request $request)
    {
        $acc_id = $request->input('acc_id');
        $id = $request->input('id');
        $upload_date = $request->input('upload_date');

        $account = $request->input('account') ?? [];
        $profile = $request->input('profile') ?? [];
        $file_expiry_data = $request->input('file_expiry_data');

        Log::info('Received File Upload Request', [
            'acc_id' => $acc_id,
            'gra_id' => $id,
            'upload_date' => $upload_date,
            'account_files' => $account,
            'profile_files' => $profile,
            'expiry_data' => $file_expiry_data
        ]);

        $accountFiles = [];
        $profileFiles = [];
        $allAccMediaIds = [];

        // --- Process Account Files ---
        if (!empty($account) && is_array($account)) {
            foreach ($account as $key => $value) {
                Log::debug("Inspecting account item", ['key' => $key, 'value' => $value]);

                // If value is a string, decode it into an array
                if (is_string($value)) {
                    $value = json_decode($value, true); // Decode JSON string to array
                    Log::debug("Decoded account item", ['key' => $key, 'decoded_value' => $value]);
                }

                // Skip if value is not an array
                if (!is_array($value)) {
                    Log::warning("Skipping non-array account item", ['key' => $key, 'value' => $value]);
                    continue;
                }

                if (!array_key_exists('uploaded', $value) || !isset($value['url'])) {
                    Log::warning("Skipping invalid account item (missing keys)", ['key' => $key, 'value' => $value]);
                    continue;
                }

                $fileName = $key;
                $fileUrl = $value['url'];
                $isUploaded = $value['uploaded'];
                $media_type = $value['media_type'] ?? null;
                $file_size = $value['file_size'] ?? null;
                $expiry_date = null;

                if ($file_expiry_data) {
                    if (strpos($fileName, 'B_Profile_') === 0 && isset($file_expiry_data['auth_person_expiry_date'])) {
                        $expiry_date = $file_expiry_data['auth_person_expiry_date'];
                    } elseif ((strpos($fileName, 'Em_Front_') === 0 || strpos($fileName, 'Em_Back_') === 0) && isset($file_expiry_data['em_expiry_date'])) {
                        $expiry_date = $file_expiry_data['em_expiry_date'];
                    } elseif (strpos($fileName, 'Passport_') === 0 && isset($file_expiry_data['passport_expiry_date'])) {
                        $expiry_date = $file_expiry_data['passport_expiry_date'];
                    }
                }

                $acc_media_id = (string) Hash::make($acc_id . now() . 'garage' . $fileName . rand(10000, 99999) . uniqid('', true));

                $fileData = [
                    'gra_id' => $id,
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

                $accountFiles[] = $fileData;
            }
        }

        // --- Process Profile Files ---
        if (!empty($profile) && is_array($profile)) {
            foreach ($profile as $key => $value) {
                Log::debug("Inspecting profile item", ['key' => $key, 'value' => $value]);

                // If value is a string, decode it into an array
                if (is_string($value)) {
                    $value = json_decode($value, true); // Decode JSON string to array
                    Log::debug("Decoded profile item", ['key' => $key, 'decoded_value' => $value]);
                }

                // Skip if value is not an array
                if (!is_array($value)) {
                    Log::warning("Skipping non-array profile item", ['key' => $key, 'value' => $value]);
                    continue;
                }

                if (!array_key_exists('uploaded', $value) || !isset($value['url'])) {
                    Log::warning("Skipping invalid profile item (missing keys)", ['key' => $key, 'value' => $value]);
                    continue;
                }

                $fileName = $key;
                $fileUrl = $value['url'];
                $isUploaded = $value['uploaded'];
                $media_type = $value['media_type'] ?? null;
                $file_size = $value['file_size'] ?? null;
                $expiry_date = null;

                if ($file_expiry_data) {
                    if (strpos($fileName, 'Registration_Certificate_') === 0 && isset($file_expiry_data['registration_certificate_expiry_data'])) {
                        $expiry_date = $file_expiry_data['registration_certificate_expiry_data'];
                    } elseif (strpos($fileName, 'Proof_of_Location_') === 0 && isset($file_expiry_data['proof_of_location_expiry_data'])) {
                        $expiry_date = $file_expiry_data['proof_of_location_expiry_data'];
                    } elseif (strpos($fileName, 'Trad_') === 0 && isset($file_expiry_data['trad_expiry_date'])) {
                        $expiry_date = $file_expiry_data['trad_expiry_date'];
                    }
                }

                $acc_media_id = (string) Hash::make($acc_id . now() . 'garage' . $fileName . rand(10000, 99999) . uniqid('', true));

                $fileData = [
                    'gra_id' => $id,
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

                $profileFiles[] = $fileData;
            }
        }

        try {
            // --- Save Account Files ---
            foreach ($accountFiles as $fileData) {
                Log::info('Saving Account File:', ['file_name' => $fileData['file_name']]);

                $existing = AccountsMedia::where('file_name', $fileData['file_name'])->where('gra_id', $id)->first();
                if ($existing && self::deleteFileByUrl($existing->file_path)) {
                    $existing->delete();
                }

                try {
                    $saved = AccountsMedia::create($fileData);
                    if ($saved && $saved->acc_media_id) {
                        $allAccMediaIds[] = (string) $saved->acc_media_id;
                        Log::info('[FileUpload] Account file saved', ['id' => $saved->id]);
                    }
                } catch (\Exception $e) {
                    Log::error('[FileUpload] Failed to save account file', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // --- Save Profile Files ---
            foreach ($profileFiles as $fileData) {
                Log::info('Saving Profile File:', ['file_name' => $fileData['file_name']]);

                $existing = AccountsMedia::where('file_name', $fileData['file_name'])->where('gra_id', $id)->first();
                if ($existing && self::deleteFileByUrl($existing->file_path)) {
                    $existing->delete();
                }

                try {
                    $saved = AccountsMedia::create($fileData);
                    if ($saved && $saved->acc_media_id) {
                        $allAccMediaIds[] = (string) $saved->acc_media_id;
                        Log::info('[FileUpload] Profile file saved', ['id' => $saved->id]);
                    }
                } catch (\Exception $e) {
                    Log::error('[FileUpload] Failed to save profile file', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // --- Update Garage Files ID Array ---
            if (!empty($allAccMediaIds)) {
                Garage::where('gra_id', $id)->update(['files_id_array' => json_encode($allAccMediaIds)]);
                Log::info('Garage files_id_array updated', ['gra_id' => $id, 'files_id_array' => $allAccMediaIds]);
            } else {
                Log::info('No media saved; skipping Garage update.', ['gra_id' => $id]);
            }

            return response()->json([
                'message' => 'Files successfully processed',
                'status' => true,
                'data' => []
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error saving files', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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

        $garageQuery = Garage::query()->with('files', 'notification')->where('acc_id', $request->acc_id);

        $account = Account::where('acc_id', $request->acc_id)->first();
        $accountInfo = [
            'email' => $account->email,
            'phone' => $account->phone,

        ];
        $decryptedAccountData = EncryptionServiceConnections::decryptData($accountInfo);

        // If gra_id is provided, order by gra_id matching the requested value (put matches at the top)
        if ($request->has('gra_id') && $request->input('gra_id') !== null) {
            $graId = $request->input('gra_id');  // This is the hashed gra_id from the request
            // Sort first by whether gra_id matches the requested hashed value (true matches first)
            $garageQuery->orderByRaw("IF(gra_id = ?, 0, 1)", [$graId]);
        }

        // Apply pagination to the query (if pagination is needed)
        $garage = $garageQuery->get(); // Get the results from the query builder

        if ($garage->isEmpty()) {
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
            'data' => [
                'garages' => $garage,
                'account' => [
                    'firstName' => $account->firstName,
                    'lastName' => $account->lastName,
                    'email' => $decryptedAccountData['data']['email'],
                    'phone' => $decryptedAccountData['data']['phone'],
                ],
            ],
        ], 200);
    }



}