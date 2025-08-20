<?php

namespace App\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Garage;
use App\Models\Vendor;
use App\Models\Account;
use ZipArchive;

use App\Services\GarageServiceConnections;
use App\Services\VendorServiceConnections;
use Maatwebsite\Excel\Facades\Excel;


// For generating random strings
use App\Models\Catalog;  // Make sure you import the Catalog model



define('BASE_ACCOUNTS_PROFILE_URL', config('microservices.urls.accountProfileImage'));
define('BASE_VENDORS_IMAGE_URL', config('microservices.urls.uploadVendorImage'));
define('BASE_VENDORS_DOC_URL', config('microservices.urls.uploadVendorDoc'));

define('BASE_GARAGE_IMAGE_URL', config('microservices.urls.uploadGarageImage'));
define('BASE_GARAGE_DOC_URL', config('microservices.urls.uploadGarageDoc'));

class ImagesDocHelper
{
    public static function UploadImageOrDoc(Request $request)
    {
        set_time_limit(300);

        try {
            $uploadProtocol = json_decode($request->input('upload_protocol'), true);

            $protocolId = $uploadProtocol['id'];
            $protocolType = $uploadProtocol['type'];
            $protocolTime = $uploadProtocol['time'];
            $account_files = $request->file('account_files');  // Account files to be unzipped
            $profile_files = $request->file('profile_files');  // Profile files to be validated
            $file_expiry_data = json_decode($request->input('file_expiry_data'), true);

            $profileInfo = null;
            $uploadedFilesUrls = [];
            $uploadedFilesState = [
                'account' => [],
                'profile' => [],
            ];

            $baseUrl = "https://pick-a-part.ca/PickApart_CA/Files/";

            if ($protocolType === 'Garage') {

                $garage = Garage::where('gra_id', $protocolId)->first();

                if (!$garage) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid Protocol id, Garage record not found',
                        'data' => ['id' => $protocolId]
                    ], 404); // HTTP status code 404 for not found
                }

                $unzippedAccountPath = BASE_GARAGE_IMAGE_URL . 'Account_EX' . str_replace('/', '_', $garage->id) . '_' . now()->format('y_m_d_H_i_s') . '/';
                $unzippedProfilePath = BASE_GARAGE_IMAGE_URL . 'Profile_EX' . str_replace('/', '_', $garage->id) . '_' . now()->format('y_m_d_H_i_s') . '/';

                $account_fileStan = ['Profile_' . str_replace('/', '_', $garage->acc_id), 'Passport_' . str_replace('/', '_', $garage->acc_id), 'Em_Front_' . str_replace('/', '_', $garage->acc_id), 'Em_Back_' . str_replace('/', '_', $garage->acc_id), 'Auth_Person_Front_' . str_replace('/', '_', $garage->acc_id), 'Auth_Person_Back_' . str_replace('/', '_', $garage->acc_id)];
                $profile_filesStan = ['Proof_of_Location_' . str_replace('/', '_', $garage->acc_id), 'B_Profile_' . str_replace('/', '_', $garage->acc_id), 'Trad_' . str_replace('/', '_', $garage->acc_id), 'Proof_' . str_replace('/', '_', $garage->acc_id), 'Tax_' . str_replace('/', '_', $garage->acc_id), 'Registration_Certificate_' . str_replace('/', '_', $garage->acc_id), 'Tax_' . str_replace('/', '_', $garage->acc_id)];
                Log::info('$account_fileStan', $account_fileStan);
                // Unzip and check the number of files in 'account_files' if present
                if ($account_files) {
                    $unzippedFilesAccount = self::unzipFiles($account_files, $unzippedAccountPath);
                }

                // Unzip and check the number of files in 'profile_files' if present
                if ($profile_files) {
                    $unzippedFilesProfile = self::unzipFiles($profile_files, $unzippedProfilePath);
                }

                // Create folder if not exists in BASE_GARAGE_IMAGE_URL
                $folderPathImageAccount = BASE_GARAGE_IMAGE_URL . $garage->id . '/' . 'Account_' . str_replace('/', '_', $garage->acc_id) . '/Account/';
                if (!Storage::disk('hostinger')->exists($folderPathImageAccount)) {
                    Storage::disk('hostinger')->makeDirectory($folderPathImageAccount);
                }

                $folderPathDocAccount = BASE_GARAGE_IMAGE_URL . $garage->id . '/' . 'Profile_' . str_replace('/', '_', $garage->gra_id) . '/Profile/';
                if (!Storage::disk('hostinger')->exists($folderPathDocAccount)) {
                    Storage::disk('hostinger')->makeDirectory($folderPathDocAccount);
                }

