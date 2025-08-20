<?php


// config/services.php
return [
    'urls' => [
        //------- Encryption/Decryption-------MicroService---------------------------------------------------
        'encryption' => env('ENCRYPTION_SERVICE_URL', 'https://api-encryption-service.pick-a-part.ca/api/encrypt-data'),
        'decryption' => env('DECRYPTION_SERVICE_URL', 'https://api-encryption-service.pick-a-part.ca/api/decrypt-data'),
        'emailsVendor' => env('EMAILS_VENDOR'),
        'emailsGarage' => env('EMAILS_GARAGE'),
        'notifyVendor' => env('NOTIFY_VENDOR'),
        'setAccountStatedMail' => env('SET_ACCOUNT_STATED_MAIL'),
        // 'setAccountStatedMail' => env('SET_ACCOUNT_STATED_MAIL', 'http://127.0.0.1:8001/api/set-account-stated-mail'),
        // Add more URLs here if needed
    ]


];
