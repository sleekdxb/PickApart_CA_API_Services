<?php


// config/services.php
return [
    'urls' => [
        //------- Encryption/Decryption-------MicroService---------------------------------------------------
        'encryption' => env('ENCRYPTION_SERVICE_URL', ''),
        'decryption' => env('DECRYPTION_SERVICE_URL', ''),
        // Add more URLs here if needed
    ]

];
