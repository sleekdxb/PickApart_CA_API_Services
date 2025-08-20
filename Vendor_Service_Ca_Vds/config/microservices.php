<?php


// config/services.php
return [
    'urls' => [
        //------- Encryption/Decryption-------MicroService---------------------------------------------------
        'encryption' => env('ENCRYPTION_SERVICE_URL', 'https://api-encryption-service.pick-a-part.ca/api/encrypt-data'),
        'decryption' => env('DECRYPTION_SERVICE_URL', 'https://api-encryption-service.pick-a-part.ca/api/decrypt-data'),



        // Add more URLs here if needed
    ]
];