                //-------------------------------------------------------------------------------
                $folderPathImageProfile = BASE_GARAGE_DOC_URL . $garage->id . '/' . 'Account_' . str_replace('/', '_', $garage->acc_id) . '/Account/';
                if (!Storage::disk('hostinger')->exists($folderPathImageProfile)) {
                    Storage::disk('hostinger')->makeDirectory($folderPathImageProfile);
                }

                $folderPathDocProfile = BASE_GARAGE_DOC_URL . $garage->id . '/' . 'Profile_' . str_replace('/', '_', $garage->gra_id) . '/Profile/';
                if (!Storage::disk('hostinger')->exists($folderPathDocProfile)) {
                    Storage::disk('hostinger')->makeDirectory($folderPathDocProfile);
                }

                // Process the account files if they exist
                if (isset($unzippedFilesAccount)) {
                    $uploadedFilesState['account'] = self::processAndUploadFiles($unzippedFilesAccount, $account_fileStan, $folderPathImageAccount, $folderPathDocAccount, $baseUrl);
                }

                // Process the profile files if they exist
                if (isset($unzippedFilesProfile)) {
                    $uploadedFilesState['profile'] = self::processAndUploadFiles($unzippedFilesProfile, $profile_filesStan, $folderPathImageProfile, $folderPathDocProfile, $baseUrl);
                }
                GarageServiceConnections::setGarageProfile($garage->acc_id, $garage->gra_id, $uploadedFilesState['account'], $uploadedFilesState['profile'], $file_expiry_data, $protocolTime);

            } elseif ($protocolType === 'Vendor') {
                $vendor = Vendor::where('vend_id', $protocolId)->first();

                if (!$vendor) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid Protocol id, Vendor record not found',
                        'data' => ['id' => $protocolId]
                    ], 404); // HTTP status code 404 for not found
                }

                $account_fileStan = ['Profile_' . str_replace('/', '_', $vendor->acc_id), 'Passport_' . str_replace('/', '_', $vendor->acc_id), 'Em_Front_' . str_replace('/', '_', $vendor->acc_id), 'Em_Back_' . str_replace('/', '_', $vendor->acc_id), 'Auth_Person_Front_' . str_replace('/', '_', $vendor->acc_id), 'Auth_Person_Back_' . str_replace('/', '_', $vendor->acc_id)];
                $profile_filesStan = ['B_Profile_' . str_replace('/', '_', $vendor->acc_id), 'Trad_' . str_replace('/', '_', $vendor->acc_id), 'Proof_' . str_replace('/', '_', $vendor->acc_id), 'Tax_' . str_replace('/', '_', $vendor->acc_id)];

                // Unzip and check the number of files in 'account_files' if present
                if ($account_files) {
                    $unzippedAccountPath = BASE_VENDORS_IMAGE_URL . $vendor->id . 'Account_EX' . str_replace('/', '_', $vendor->id) . '_' . now()->format('y_m_d_H_i_s') . '/';
                    $unzippedFilesAccount = self::unzipFiles($account_files, $unzippedAccountPath);
                }

                // Unzip and check the number of files in 'profile_files' if present
                if ($profile_files) {
                    $unzippedProfilePath = BASE_VENDORS_IMAGE_URL . $vendor->id . 'Profile_EX' . str_replace('/', '_', $vendor->id) . '_' . now()->format('y_m_d_H_i_s') . '/';
                    $unzippedFilesProfile = self::unzipFiles($profile_files, $unzippedProfilePath);
                }

                // Create folder if not exists in BASE_VENDORS_IMAGE_URL
                $folderPathImageAccount = BASE_VENDORS_IMAGE_URL . $vendor->id . 'Account_' . str_replace('/', '_', $vendor->acc_id) . '/Account/';
                if (!Storage::disk('hostinger')->exists($folderPathImageAccount)) {
                    Storage::disk('hostinger')->makeDirectory($folderPathImageAccount);
                }

                $folderPathImageProfile = BASE_VENDORS_IMAGE_URL . $vendor->id . 'Profile_' . str_replace('/', '_', $vendor->vend_id) . '/Profile/';
                if (!Storage::disk('hostinger')->exists($folderPathImageProfile)) {
                    Storage::disk('hostinger')->makeDirectory($folderPathImageProfile);
                }

                //-------------------------------------------------------------------------------
                $folderPathDocAccount = BASE_VENDORS_DOC_URL . $vendor->id . 'Account_' . str_replace('/', '_', $vendor->acc_id) . '/Account/';
                if (!Storage::disk('hostinger')->exists($folderPathDocAccount)) {
                    Storage::disk('hostinger')->makeDirectory($folderPathDocAccount);
                }

                $folderPathDocProfile = BASE_VENDORS_DOC_URL . $vendor->id . 'Profile_' . str_replace('/', '_', $vendor->vend_id) . '/Profile/';
                if (!Storage::disk('hostinger')->exists($folderPathDocProfile)) {
                    Storage::disk('hostinger')->makeDirectory($folderPathDocProfile);
                }

                // Process the account files if they exist
                if (isset($unzippedFilesAccount)) {
                    $uploadedFilesState['account'] = self::processAndUploadFiles($unzippedFilesAccount, $account_fileStan, $folderPathImageAccount, $folderPathDocAccount, $baseUrl);
                }

                // Process the profile files if they exist
                if (isset($unzippedFilesProfile)) {
                    $uploadedFilesState['profile'] = self::processAndUploadFiles($unzippedFilesProfile, $profile_filesStan, $folderPathImageProfile, $folderPathDocProfile, $baseUrl);
                }

                VendorServiceConnections::setVendorProfile($vendor->acc_id, $vendor->vend_id, $uploadedFilesState['account'], $uploadedFilesState['profile'], $file_expiry_data, $protocolTime);
            } elseif ($protocolType === 'STR') {
                $account = Account::where('acc_id', $protocolId)->first();

                if (!$account) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid Protocol id, Vendor record not found',
                        'data' => ['id' => $protocolId]
                    ], 404);
                }

                $sanitizedAccId = str_replace('/', '_', $account->acc_id);
                $sanitizedVendId = str_replace('/', '_', $account->vend_id);

                $account_fileStan = [
                    'Profile_' . $sanitizedAccId,
                    'Passport_' . $sanitizedAccId,
                    'Em_Front_' . $sanitizedAccId,
                    'Em_Back_' . $sanitizedAccId,
                    'Auth_Person_Front_' . $sanitizedAccId,
                    'Auth_Person_Back_' . $sanitizedAccId
                ];

                $profile_filesStan = [
                    'B_Profile_' . $sanitizedAccId,
                    'Trad_' . $sanitizedAccId,
                    'Proof_' . $sanitizedAccId,
                    'Tax_' . $sanitizedAccId
                ];

                // Unzip account files
                if ($account_files) {
                    $unzippedAccountPath = BASE_ACCOUNTS_PROFILE_URL . $account->id . 'Account_EX' . $sanitizedAccId . '_' . now()->format('y_m_d_H_i_s') . '/';
                    $unzippedFilesAccount = self::unzipFiles($account_files, $unzippedAccountPath);
                }

                // Unzip profile files
                if ($profile_files) {
                    $unzippedProfilePath = BASE_ACCOUNTS_PROFILE_URL . $account->id . 'Profile_EX' . $sanitizedAccId . '_' . now()->format('y_m_d_H_i_s') . '/';
                    $unzippedFilesProfile = self::unzipFiles($profile_files, $unzippedProfilePath);
                }

                // Create required folders
                $folderPathImageAccount = BASE_ACCOUNTS_PROFILE_URL . $account->id . 'Account_' . $sanitizedAccId . '/Account/';
                $folderPathDocAccount = $folderPathImageAccount;
                $folderPathImageProfile = BASE_ACCOUNTS_PROFILE_URL . $account->id . 'Profile_' . $sanitizedVendId . '/Profile/';
                $folderPathDocProfile = $folderPathImageProfile;

                foreach ([$folderPathImageAccount, $folderPathImageProfile, $folderPathDocAccount, $folderPathDocProfile] as $path) {
                    if (!Storage::disk('hostinger')->exists($path)) {
                        Storage::disk('hostinger')->makeDirectory($path);
                    }
                }

                // Process and upload account files
                if (isset($unzippedFilesAccount)) {
                    $uploadedFilesState['account'] = self::processAndUploadFiles(
                        $unzippedFilesAccount,
                        $account_fileStan,
                        $folderPathImageAccount,
                        $folderPathDocAccount,
                        $baseUrl
                    );

                    // ✅ Save full uploaded Profile_ URL into profile_url field
                    $profileKey = 'Profile_' . $sanitizedAccId;
                    if (isset($uploadedFilesState['account'][$profileKey])) {
                        $account->profile_url = $uploadedFilesState['account'][$profileKey]; // Full URL
                        $account->save();
                    }
                }

                // Process and upload profile files
                if (isset($unzippedFilesProfile)) {
                    $uploadedFilesState['profile'] = self::processAndUploadFiles(
                        $unzippedFilesProfile,
                        $profile_filesStan,
                        $folderPathImageProfile,
                        $folderPathDocProfile,
                        $baseUrl
                    );
                }
            }


            // Return JSON response with uploaded file URLs
            return response()->json([
                "status" => true,
                "message" => "Files uploaded successfully!",
                "uploaded_files_state" => $uploadedFilesState
            ], 200);

        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error('Exception during file upload: ' . $e->getMessage());

            // Handle the exception, you might want to provide a more detailed error response
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred during file upload: ' . $e->getMessage()
            ], 422);
        }
    }


    // New helper method to process and upload files
    private static function processAndUploadFiles($unzippedFiles, $fileStan, $imageBaseUrl, $docBaseUrl, $baseUrl)
    {
        $uploadedFilesState = [];

        // Iterate over the unzipped files
        foreach ($unzippedFiles as $unzippedFile) {
            $fileName = basename($unzippedFile);
            $fileBaseName = pathinfo($fileName, PATHINFO_FILENAME);  // Get the file name without the extension
            $fileExtension = strtolower(pathinfo($unzippedFile, PATHINFO_EXTENSION));  // Convert file extension to lowercase
            $filePath = null;
            $fileUploaded = false;
            $fileUrl = null;
            $fileSize = filesize($unzippedFile); // Get file size in bytes

            // Check if the file base name matches the expected base names
            if (in_array($fileBaseName, $fileStan)) {
                // Generate a new filename by appending the timestamp to the original file name
                $timestamp = time(); // Get the current Unix timestamp
                $newFileName = $fileBaseName . '_' . $timestamp . '.' . $fileExtension; // Create a new filename with the original file name and timestamp

                // Determine the file upload path based on the file extension
                if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                    // Upload to the image folder
                    $filePath = $imageBaseUrl . $newFileName;
                } elseif (in_array($fileExtension, ['pdf', 'doc', 'docx'])) {
                    // Upload to the document folder
                    $filePath = $docBaseUrl . $newFileName;
                }

                // If file path is determined (for image or document), proceed with upload
                if ($filePath) {
                    // Check if a file with the same name already exists, replace it if so
                    if (Storage::disk('hostinger')->exists($filePath)) {
                        // Delete the existing file to replace it
                        Storage::disk('hostinger')->delete($filePath);
                    }

                    // Upload the file, ensuring we use the new filename with the timestamp
                    Storage::disk('hostinger')->put($filePath, file_get_contents($unzippedFile));
                    $fileUploaded = true;
                    $fileUrl = $baseUrl . $filePath; // Create the full URL for the file
                }
            }

            // If the file was uploaded successfully, add it to the state
            if ($fileUploaded) {
                // Add the file upload status, URL, media type (file extension), and file size to the state array
                $uploadedFilesState[$fileBaseName] = [
                    'uploaded' => $fileUploaded,
                    'url' => $fileUrl,
                    'media_type' => $fileExtension, // Adding the file extension as media type
                    'file_size' => $fileSize // Adding file size in bytes
                ];
            }
        }

        return $uploadedFilesState;
    }





    private static function unzipFiles($file, $destinationDir = null)
    {
        $unzippedFiles = [];
        $zip = new ZipArchive();

        // If no destination directory is provided, set the default directory
        if ($destinationDir === null) {
            $destinationDir = storage_path('app/temp_zip/');
        }

        // Ensure the destination directory exists, if not, create it
        if (!is_dir($destinationDir)) {
            if (!mkdir($destinationDir, 0777, true)) {
                Log::error("Failed to create temp directory at $destinationDir");
                return [];
            }
        }




        // Check if the file is a zip file or an application/octet-stream type
        $mimeType = $file->getMimeType();

        // If it's an octet-stream, we assume it's a zip file and proceed to unzip
        if ($mimeType === 'application/octet-stream' || $mimeType === 'application/zip') {
            Log::info('Unzipping file: ' . $file->getRealPath());

            // Open the zip file
            if ($zip->open($file->getRealPath()) === true) {
                Log::info('Zip file opened successfully');

                // Extract the files to the destination directory
                $zip->extractTo($destinationDir);
                $zip->close();

                Log::info('Files extracted to: ' . $destinationDir);

                // Retrieve the files in the unzipped directory
                $unzippedFiles = glob($destinationDir . '*'); // Get all files in the directory

                // Log the names of all the unzipped files
                foreach ($unzippedFiles as $unzippedFile) {
                    Log::info('Unzipped file: ' . basename($unzippedFile)); // Log just the file name
                }

                // After unzipping, clean up by removing the zip file if needed (optional)
                // unlink($file->getRealPath());
            } else {
                Log::error("Failed to open the zip file: " . $file->getRealPath());
            }
        } else {
            Log::error("File is not a zip file. MIME type is: $mimeType");
        }

        // Return the unzipped files
        return $unzippedFiles;
    }


    public function decodeVin(Request $request)
    {
        // Validate the VIN parameter (ensure the 'vin' parameter is passed in the request)
        $vin = $request->input('vin');

        // Check if VIN is provided in the request
        if (empty($vin)) {
            return response()->json(['error' => 'VIN parameter is required.'], 400);
        }

        // Validate the VIN length (must be 17 characters)
        if (strlen($vin) !== 17) {
            return response()->json(['error' => 'Invalid VIN length. VIN must be 17 characters.'], 400);
        }

        // Step 1: Extract the year from the 10th character of the VIN
        $yearChar = strtoupper($vin[9]); // 10th character is at index 9 (0-based index)
        $year = $this->getYearFromChar($yearChar);

        // Step 2: Extract the model from the 4th to 8th characters of the VIN
        // $modelCode = substr($vin, 3, 4); // Get characters from position 4-8 (0-based index: 3-7)
        // $model = $this->getModelFromCode($vin );

        // Step 3: Extract the manufacturer prefix (first 3 characters) from the VIN
        $manufacturerPrefix = strtoupper(substr($vin, 1, 3)); // First 3 characters (WMI region)

        $make = $this->getManufacturerAndModelFromCode($vin); // Returns 'Honda'
        // Step 4: Get the manufacturer and region using the full WMI (first 3 characters)
        $region = $this->getRegionFromWmi($manufacturerPrefix);

        // Return the decoded year, model, manufacturer, and region
        return response()->json([
            'vin' => $vin,
            'year' => $year,
            'make' => $make['manufacturer'],
            'model' => $make['model'],
            'region' => $region
        ]);
    }

    // Helper function to map the year based on the 10th character (from 1980 to 2026)
    private function getYearFromChar($char)
    {
        // Mapping for 10th character to year (1980-2026)
        $yearMap = [
            'A' => 1980,
            'B' => 1981,
            'C' => 1982,
            'D' => 1983,
            'E' => 1984,
            'F' => 1985,
            'G' => 1986,
            'H' => 1987,
            'J' => 1988,
            'K' => 1989,
            'L' => 1990,
            'M' => 1991,
            'N' => 1992,
            'P' => 1993,
            'R' => 1994,
            'S' => 1995,
            'T' => 1996,
            'V' => 1997,
            'W' => 1998,
            'X' => 1999,
            'Y' => 2000,
            '1' => 2001,
            '2' => 2002,
            '3' => 2003,
            '4' => 2004,
            '5' => 2005,
            '6' => 2006,
            '7' => 2007,
            '8' => 2008,
            '9' => 2009,
            'A' => 2010,
            'B' => 2011,
            'C' => 2012,
            'D' => 2013,
            'E' => 2014,
            'F' => 2015,
            'G' => 2016,
            'H' => 2017,
            'J' => 2018,
            'K' => 2019,
            'L' => 2020,
            'M' => 2021,
            'N' => 2022,
            'P' => 2023,
            'R' => 2024,
            'S' => 2025,
            'T' => 2026
        ];

        return isset($yearMap[$char]) ? $yearMap[$char] : 'Unknown Year';
    }

    // Helper function to map the model code (4th-8th characters) to a vehicle model

    private function getModelFromCode($vin)
    {
        // Convert VIN to an array (assuming VIN is a string of at least 17 characters)
        $vinArray = str_split($vin);

        // Get the combined value from indices 2 and 3
        $vinSubset = $vinArray[1] . $vinArray[2]; // Concatenating index 1 and 2 (0-based index)

        // Retrieve all catalog entries (matching first 4 characters of VIN)
        $catalogEntries = Catalog::select('vin', 'model')
            ->where('vin', 'like', substr($vin, 0, 4) . '%')
            ->get();

        Log::info("vin " . json_encode($catalogEntries));

        $bestMatch = null;
        $highestMatchCount = 0;

        // Iterate through each catalog entry to compare the 2nd and 3rd characters of their VIN
        foreach ($catalogEntries as $catalogEntry) {
            // Extract the 2nd and 3rd characters of the current catalog entry's VIN
            $entryVinArray = str_split($catalogEntry->vin);
            $catalogVinSubset = $entryVinArray[1] . $entryVinArray[2];

            // Compare the catalog VIN subset with the input VIN subset
            $matchCount = 0;
            if ($vinSubset == $catalogVinSubset) {
                $matchCount++;
            }

            // Update best match if this entry has more matches or is the first match found
            if ($matchCount > $highestMatchCount) {
                $highestMatchCount = $matchCount;
                $bestMatch = $catalogEntry;
            }
        }

        // If we found a best match, return the model, otherwise return null or a fallback value
        return $bestMatch ? $bestMatch->model : null;
    }
    public function addCsv(Request $request)
    {
        // Validate the incoming data
        $validator = Validator::make($request->all(), [
            'vin' => 'required|string|unique:catalogs,vin',
            'manufacturer' => 'required|string',
            'model' => 'required|string',
        ]);

        // If validation fails, return an error response
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Retrieve the validated data
        $validated = $validator->validated();

        // Create a new catalog entry
        $catalog = Catalog::create([
            'vin' => $validated['vin'],
            'manufacturer' => $validated['manufacturer'],
            'model' => $validated['model'],
        ]);

        // Return a response, indicating success
        return response()->json([
            'message' => 'Catalog entry created successfully',
            'data' => $catalog
        ], 201);
    }

    private function getManufacturerAndModelFromCode($vin)
    {
        // Convert VIN to an array (assuming VIN is a string of at least 17 characters)
        $vinArray = str_split($vin);

        // Get the combined value from indices 2 and 3
        $vinSubset = $vinArray[1] . $vinArray[2]; // Concatenating index 2 and 3 (0-based index)

        // Retrieve all catalog entries
        $catalogEntries = Catalog::select('vin', 'manufacturer', 'model')
            ->where('vin', 'like', substr($vin, 0, 4) . '%')
            ->get();

        Log::info("vin " . json_encode($catalogEntries));
        $bestMatch = null;
        $highestMatchCount = 0;

        // Iterate through each catalog entry to compare the 2nd and 3rd characters of their VIN
        foreach ($catalogEntries as $catalogEntry) {
            // Extract the 2nd and 3rd characters of the current catalog entry's VIN
            $entryVinArray = str_split($catalogEntry->vin);
            $catalogVinSubset = $entryVinArray[1] . $entryVinArray[2];

            // Compare the catalog VIN subset with the input VIN subset
            $matchCount = 0;
            if ($vinSubset == $catalogVinSubset) {
                $matchCount++;
            }

            // Update best match if this entry has more matches or is the first match found
            if ($matchCount > $highestMatchCount) {
                $highestMatchCount = $matchCount;
                $bestMatch = $catalogEntry;
            }
        }

        // If we found a best match, return the model and manufacturer as an array, otherwise return null
        return $bestMatch ? [
            'model' => $bestMatch->model,
            'manufacturer' => $bestMatch->manufacturer
        ] : null;
    }



    // Helper function to map the region and manufacturer from the WMI (first 3 characters)
    private function getRegionFromWmi($wmi)
    {
        // Define the region mappings based on the first 2 or 3 characters of the WMI
        $regionMap = [
            // Africa (WMI A-C)
            'AA' => 'South Africa',
            'AB' => 'South Africa',
            'AC' => 'South Africa',

            // Asia (WMI J-R)
            'J' => 'Japan',
            'K' => 'South Korea',
            'L' => 'China',
            'MA' => 'India',
            'MB' => 'India',
            'MC' => 'India',
            'MD' => 'India',
            'ME' => 'India',
            'MF' => 'Indonesia',
            'MG' => 'Indonesia',
            'MH' => 'Indonesia',
            'MI' => 'Indonesia',
            'MJ' => 'Indonesia',
            'MK' => 'Indonesia',
            'ML' => 'Thailand',
            'MM' => 'Thailand',
            'MN' => 'Thailand',
            'MO' => 'Thailand',
            'MP' => 'Thailand',
            'MQ' => 'Thailand',
            'MR' => 'Thailand',
            'MS' => 'Myanmar',
            'NL' => 'Turkey',
            'NM' => 'Turkey',
            'NN' => 'Turkey',
            'NO' => 'Turkey',
            'PA' => 'Philippines',
            'PB' => 'Philippines',
            'PC' => 'Philippines',
            'PD' => 'Philippines',
            'PE' => 'Philippines',
            'PL' => 'Malaysia',
            'PM' => 'Malaysia',
            'PN' => 'Malaysia',
            'PO' => 'Malaysia',
            'PR' => 'Malaysia',
            'RF' => 'Taiwan',
            'RG' => 'Taiwan',

            // Europe (WMI S-Z)
            'SA' => 'United Kingdom',
            'SB' => 'United Kingdom',
            'SC' => 'United Kingdom',
            'SD' => 'United Kingdom',
            'SE' => 'United Kingdom',
            'SF' => 'United Kingdom',
            'SG' => 'United Kingdom',
            'SH' => 'United Kingdom',
            'SI' => 'United Kingdom',
            'SJ' => 'United Kingdom',
            'SK' => 'United Kingdom',
            'SL' => 'United Kingdom',
            'SM' => 'United Kingdom',

            'SN' => 'Germany',
            'SO' => 'Germany',
            'SP' => 'Germany',
            'SQ' => 'Germany',
            'SR' => 'Germany',
            'SS' => 'Germany',
            'ST' => 'Germany',
            'SU' => 'Germany',
            'SV' => 'Germany',
            'SW' => 'Germany',
            'SX' => 'Germany',
            'SY' => 'Germany',
            'SZ' => 'Germany',

            'SU' => 'Poland',
            'SV' => 'Poland',
            'SW' => 'Poland',
            'SX' => 'Poland',
            'SY' => 'Poland',
            'SZ' => 'Poland',
            'TA' => 'Switzerland',
            'TB' => 'Switzerland',
            'TC' => 'Switzerland',
            'TD' => 'Switzerland',
            'TE' => 'Switzerland',

            'TJ' => 'Czech Republic',
            'TK' => 'Czech Republic',
            'TL' => 'Czech Republic',
            'TM' => 'Czech Republic',
            'TN' => 'Czech Republic',
            'TO' => 'Czech Republic',
            'TP' => 'Czech Republic',

            'TR' => 'Hungary',
            'TS' => 'Hungary',
            'TT' => 'Hungary',
            'TU' => 'Hungary',

            'TW' => 'Portugal',
            'TX' => 'Portugal',
            'TY' => 'Portugal',
            'TZ' => 'Portugal',

            'VA' => 'Austria',
            'VB' => 'Austria',
            'VC' => 'Austria',
            'VD' => 'Austria',

            'VF' => 'France',
            'VG' => 'France',
            'VH' => 'France',
            'VI' => 'France',
            'VJ' => 'France',
            'VK' => 'France',
            'VL' => 'France',
            'VM' => 'France',

            'VS' => 'Spain',
            'VT' => 'Spain',
            'VU' => 'Spain',
            'VV' => 'Spain',

            'VX' => 'Yugoslavia',
            'VY' => 'Yugoslavia',
            'VZ' => 'Yugoslavia',
            'V3' => 'Yugoslavia',

            'XL' => 'The Netherlands',
            'XM' => 'The Netherlands',

            'XS' => 'USSR',
            'XT' => 'USSR',
            'XU' => 'USSR',
            'XV' => 'USSR',
            'XW' => 'USSR',
            'XX' => 'USSR',

            'X0' => 'Russia',

            'YA' => 'Belgium',
            'YB' => 'Belgium',
            'YC' => 'Belgium',
            'YD' => 'Belgium',

            'YF' => 'Finland',
            'YG' => 'Finland',
            'YH' => 'Finland',
            'YI' => 'Finland',
            'YJ' => 'Finland',

            'YS' => 'Sweden',
            'YT' => 'Sweden',
            'YU' => 'Sweden',
            'YV' => 'Sweden',
            'YW' => 'Sweden',

            'ZA' => 'Italy',
            'ZB' => 'Italy',
            'ZC' => 'Italy',
            'ZD' => 'Italy',
            'ZE' => 'Italy',
            'ZF' => 'Italy',

            // North America (WMI 1-5)
            '1' => 'United States',
            '4' => 'United States',
            '5' => 'United States',
            '2' => 'Canada',
            '3' => 'Mexico',

            // Oceania (WMI 6-7)
            '6A' => 'Australia',
            '6B' => 'Australia',
            '6C' => 'Australia',
            '6D' => 'Australia',
            '6E' => 'Australia',
            '6F' => 'Australia',
            '6G' => 'Australia',
            '6H' => 'Australia',
            '6I' => 'Australia',
            '6J' => 'Australia',
            '6K' => 'Australia',
            '6L' => 'Australia',
            '6M' => 'Australia',
            '6N' => 'Australia',
            '6O' => 'Australia',
            '6P' => 'Australia',
            '6Q' => 'Australia',
            '6R' => 'Australia',
            '6S' => 'Australia',
            '6T' => 'Australia',
            '6U' => 'Australia',
            '6V' => 'Australia',
            '6W' => 'Australia',

            '7A' => 'New Zealand',
            '7B' => 'New Zealand',
            '7C' => 'New Zealand',
            '7D' => 'New Zealand',
            '7E' => 'New Zealand',

            // South America (WMI 8-9)
            '8A' => 'Argentina',
            '8B' => 'Argentina',
            '8C' => 'Argentina',
            '8D' => 'Argentina',
            '8E' => 'Argentina',
            '8F' => 'Chile',
            '8G' => 'Chile',
            '8H' => 'Chile',
            '8I' => 'Chile',
            '8J' => 'Chile',

            '8L' => 'Ecuador',
            '8M' => 'Ecuador',
            '8N' => 'Ecuador',
            '8O' => 'Ecuador',

            '8X' => 'Venezuela',
            '8Y' => 'Venezuela',
            '8Z' => 'Venezuela'
        ];

        return $regionMap[$wmi] ?? 'Unknown Region';
    }
    private function getManufacturerFromWmi($vin)
    {
        // Capitalize the WMI part of the VIN to ensure case-insensitive matching


        // Define the manufacturer mappings based on the first 2 or 3 characters of the WMI
        $manufacturerMap = [
            // Example of common manufacturer mappings
            'HG' => 'Honda',
            'C4' => 'Chrysler',
            'FA' => 'Ford',
            'T1' => 'Toyota',
            'UX' => 'BMW',
            'HM' => 'Honda (Japan)',
            'ND' => 'Kia',
            'A1' => 'Audi',
            'G1' => 'Chevrolet',
            'YV' => 'Mazda',
            'MC' => 'Toyota (China)',

            // Additional manufacturers
            '1FA' => 'Ford', // Extended codes like 1FA, 1FB, etc.
            '2FA' => 'Ford ',
            '3FA' => 'Ford ',
            '4T1' => 'Toyota',
            '5UX' => 'BMW',
            'JHM' => 'Honda ',
            'KND' => 'Kia',
            'WA1' => 'Audi',
            '1G1' => 'Chevrolet',
            '1YV' => 'Mazda',
            'TMC' => 'Toyota ',
            'WVW' => 'Volkswagen',
            'SCB' => 'Bentley',
            'VLF' => 'Fisker',
            '1N4' => 'Nissan',
            'WAUZ' => 'Audi',
            '3W' => 'Ford ',

            // Add more manufacturer mappings as needed
        ];

        // Return the manufacturer name based on the WMI (first 3 characters of the VIN)
        return $manufacturerMap[$vin] ?? 'Unknown Manufacturer';
    }

    public function uploadCatalog(Request $request)
    {
        try {
            // Get the file name from the request (e.g., 'catalogs/sample.xlsx')
            $fileName = $request->input('file_name');  // File name expected from request

            // Define the storage disk as 'public' (files are stored in storage/app/public)
            $storageDisk = 'public';  // Use 'public' disk for files stored in storage/app/public

            // Check if the file exists in the public storage directory
            if (!Storage::disk($storageDisk)->exists($fileName)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The file does not exist at the specified location.'
                ], 404);
            }

            // Retrieve the file from storage (absolute path)
            $filePath = Storage::disk($storageDisk)->path($fileName);  // Get the absolute file path

            // Log the file path accessed


            // Import the Excel data using the absolute file path
            Excel::import(new Catalog, $filePath);

            // Generate the public URL of the file
            $fileUrl = Storage::disk($storageDisk)->url($fileName);

            // Return success response with the file URL
            return response()->json([
                'status' => 'success',
                'message' => 'Catalog data uploaded successfully!',
                'file_url' => $fileUrl // Provide the public URL of the file
            ]);
        } catch (\Exception $e) {
            // Return error response if any exception occurs
            return response()->json([
                'status' => 'error',
                'message' => 'There was an error processing the file: ' . $e->getMessage()
            ], 500);
        }
    }


}
