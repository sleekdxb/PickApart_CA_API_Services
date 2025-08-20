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
use ZipArchive;
use App\Services\VendorServiceConnections;
use App\Services\PartServiceConnections;
use Maatwebsite\Excel\Facades\Excel;

define('BASE_PARTS_IMAGE_URL', config('microservices.urls.uploadPartsImage'));
define('BASE_PARTS_DOC_URL', config('microservices.urls.uploadPartsDoc'));

class PartsImagesHelper
{
    public static function UploadPartImage(Request $request)
    {
        set_time_limit(300); // Set execution time limit

        try {
            // Decode the upload protocol to get the vendor and part information
            $uploadProtocol = json_decode($request->input('upload_protocol'), true);
            $protocolVendorId = $uploadProtocol['vend_id'];
            $protocolInventoryId = $uploadProtocol['inve_id'];   // Vendor ID
            $protocolPartId = $uploadProtocol['part_id'];
            $part_images = $request->file('part_images');    // Account files to be unzipped

            $uploadedFilesUrls = [];
            $uploadedFilesState = [
                'part_images' => [],
            ];

            // Create a base URL
            $baseUrl = "https://pick-a-part.ca/PickApart_CA/Files/";

            // Define the folder structure based on vendor and part IDs
            $folderPath = BASE_PARTS_IMAGE_URL . str_replace('/', '_', $protocolVendorId) . '/' . str_replace('/', '_', $protocolInventoryId) . '/' . str_replace('/', '_', $protocolPartId) . '/';
            $folderPathDoc = BASE_PARTS_DOC_URL . str_replace('/', '_', $protocolVendorId) . '/' . str_replace('/', '_', $protocolInventoryId) . '/' . str_replace('/', '_', $protocolPartId) . '/';


            // Check if the folder exists, create it if it doesn't
            if (!Storage::disk('hostinger')->exists($folderPath)) {
                Storage::disk('hostinger')->makeDirectory($folderPath, 0775); // Ensure the folder is writable with appropriate permissions
                // Log::info("Created directory: $folderPath");
            }

            // Define a temporary folder for unzipped files
            $folderPathUnzip = BASE_PARTS_IMAGE_URL . str_replace('/', '_', $protocolVendorId) . '/' . str_replace('/', '_', $protocolInventoryId) . '/' . str_replace('/', '_', $protocolPartId) . '_' . now()->format('y_m_d_H_i_s') . '/';
            if (!Storage::disk('hostinger')->exists($folderPathUnzip)) {
                Storage::disk('hostinger')->makeDirectory($folderPathUnzip, 0775); // Ensure the unzip folder exists and is writable
                // Log::info("Created unzip directory: $folderPathUnzip");
            }

            // Unzip the part images (account files)
            $unzippedFiles = self::unzipFiles($part_images, $folderPathUnzip);

            // Process and upload the unzipped files
            $uploadedFilesState['part_images'] = self::processAndUploadFiles($unzippedFiles, $folderPath, $baseUrl, $folderPathDoc);

            // Clean up the unzip folder after processing
            Storage::disk('hostinger')->deleteDirectory($folderPathUnzip);

            $file_sate = PartServiceConnections::setPartFileState($protocolPartId, $uploadedFilesState['part_images']);
            Log::info($file_sate);
            return response()->json([
                "status" => true,
                "message" => "Files uploaded successfully!",
                "uploaded_files_state" => $uploadedFilesState
            ], 200);

        } catch (\Exception $e) {
            // Log the exception for debugging
            //  Log::error('Exception during file upload: ' . $e->getMessage());

            // Handle the exception, you might want to provide a more detailed error response
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred during file upload: ' . $e->getMessage()
            ], 422);
        }
    }

    // New helper method to process and upload files
    // New helper method to process and upload files
    private static function processAndUploadFiles($unzippedFiles, $imageBaseUrl, $baseUrl, $folderPathDoc)
    {
        $uploadedFilesState = [];

        // Iterate over the unzipped files
        foreach ($unzippedFiles as $unzippedFile) {
            $fileName = basename($unzippedFile);
            $fileBaseName = pathinfo($fileName, PATHINFO_FILENAME);  // Get the file name without the extension
            $fileExtension = pathinfo($unzippedFile, PATHINFO_EXTENSION);
            $filePath = null;
            $fileUploaded = false;
            $fileUrl = null;
            $fileSize = filesize($unzippedFile); // Get file size in bytes

            //   Log::info("Processing file: $fileName, Path: $unzippedFile");

            // Check if the file is an image or document
            if (in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                // Process image files
                $filePath = $imageBaseUrl . '/' . $fileName;
            } elseif (in_array(strtolower($fileExtension), ['doc', 'docx', 'pdf'])) {
                // Process document files (doc, docx, pdf)
                $filePath = $folderPathDoc . '/' . $fileName;

                // Ensure the document folder exists
                if (!Storage::disk('hostinger')->exists($folderPathDoc)) {
                    Storage::disk('hostinger')->makeDirectory($folderPathDoc, 0775); // Ensure the folder is writable with appropriate permissions
                    Log::info("Created document directory: $folderPathDoc");
                }
            }

            // If it's a valid file path and file is readable, proceed with upload
            if ($filePath && is_readable($unzippedFile)) {
                // If file exists, delete it before uploading
                if (Storage::disk('hostinger')->exists($filePath)) {
                    Storage::disk('hostinger')->delete($filePath); // Replace existing file
                    //  Log::info("Deleted existing file: $filePath");
                }

                // Upload the file
                Storage::disk('hostinger')->put($filePath, file_get_contents($unzippedFile));
                $fileUploaded = true;
                $fileUrl = $baseUrl . '/' . $filePath;

                // Log::info("File uploaded successfully: $fileUrl");
            } else {
                // Log::error("File is not readable or does not exist: $unzippedFile");
            }

            // If the file was uploaded successfully, add it to the state
            if ($fileUploaded) {
                $uploadedFilesState[$fileBaseName] = [
                    'uploaded' => $fileUploaded,
                    'url' => $fileUrl,
                    'media_type' => $fileExtension,
                    'file_size' => $fileSize
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
            if (!mkdir($destinationDir, 0775, true)) {
                Log::error("Failed to create temp directory at $destinationDir");
                return [];
            }
        }

        // Log the received MIME type
        Log::info('Received file MIME type: ' . $file->getMimeType());

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

            } else {
                Log::error("Failed to open the zip file: " . $file->getRealPath());
            }
        } else {
            Log::error("File is not a zip file. MIME type is: $mimeType");
        }

        // Return the unzipped files
        return $unzippedFiles;
    }
}
