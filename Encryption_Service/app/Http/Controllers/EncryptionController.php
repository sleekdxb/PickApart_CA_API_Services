<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\EncryptionHelper;
class EncryptionController extends Controller
{
    public function encrypt(Request $request)
    {
        // Get data from request
        return EncryptionHelper::handleEncryptRequest($request); // Call the helper's encryption method
    }

   public function decrypt(Request $request)
    {
         // Get data from request
        return EncryptionHelper::handleDecryptRequest($request); // Call the helper's decryption method
    }
}
