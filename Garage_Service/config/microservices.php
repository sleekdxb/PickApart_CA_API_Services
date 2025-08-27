<?php


// config/services.php
return [
    'urls' => [
        //------- Encryption/Decryption-------MicroService---------------------------------------------------
        'encryption' => env('ENCRYPTION_SERVICE_URL', ''),
        'decryption' => env('DECRYPTION_SERVICE_URL', ''),
        'emailsRegistrationSuccessMail' => env('EMAILS_REGISTRATION_SUCCESS_MAIL', ''),
        'notifyVendorReq' => env('NOTIFY_VENDOR_REQ'),
        'notifyGarage' => env('NOTIFY_GARAGE'),
        'emailsGarage' => env('EMAILS_GARAGE'),
        // Add more URLs here if needed
    ]
];
