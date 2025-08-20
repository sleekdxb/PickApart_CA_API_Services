<?php


// config/services.php
return [
    'urls' => [
        //------- Encryption/Decryption-------MicroService---------------------------------------------------
        'encryption' => env('ENCRYPTION_SERVICE_URL', 'https://api-encryption-service.pick-a-part.ca/api/encrypt-data'),
        'decryption' => env('DECRYPTION_SERVICE_URL', 'https://api-encryption-service.pick-a-part.ca/api/decrypt-data'),
        'emailsRegistrationSuccessMail' => env('EMAILS_REGISTRATION_SUCCESS_MAIL', 'https://api-mailing-service.pick-a-part.ca/api/set-garage_Vendor-cearation-created-mail'),
        'notifyVendorReq' => env('NOTIFY_VENDOR_REQ'),
        'notifyGarage' => env('NOTIFY_GARAGE'),
        'emailsGarage' => env('EMAILS_GARAGE'),
        // Add more URLs here if needed
    ]
];
