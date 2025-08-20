<?php


// config/services.php
return [
    'urls' => [
        //------- Encryption/Decryption-------MicroService---------------------------------------------------
        'encryption' => env('ENCRYPTION_SERVICE_URL', 'https://api-encryption-service.pick-a-part.ca/api/encrypt-data'),
        'decryption' => env('DECRYPTION_SERVICE_URL', 'https://api-encryption-service.pick-a-part.ca/api/decrypt-data'),
        'emailsRegistrationSuccessMail' => env('EMAILS_REGISTRATION_SUCCESS_MAIL', 'https://api-mailing-service.pick-a-part.ca/api/set-emails-registration-success-mail'),
        'notifyVendor' => env('NOTIFY_VENDOR'),
        'emailsVendor' => env('EMAILS_VENDOR'),


        // Add more URLs here if needed
    ]
];
