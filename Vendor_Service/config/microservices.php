<?php


// config/services.php
return [
    'urls' => [
        //------- Encryption/Decryption-------MicroService---------------------------------------------------
        'encryption' => env('ENCRYPTION_SERVICE_URL', ''),
        'decryption' => env('DECRYPTION_SERVICE_URL', ''),
        'emailsRegistrationSuccessMail' => env('EMAILS_REGISTRATION_SUCCESS_MAIL', ''),
        'notifyVendor' => env('NOTIFY_VENDOR'),
        'emailsVendor' => env('EMAILS_VENDOR'),


        // Add more URLs here if needed
    ]
];
