<?php


// config/services.php
return [
    'urls' => [
        //------- Encryption/Decryption-------MicroService---------------------------------------------------
        'encryption' => env('ENCRYPTION_SERVICE_URL', 'https://api-encryption-service.pick-a-part.ca/api/encrypt-data'),
        'decryption' => env('DECRYPTION_SERVICE_URL', 'https://api-encryption-service.pick-a-part.ca/api/decrypt-data'),
        'sendEmail' => env('SEND_EMAIL_SERVICE_URL', 'https://api-mailing-service.pick-a-part.ca/api/send-email'),
        'setProfile' => env('SER_PROFILE_SERVICE_URL', 'https://api-vendor-service.pick-a-part.ca/api/set-profile-by-id'),

        //-----------------------------------------------------------------------------------------------
        'emailsRegistrationSuccessMail' => env('EMAILS_REGISTRATION_SUCCESS_MAIL', 'https://api-mailing-service.pick-a-part.ca/api/set-emails-registration-success-mail'),
        'sendResetPasswordEmailParties' => env('SEND_RESET_PASSWORD_EMAIL_PARTIES', 'https://api-mailing-service.pick-a-part.ca/api/reset-password-email'),
        'notifyAccountParties' => env('NOTIFY_ACCOUNT_PARTIES', 'https://api-notification-service.pick-a-part.ca/api/notifications/account'),


        // Add more URLs here if needed
    ]
];
