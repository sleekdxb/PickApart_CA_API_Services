<?php


// config/services.php
return [
    'urls' => [
        // Base upload paths (defaults now = empty string)
        'accountProfileImage' => env('BASE_ACCOUNTS_PROFILE_URL', ''),
        'uploadVendorImage' => env('BASE_VENDORS_IMAGE_URL', ''),
        'uploadVendorDoc' => env('BASE_VENDORS_DOC_URL', ''),
        'uploadGarageImage' => env('BASE_GARAGE_IMAGE_URL', ''),
        'uploadGarageDoc' => env('BASE_GARAGE_DOC_URL', ''),
        'uploadPartsImage' => env('BASE_PARTS_IMAGE_URL', ''),
        'uploadPartsDoc' => env('BASE_PARTS_DOC_URL', ''), // fixed env name

        // Service endpoints (defaults now = empty string)
        'setPartFileState' => env('SET_PART_FILE_STATE_SERVICE_URL', ''),
        'setFileStateVendor' => env('SET_FILE_STATE_VENDOR_SERVICE_URL', ''),  // separate envs
        'setFileStateGarage' => env('SET_FILE_STATE_GARAGE_SERVICE_URL', ''),  // separate envs
        'decryption' => env('DECRYPTION_SERVICE_URL', ''),

        // Add more URLs here if needed
    ]
];
