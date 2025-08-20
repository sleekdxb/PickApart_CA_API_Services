<?php


// config/services.php
return [
    'urls' => [
        'accountProfileImage' => env('BASE_ACCOUNTS_PROFILE_URL', 'Images/Accounts/'),
        'uploadVendorImage' => env('BASE_VENDORS_IMAGE_URL', 'Images/Vendors/'),
        'uploadVendorDoc' => env('BASE_VENDORS_DOC_URL', 'Docs/Vendors/'),
        'uploadGarageImage' => env('BASE_GARAGE_IMAGE_URL', 'Images/Garages/'),
        'uploadGarageDoc' => env('BASE_GARAGE_DOC_URL', 'Docs/Garages/'),
        'uploadPartsImage' => env('BASE_PARTS_IMAGE_URL', 'Parts/Images/'),
        'uploadPartsDoc' => env('BASE_PARTS_IMAGE_URL', 'Parts/Doc/'),


        'setPartFileState' => env('SET_PART_FILE_STATE_SERVICE_URL', 'https://api-parts-service.pick-a-part.ca/api/setPartFileState'),

        'setFileStateVendor' => env('SET_FILE_STATE_SERVICE_URL', 'https://api-vendor-service.pick-a-part.ca/api/set-profileOrAccount-fileState-by-id'),
        'setFileStateGarage' => env('SET_FILE_STATE_SERVICE_URL', 'https://api-garage-service.pick-a-part.ca/api/set-profileOrAccount-fileState-by-id'),
        'decryption' => env('DECRYPTION_SERVICE_URL', 'https://api-encryption-service.pick-a-part.ca/api/decrypt-data'),



        // Add more URLs here if needed
    ]
];
