<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyAccountEmail;
use App\Mail\AuthAccountEmail;

use App\Mail\AccountStatedMail;
use App\Mail\Garage_VendorCearationCreatedMail;
use App\Mail\MaintenanceMail;
use App\Mail\PasswordMail;
use App\Mail\RegistrationSuccessMail;
use App\Mail\SubscriptionMail;
use App\Mail\AccountsMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\AccountsEmail;
use App\Models\SentEmail;
use App\Models\GarageEmail;
use App\Models\VendorEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\EncryptionServiceConnections;

class EmailController extends Controller
{

    public function setEmailsSubscriptionMail(Request $request)
    {
        // Manually validate incoming request data
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string',
            'recipient_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
            'emailMessage' => 'required|string',
            'subject' => 'required|string',  // Added subject to the validation rules
            'data' => 'required|array', // Ensure 'data' is present and is an array
            'data.*' => 'integer', // Each element of the array must be a string (encrypted values)
        ]);

        // If validation fails, return a response with status false and validation errors
        if ($validator->fails()) {
            Log::debug('Validation Errors:', $validator->errors()->toArray());  // Log the validation errors for debugging
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422); // Unprocessable Entity (422) is typically used for validation errors
        }

        try {
            // Send the verification email
            Mail::to($request->email)->send(new SubscriptionMail($request->all()));
        } catch (\Exception $e) {
            Log::error('Mail send error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to send email.',
                'code' => 500,
            ], 500); // Internal Server Error (500) response
        }

        // Prepare data for encryption
        $dataToEncrypt = [
            'recipient_email' => $request->input('email'),
            'recipient_name' => $request->input('name'),
            'subject' => $request->input('subject'),  // Correct field used
            'body' => $request->input('message'),  // Correct field used
        ];

        // Encrypt the email data
        $encryptedDataRespond = EncryptionServiceConnections::encryptData($dataToEncrypt);

        if (!$encryptedDataRespond['status']) {
            Log::error('Encryption failed: ', [
                'message' => $encryptedDataRespond['message'],
            ]);
            return response()->json([
                'status' => false,
                'message' => $encryptedDataRespond['message'],
                'code' => $encryptedDataRespond['code'],
            ], $encryptedDataRespond['code']);
        }

        $encryptedData = $encryptedDataRespond['data'];

        // Use DB transaction to ensure consistency
        DB::beginTransaction();

        try {
            // Save the email data in the database
            SentEmail::create([
                'recipient_id' => $request->input('recipient_id'),
                'sender_id' => $request->input('sender_id') ?? '',
                'recipient_name' => $encryptedData['recipient_name'],
                'recipient_email' => $encryptedData['recipient_email'],
                'subject' => $encryptedData['subject'],
                'body' => $encryptedData['body'],
                'status' => 'sent',  // Status is 'sent' since we assume the mail was sent
                'sent_at' => now(),
            ]);

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback transaction in case of an error
            DB::rollBack();
            Log::error('Database error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while saving email data.',
                'code' => 500,
            ], 500);
        }

        // Return success response
        return response()->json([
            'status' => true,
            'message' => 'Email sent successfully!',
            'code' => 200,
        ], 200); // OK (200) response

    }
    public function setEmailsRegistrationSuccessMail(Request $request)
    {

        // Manually validate incoming request data
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string',
            'recipient_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
            'message' => 'required|string',
            'subject' => 'required|string',  // Added subject to the validation rules
            'data' => 'nullable|array', // Ensure 'data' is present and is an array
            'data.*' => 'integer', // Each element of the array must be a string (encrypted values)
        ]);

        // If validation fails, return a response with status false and validation errors
        if ($validator->fails()) {
            Log::debug('Validation Errors:', $validator->errors()->toArray());  // Log the validation errors for debugging
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422); // Unprocessable Entity (422) is typically used for validation errors
        }

        try {
            // Send the verification email
            Mail::to($request->email)->send(new RegistrationSuccessMail($request->all()));
        } catch (\Exception $e) {
            Log::error('Mail send error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to send email.',
                'code' => 500,
            ], 500); // Internal Server Error (500) response
        }

        // Prepare data for encryption
        $dataToEncrypt = [
            'recipient_email' => $request->input('email'),
            'recipient_name' => $request->input('name'),
            'subject' => $request->input('subject'),  // Correct field used
            'body' => $request->input('message'),  // Correct field used
        ];

        // Encrypt the email data
        $encryptedDataRespond = EncryptionServiceConnections::encryptData($dataToEncrypt);

        if (!$encryptedDataRespond['status']) {
            Log::error('Encryption failed: ', [
                'message' => $encryptedDataRespond['message'],
            ]);
            return response()->json([
                'status' => false,
                'message' => $encryptedDataRespond['message'],
                'code' => $encryptedDataRespond['code'],
            ], $encryptedDataRespond['code']);
        }

        $encryptedData = $encryptedDataRespond['data'];

        // Use DB transaction to ensure consistency
        DB::beginTransaction();

        try {
            // Save the email data in the database
            SentEmail::create([
                'recipient_id' => $request->input('recipient_id'),
                'sender_id' => $request->input('sender_id') ?? '',
                'recipient_name' => $encryptedData['recipient_name'],
                'recipient_email' => $encryptedData['recipient_email'],
                'subject' => $encryptedData['subject'],
                'body' => $encryptedData['body'],
                'status' => 'sent',  // Status is 'sent' since we assume the mail was sent
                'sent_at' => now(),
            ]);

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback transaction in case of an error
            DB::rollBack();
            Log::error('Database error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while saving email data.',
                'code' => 500,
            ], 500);
        }

        // Return success response
        return response()->json([
            'status' => true,
            'message' => 'Email sent successfully!',
            'code' => 200,
        ], 200); // OK (200) response
    }
    public function setEmailsPasswordMail(Request $request)
    {
        // Manually validate incoming request data
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string',
            'recipient_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
            'message' => 'required|string',
            'subject' => 'required|string',  // Added subject to the validation rules
            'data' => 'required|array', // Ensure 'data' is present and is an array
            'data.*' => 'integer', // Each element of the array must be a string (encrypted values)
        ]);

        // If validation fails, return a response with status false and validation errors
        if ($validator->fails()) {
            Log::debug('Validation Errors:', $validator->errors()->toArray());  // Log the validation errors for debugging
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422); // Unprocessable Entity (422) is typically used for validation errors
        }

        try {
            // Send the verification email
            Mail::to($request->email)->send(new PasswordMail($request->all()));
        } catch (\Exception $e) {
            Log::error('Mail send error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to send email.',
                'code' => 500,
            ], 500); // Internal Server Error (500) response
        }

        // Prepare data for encryption
        $dataToEncrypt = [
            'recipient_email' => $request->input('email'),
            'recipient_name' => $request->input('name'),
            'subject' => $request->input('subject'),  // Correct field used
            'body' => $request->input('message'),  // Correct field used
        ];

        // Encrypt the email data
        $encryptedDataRespond = EncryptionServiceConnections::encryptData($dataToEncrypt);

        if (!$encryptedDataRespond['status']) {
            Log::error('Encryption failed: ', [
                'message' => $encryptedDataRespond['message'],
            ]);
            return response()->json([
                'status' => false,
                'message' => $encryptedDataRespond['message'],
                'code' => $encryptedDataRespond['code'],
            ], $encryptedDataRespond['code']);
        }

        $encryptedData = $encryptedDataRespond['data'];

        // Use DB transaction to ensure consistency
        DB::beginTransaction();

        try {
            // Save the email data in the database
            SentEmail::create([
                'recipient_id' => $request->input('recipient_id'),
                'sender_id' => $request->input('sender_id') ?? '',
                'recipient_name' => $encryptedData['recipient_name'],
                'recipient_email' => $encryptedData['recipient_email'],
                'subject' => $encryptedData['subject'],
                'body' => $encryptedData['body'],
                'status' => 'sent',  // Status is 'sent' since we assume the mail was sent
                'sent_at' => now(),
            ]);

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback transaction in case of an error
            DB::rollBack();
            Log::error('Database error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while saving email data.',
                'code' => 500,
            ], 500);
        }

        // Return success response
        return response()->json([
            'status' => true,
            'message' => 'Email sent successfully!',
            'code' => 200,
        ], 200); // OK (200) response

    }


    public function setMaintenanceMail(Request $request)
    {

        // Manually validate incoming request data
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string',
            'recipient_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
            'message' => 'required|string',
            'subject' => 'required|string',  // Added subject to the validation rules
            'data' => 'nullable|array', // Ensure 'data' is present and is an array
            'data.*' => 'integer', // Each element of the array must be a string (encrypted values)
        ]);

        // If validation fails, return a response with status false and validation errors
        if ($validator->fails()) {
            Log::debug('Validation Errors:', $validator->errors()->toArray());  // Log the validation errors for debugging
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422); // Unprocessable Entity (422) is typically used for validation errors
        }

        try {
            // Send the verification email
            Mail::to($request->email)->send(new MaintenanceMail($request->all()));
        } catch (\Exception $e) {
            Log::error('Mail send error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to send email.',
                'code' => 500,
            ], 500); // Internal Server Error (500) response
        }

        // Prepare data for encryption
        $dataToEncrypt = [
            'recipient_email' => $request->input('email'),
            'recipient_name' => $request->input('name'),
            'subject' => $request->input('subject'),  // Correct field used
            'body' => $request->input('message'),  // Correct field used
        ];

        // Encrypt the email data
        $encryptedDataRespond = EncryptionServiceConnections::encryptData($dataToEncrypt);

        if (!$encryptedDataRespond['status']) {
            Log::error('Encryption failed: ', [
                'message' => $encryptedDataRespond['message'],
            ]);
            return response()->json([
                'status' => false,
                'message' => $encryptedDataRespond['message'],
                'code' => $encryptedDataRespond['code'],
            ], $encryptedDataRespond['code']);
        }

        $encryptedData = $encryptedDataRespond['data'];

        // Use DB transaction to ensure consistency
        DB::beginTransaction();

        try {
            // Save the email data in the database
            SentEmail::create([
                'recipient_id' => $request->input('recipient_id'),
                'sender_id' => $request->input('sender_id') ?? '',
                'recipient_name' => $encryptedData['recipient_name'],
                'recipient_email' => $encryptedData['recipient_email'],
                'subject' => $encryptedData['subject'],
                'body' => $encryptedData['body'],
                'status' => 'sent',  // Status is 'sent' since we assume the mail was sent
                'sent_at' => now(),
            ]);

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback transaction in case of an error
            DB::rollBack();
            Log::error('Database error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while saving email data.',
                'code' => 500,
            ], 500);
        }

        // Return success response
        return response()->json([
            'status' => true,
            'message' => 'Email sent successfully!',
            'code' => 200,
        ], 200); // OK (200) response
    }


    public function setGarage_VendorCearationCreatedMail(Request $request)
    {
        // Manually validate incoming request data
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string',
            'recipient_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
            'upper_info' => 'required|string',
            'but_info' => 'required|string',
            'account_type' => 'required|string',
            'message' => 'required|string',
            'subject' => 'required|string',  // Added subject to the validation rules
            'data' => 'nullable|array', // Ensure 'data' is present and is an array
            'data.*' => 'integer', // Each element of the array must be a string (encrypted values)
        ]);

        // If validation fails, return a response with status false and validation errors
        if ($validator->fails()) {
            Log::debug('Validation Errors:', $validator->errors()->toArray());  // Log the validation errors for debugging
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422); // Unprocessable Entity (422) is typically used for validation errors
        }

        try {
            // Send the verification email
            Mail::to($request->email)->send(new Garage_VendorCearationCreatedMail($request->all()));
        } catch (\Exception $e) {
            Log::error('Mail send error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to send email.',
                'code' => 500,
            ], 500); // Internal Server Error (500) response
        }

        // Prepare data for encryption
        $dataToEncrypt = [
            'recipient_email' => $request->input('email'),
            'recipient_name' => $request->input('name'),
            'subject' => $request->input('subject'),  // Correct field used
            'body' => $request->input('message'),  // Correct field used
        ];

        // Encrypt the email data
        $encryptedDataRespond = EncryptionServiceConnections::encryptData($dataToEncrypt);

        if (!$encryptedDataRespond['status']) {
            Log::error('Encryption failed: ', [
                'message' => $encryptedDataRespond['message'],
            ]);
            return response()->json([
                'status' => false,
                'message' => $encryptedDataRespond['message'],
                'code' => $encryptedDataRespond['code'],
            ], $encryptedDataRespond['code']);
        }

        $encryptedData = $encryptedDataRespond['data'];

        // Use DB transaction to ensure consistency
        DB::beginTransaction();

        try {
            // Save the email data in the database
            if ($request->input('account_type') === 'Garage') {
                GarageEmail::create([
                    'recipient_id' => $request->input('recipient_id'),
                    'sender_id' => $request->input('sender_id') ?? '',
                    'recipient_name' => $encryptedData['recipient_name'],
                    'recipient_email' => $encryptedData['recipient_email'],
                    'subject' => $encryptedData['subject'],
                    'body' => $encryptedData['body'],
                    'status' => 'sent',  // Status is 'sent' since we assume the mail was sent
                    'sent_at' => now(),
                ]);
            } else {
                VendorEmail::create([
                    'recipient_id' => $request->input('recipient_id'),
                    'sender_id' => $request->input('sender_id') ?? '',
                    'recipient_name' => $encryptedData['recipient_name'],
                    'recipient_email' => $encryptedData['recipient_email'],
                    'subject' => $encryptedData['subject'],
                    'body' => $encryptedData['body'],
                    'status' => 'sent',  // Status is 'sent' since we assume the mail was sent
                    'sent_at' => now(),
                ]);
            }

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback transaction in case of an error
            DB::rollBack();
            Log::error('Database error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while saving email data.',
                'code' => 500,
            ], 500);
        }

        // Return success response
        return response()->json([
            'status' => true,
            'message' => 'Email sent successfully!',
            'code' => 200,
        ], 200); // OK (200) response

    }



    public function setAccountStatedMail(Request $request)
    {

        // Manually validate incoming request data
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string',
            'recipient_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
            'upper_info' => 'required|string',
            'but_info' => 'required|string',
            'account_type' => 'required|string',
            'message' => 'required|string',
            'subject' => 'required|string',  // Added subject to the validation rules
            'data' => 'nullable|array', // Ensure 'data' is present and is an array
            'data.*' => 'integer', // Each element of the array must be a string (encrypted values)
        ]);

        // If validation fails, return a response with status false and validation errors
        if ($validator->fails()) {
            Log::debug('Validation Errors:', $validator->errors()->toArray());  // Log the validation errors for debugging
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422); // Unprocessable Entity (422) is typically used for validation errors
        }

        try {
            // Send the verification email
            Mail::to($request->email)->send(new AccountStatedMail($request->all()));
        } catch (\Exception $e) {
            Log::error('Mail send error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to send email.',
                'code' => 500,
            ], 500); // Internal Server Error (500) response
        }

        // Prepare data for encryption
        $dataToEncrypt = [
            'recipient_email' => $request->input('email'),
            'recipient_name' => $request->input('name'),
            'subject' => $request->input('subject'),  // Correct field used
            'body' => $request->input('message'),  // Correct field used
        ];

        // Encrypt the email data
        $encryptedDataRespond = EncryptionServiceConnections::encryptData($dataToEncrypt);

        if (!$encryptedDataRespond['status']) {
            Log::error('Encryption failed: ', [
                'message' => $encryptedDataRespond['message'],
            ]);
            return response()->json([
                'status' => false,
                'message' => $encryptedDataRespond['message'],
                'code' => $encryptedDataRespond['code'],
            ], $encryptedDataRespond['code']);
        }

        $encryptedData = $encryptedDataRespond['data'];

        // Use DB transaction to ensure consistency
        DB::beginTransaction();

        try {
            // Save the email data in the database
            SentEmail::create([
                'recipient_id' => $request->input('recipient_id'),
                'sender_id' => $request->input('sender_id') ?? '',
                'recipient_name' => $encryptedData['recipient_name'],
                'recipient_email' => $encryptedData['recipient_email'],
                'subject' => $encryptedData['subject'],
                'body' => $encryptedData['body'],
                'status' => 'sent',  // Status is 'sent' since we assume the mail was sent
                'sent_at' => now(),
            ]);

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback transaction in case of an error
            DB::rollBack();
            Log::error('Database error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while saving email data.',
                'code' => 500,
            ], 500);
        }

        // Return success response
        return response()->json([
            'status' => true,
            'message' => 'Email sent successfully!',
            'code' => 200,
        ], 200); // OK (200) response
    }


    public function sendEmail(Request $request)
    {
        // Manually validate incoming request data
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string',
            'recipient_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
            'message' => 'required|string',
            'subject' => 'required|string',  // Added subject to the validation rules
            'data' => 'required|array', // Ensure 'data' is present and is an array
            'data.*' => 'integer', // Each element of the array must be a string (encrypted values)
        ]);

        // If validation fails, return a response with status false and validation errors
        if ($validator->fails()) {
            Log::debug('Validation Errors:', $validator->errors()->toArray());  // Log the validation errors for debugging
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422); // Unprocessable Entity (422) is typically used for validation errors
        }

        try {
            // Send the verification email
            Mail::to($request->email)->send(new VerifyAccountEmail($request->all()));
        } catch (\Exception $e) {
            Log::error('Mail send error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to send email.',
                'code' => 500,
            ], 500); // Internal Server Error (500) response
        }

        // Prepare data for encryption
        $dataToEncrypt = [
            'recipient_email' => $request->input('email'),
            'recipient_name' => $request->input('name'),
            'subject' => $request->input('subject'),  // Correct field used
            'body' => $request->input('message'),  // Correct field used
        ];

        // Encrypt the email data
        $encryptedDataRespond = EncryptionServiceConnections::encryptData($dataToEncrypt);

        if (!$encryptedDataRespond['status']) {
            Log::error('Encryption failed: ', [
                'message' => $encryptedDataRespond['message'],
            ]);
            return response()->json([
                'status' => false,
                'message' => $encryptedDataRespond['message'],
                'code' => $encryptedDataRespond['code'],
            ], $encryptedDataRespond['code']);
        }

        $encryptedData = $encryptedDataRespond['data'];

        // Use DB transaction to ensure consistency
        DB::beginTransaction();

        try {
            // Save the email data in the database
            SentEmail::create([
                'recipient_id' => $request->input('recipient_id'),
                'sender_id' => $request->input('sender_id') ?? '',
                'recipient_name' => $encryptedData['recipient_name'],
                'recipient_email' => $encryptedData['recipient_email'],
                'subject' => $encryptedData['subject'],
                'body' => $encryptedData['body'],
                'status' => 'sent',  // Status is 'sent' since we assume the mail was sent
                'sent_at' => now(),
            ]);

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback transaction in case of an error
            DB::rollBack();
            Log::error('Database error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while saving email data.',
                'code' => 500,
            ], 500);
        }

        // Return success response
        return response()->json([
            'status' => true,
            'message' => 'Email sent successfully!',
            'code' => 200,
        ], 200); // OK (200) response
    }
    
    //------------------------------------------------------------------------------------------

    public function sendEmailTowFactor(Request $request)
    {
        // Manually validate incoming request data
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string',
            'recipient_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
            'message' => 'required|string',
            'subject' => 'required|string',  // Added subject to the validation rules
            'data' => 'required|array', // Ensure 'data' is present and is an array
            'data.*' => 'integer', // Each element of the array must be a string (encrypted values)
        ]);

        // If validation fails, return a response with status false and validation errors
        if ($validator->fails()) {
            Log::debug('Validation Errors:', $validator->errors()->toArray());  // Log the validation errors for debugging
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422); // Unprocessable Entity (422) is typically used for validation errors
        }

        try {
            // Send the verification email
            Mail::to($request->email)->send(new AuthAccountEmail($request->all()));
        } catch (\Exception $e) {
            Log::error('Mail send error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to send email.',
                'code' => 500,
            ], 500); // Internal Server Error (500) response
        }

        // Prepare data for encryption
        $dataToEncrypt = [
            'recipient_email' => $request->input('email'),
            'recipient_name' => $request->input('name'),
            'subject' => $request->input('subject'),  // Correct field used
            'body' => $request->input('message'),  // Correct field used
        ];

        // Encrypt the email data
        $encryptedDataRespond = EncryptionServiceConnections::encryptData($dataToEncrypt);

        if (!$encryptedDataRespond['status']) {
            Log::error('Encryption failed: ', [
                'message' => $encryptedDataRespond['message'],
            ]);
            return response()->json([
                'status' => false,
                'message' => $encryptedDataRespond['message'],
                'code' => $encryptedDataRespond['code'],
            ], $encryptedDataRespond['code']);
        }

        $encryptedData = $encryptedDataRespond['data'];

        // Use DB transaction to ensure consistency
        DB::beginTransaction();

        try {
            // Save the email data in the database
            SentEmail::create([
                'recipient_id' => $request->input('recipient_id'),
                'sender_id' => $request->input('sender_id') ?? '',
                'recipient_name' => $encryptedData['recipient_name'],
                'recipient_email' => $encryptedData['recipient_email'],
                'subject' => $encryptedData['subject'],
                'body' => $encryptedData['body'],
                'status' => 'sent',  // Status is 'sent' since we assume the mail was sent
                'sent_at' => now(),
            ]);

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback transaction in case of an error
            DB::rollBack();
            Log::error('Database error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while saving email data.',
                'code' => 500,
            ], 500);
        }

        // Return success response
        return response()->json([
            'status' => true,
            'message' => 'Email sent successfully!',
            'code' => 200,
        ], 200); // OK (200) response
    }
     public function   resetPasswordEmail(Request $request)
    {
        // Manually validate incoming request data
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string',
            'recipient_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
            'upper_info' => 'required|string',
            'but_info' => 'required|string',
            'account_type' => 'required|string',
            'message' => 'required|string',
            'subject' => 'required|string',  // Added subject to the validation rules
            'data' => 'nullable|array', // Ensure 'data' is present and is an array
            'data.*' => 'integer', // Each element of the array must be a string (encrypted values)
        ]);

        // If validation fails, return a response with status false and validation errors
        if ($validator->fails()) {
            Log::debug('Validation Errors:', $validator->errors()->toArray());  // Log the validation errors for debugging
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422); // Unprocessable Entity (422) is typically used for validation errors
        }

        try {
            // Send the verification email
            Mail::to($request->email)->send(new AccountsMail($request->all()));
        } catch (\Exception $e) {
            Log::error('Mail send error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to send email.',
                'code' => 500,
            ], 500); // Internal Server Error (500) response
        }

        // Prepare data for encryption
        $dataToEncrypt = [
            'recipient_email' => $request->input('email'),
            'recipient_name' => $request->input('name'),
            'subject' => $request->input('subject'),  // Correct field used
            'body' => $request->input('message'),  // Correct field used
        ];

        // Encrypt the email data
        $encryptedDataRespond = EncryptionServiceConnections::encryptData($dataToEncrypt);

        if (!$encryptedDataRespond['status']) {
            Log::error('Encryption failed: ', [
                'message' => $encryptedDataRespond['message'],
            ]);
            return response()->json([
                'status' => false,
                'message' => $encryptedDataRespond['message'],
                'code' => $encryptedDataRespond['code'],
            ], $encryptedDataRespond['code']);
        }

        $encryptedData = $encryptedDataRespond['data'];

        // Use DB transaction to ensure consistency
        DB::beginTransaction();

        try {
            // Save the email data in the database
            AccountsEmail::create([
                'recipient_id' => $request->input('recipient_id'),
                'sender_id' => $request->input('sender_id') ?? '',
                'recipient_name' => $encryptedData['recipient_name'],
                'recipient_email' => $encryptedData['recipient_email'],
                'subject' => $encryptedData['subject'],
                'body' => $encryptedData['body'],
                'status' => 'sent',  // Status is 'sent' since we assume the mail was sent
                'sent_at' => now(),
            ]);

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback transaction in case of an error
            DB::rollBack();
            Log::error('Database error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while saving email data.',
                'code' => 500,
            ], 500);
        }

        // Return success response
        return response()->json([
            'status' => true,
            'message' => 'Email sent successfully!',
            'code' => 200,
        ], 200); // OK (200) response
    }

}
