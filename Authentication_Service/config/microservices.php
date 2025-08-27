<?php


// config/services.php
return [
    'urls' => [
        //------- Encryption/Decryption-------MicroService---------------------------------------------------
        'encryption' => env('ENCRYPTION_SERVICE_URL', ''),
        'decryption' => env('DECRYPTION_SERVICE_URL', ''),
        'sendEmail' => env('SEND_EMAIL_SERVICE_URL', ''),
        'authTowSendEmail' => env('AUTH_SEND_EMAIL_SERVICE_URL', ''),
        'setProfile' => env('SER_PROFILE_SERVICE_URL', ''),

        //-----------------------------------------------------------------------------------------------
        'emailsRegistrationSuccessMail' => env('EMAILS_REGISTRATION_SUCCESS_MAIL', ''),
        'sendResetPasswordEmailParties' => env('SEND_RESET_PASSWORD_EMAIL_PARTIES', ''),
        'notifyAccountParties' => env('NOTIFY_ACCOUNT_PARTIES', ''),


        // Add more URLs here if needed
    ]
];
