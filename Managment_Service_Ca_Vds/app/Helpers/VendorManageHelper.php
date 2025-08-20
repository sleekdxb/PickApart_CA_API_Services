<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\VendorState;
use App\Models\Vendor;
use App\Models\Account;
use App\Models\AccountMediaState;
use App\Models\AccountState;
use App\Models\AccountMediaFile;
use App\Services\NotificationServiceConnections;
use App\Services\EncryptionServiceConnections;
use App\Services\MailingServiceConnections;
use Exception;
use Illuminate\Support\Str;

class VendorManageHelper
{
    public static function UpdateVendorWithAccount(Request $request)
    {
        $results = [];

        $account = self::extractKeysWithDefaults([
            "acc_id",
            "email",
            "password",
            "phone",
            "account_type",
            "firstName",
            "lastName"
        ], $request->input('account'));
        $accountModel = Account::where('acc_id', $account['acc_id'])->first();


        $vendor = self::extractKeysWithDefaults([
            "vend_id",
            "business_name",
            "address",
            "country",
            "official_email",
            "official_phone",
            "owner_id_number",
            "owner_id_full_name"
        ], $request->input('vendor'));

        $vendor_state = self::extractKeysWithDefaults([
            "note",
            "reason",
            "state_code",
            "state_name"
        ], $request->input('vendor_state'));



        $file_state = collect($request->input('file_state'))->map(function ($item) {
            return self::extractKeysWithDefaultsFilestate([
                "acc_media_id",
                "note",
                "reason",
                "state_code",
                "state_name"
            ], $item);
        })->toArray();



        $encrypted_file_state = [];

        foreach ($file_state as $item) {
            // Only encrypt these fields
            $fieldsToEncrypt = [
                'note' => $item['note'],
                'reason' => $item['reason'],
                'state_code' => $item['state_code'],
                'state_name' => $item['state_name'],
            ];

            // Encrypt the selected fields
            $encryptedFields = EncryptionServiceConnections::encryptData($fieldsToEncrypt);

            // Structure the result: encrypted fields inside 'data', acc_media_id unencrypted
            $encrypted_file_state[] = [
                'status' => $encryptedFields['status'] ?? true,
                'message' => $encryptedFields['message'] ?? 'Encrypted.',
                'data' => $encryptedFields['data'] ?? $encryptedFields,  // fallback
                'acc_media_id' => $item['acc_media_id'],
            ];
        }


        $account_state = self::extractKeysWithDefaults([
            "note",
            "reason",
            "state_code",
            "state_name"
        ], $request->input('account_state'));

        try {
            $fieldsToEncrypt = [
                'email' => $account['email'],
                'phone' => $account['phone'],
                'account_type' => $account['account_type'],

                'note' => $account_state['note'] ?? "null",
                'reason' => $account_state['reason'] ?? "null",
                'state_code' => $account_state['state_code'] ?? "null",
                'state_name' => $account_state['state_name'] ?? "null",
                'note_vendor_state' => $vendor_state['note'] ?? "null",
                'reason_vendor_state' => $vendor_state['reason'] ?? "null",
                'state_code_vendor_state' => $vendor_state['state_code'] ?? "null",
                'state_name_vendor_state' => $vendor_state['state_name'] ?? "null",
                'note_file_state' => $file_state['note'] ?? "null",
                'reason_file_state' => $file_state['reason'] ?? "null",
                'state_code_file_state' => $file_state['state_code'] ?? "null",
                'state_name_file_state' => $file_state['state_name'] ?? "null",
            ];

            $FieldsEncrypt = EncryptionServiceConnections::encryptData($fieldsToEncrypt);


        } catch (Exception $e) {
            Log::error('Encryption error: ' . $e->getMessage());
            $results['encryption'] = 'failed: ' . $e->getMessage();
            return response()->json([
                'status' => 'error',
                'message' => 'Encryption failed.',
                'result' => $results
            ], 200);
        }

        //---------------------------------- ✅ Account Update-----------------------------------------------------------------------------------------------
        $results['account_update'] = VendorManageHelper::updateAccountSecurely($accountModel, $account, $FieldsEncrypt);
        //----------------------------------------------------------------------------------------------------------------------------------------------------


        //--------------------Vendor Update---------------------------------------------------
        $results['vendor_update'] = VendorManageHelper::updateVendorSecurely($vendor);

        //------------------------------------------------------------------------------------


        //----------- ✅ VendorState Create (Only if account exists)--------------------------------------------------------------
        $results['vendor_state'] = VendorManageHelper::createVendorStateAndNotify(
            $accountModel,
            $account,
            $vendor,
            $vendor_state,
            $FieldsEncrypt,
            $request,

        );
        //--------------------------------------------------------------------------------------------------------------------------


        //--------------------AccountState Create-------------------------------------------------------------------------------------------
        $results[] = self::accountStateAndNotify(
            $account_state,
            $accountModel,
            $FieldsEncrypt,
            $request,
            $account['acc_id'],

        );
        //-----------------------------------------------------------------------------------------------------------------------------


        //--------------------------------- ✅ AccountMediaState Create (Only if account exists)------------------------------------
        $results['file_media_states'] = VendorManageHelper::createAccountMediaStatesAndNotify(
            $accountModel,
            $account,
            $vendor,
            $file_state,
            $encrypted_file_state,
            $request,
            $accountModel['email']

        );
        //-------------------------------------------------------------------------------------------------------------------------

        // ✅ AccountState Create (Only if account exists)


        return response()->json([
            'status' => 'success',
            'message' => 'Operations completed with results.',
            'result' => $results
        ], 200);
    }

    private static function extractKeysWithDefaults(array $keys, $source): array
    {
        $source = is_array($source) ? $source : [];

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $source[$key] ?? null;
        }
        return $result;
    }
    private static function extractKeysWithDefaultsFilestate(array $keys, $source): array
    {
        // Normalize source to array
        $source = is_array($source) ? $source : [];

        // Check if it's an array of arrays (multi-record)
        $isMulti = isset($source[0]) && is_array($source[0]);

        if ($isMulti) {
            // Handle array of arrays
            $result = [];
            foreach ($source as $item) {
                $filtered = [];
                foreach ($keys as $key) {
                    $filtered[$key] = $item[$key] ?? null;
                }
                $result[] = $filtered;
            }
            return $result;
        } else {
            // Handle single record
            $result = [];
            foreach ($keys as $key) {
                $result[$key] = $source[$key] ?? null;
            }
            return $result;
        }
    }

    //---------------------------------- Account State function ---------------------------------------------------
    private static function accountStateAndNotify($account_state, $accountModel, $FieldsEncrypt, $request, $acc_id)
    {
        $results = [
            'account_state_result' => 'not_created',
            'sub_results' => [],
            'error' => null,
        ];

        try {
            if ($accountModel && !empty(array_filter($account_state))) {

                // ✅ Create Account State from Encrypted Data
                $accountState = AccountState::create([
                    'state_id' => Str::uuid()->toString(),
                    'acc_id' => $acc_id,
                    'doer_acc_id' => $request->staff_id,
                    'note' => $FieldsEncrypt['data']['note'],
                    'reason' => $FieldsEncrypt['data']['reason'],
                    'state_code' => strtoupper(trim($FieldsEncrypt['data']['state_name'])) . 45123,
                    'state_name' => $FieldsEncrypt['data']['state_name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $results['account_state_result'] = 'created';

                // ✅ Update Account Model with state_id
                $accountModel->action_state_id = $accountState->state_id;
                $accountModel->save();

                // ✅ Prepare for Notification and Email
                $fullName = $accountModel->firstName . ' ' . $accountModel->lastName;
                $email = $accountModel->email ?? 'test@example.com';

                $stateName = trim($account_state['state_name']);
                $reason = $account_state['reason'] ?? 'No reason provided';

                // ✅ State Definitions
                $states = [
                    "Unsuspend" => [
                        'type' => 'Account Approved',
                        'subject' => 'Your Account is Approved',
                        'message' => 'Congratulations! Your account has been approved. You now have full access.',
                        'action' => 'Access Dashboard',
                        'upper_info' => 'Account State Notification',
                        'but_info' => 'Access Dashboard',
                    ],
                    "Suspend" => [
                        'type' => 'Account Suspension',
                        'subject' => 'Account Suspension',
                        'message' => 'This action was taken in line with our platform policies to maintain trust and safety.Please note that your account data will be retained as per our terms and conditions. If you believe this was a mistake, you may contact us at support@pickapart.ca within 7 days to appeal the decision.',
                        'action' => 'Thank you for your patience',
                        'upper_info' => 'Reason:' . '' . $reason,
                        'but_info' => 'Thank you for your understanding.',
                    ],

                ];

                if (isset($states[$stateName])) {
                    $data = $states[$stateName];

                    // ✅ Send Notification
                    try {
                        NotificationServiceConnections::notify([
                            'acc_id' => $acc_id,
                            'vend_id' => null,
                            'type' => $data['type'],
                            'notifiable_id' => $request->staff_id,
                            'data' => [
                                'message' => $data['message'],
                                'subject' => $data['subject'],
                                'name' => $fullName,
                                'action' => $data['action'],
                            ]
                        ]);
                        $results['sub_results']['notify_result'] = 'sent';
                    } catch (\Exception $e) {
                        Log::error('Notification send error: ' . $e->getMessage());
                        $results['sub_results']['notify_result'] = 'failed';
                    }

                    // ✅ Send Email
                    try {
                        $mailPayload = [
                            'sender_id' => $request->staff_id,
                            'recipient_id' => $acc_id,
                            'email' => $email,
                            'name' => $fullName,
                            'message' => $data['message'],
                            'subject' => $data['subject'],
                            'upper_info' => $data['upper_info'],
                            'but_info' => $data['but_info'],
                            'account_type' => $accountModel->account_type,
                            'data' => '',
                        ];

                        $mailResponse = MailingServiceConnections::sendEmailAccount($mailPayload);
                        $results['sub_results']['mail_result'] = $mailResponse['status'] ? 'sent' : 'failed';
                    } catch (\Exception $e) {
                        Log::error('Mail send error: ' . $e->getMessage());
                        $results['sub_results']['mail_result'] = 'failed';
                    }
                } else {
                    $results['error'] = 'State name not recognized: ' . $stateName;
                }
            }
        } catch (\Exception $e) {
            Log::error('AccountState error: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    public static function updateVendorSecurely(array $vendor)
    {
        $result = [
            'status' => 'not_updated',
            'error' => null,
        ];

        try {
            if (!empty($vendor['vend_id'])) {
                $vendorModel = Vendor::where('vend_id', $vendor['vend_id'])->first();
                $data = array_filter($vendor, fn($v) => !is_null($v));

                if ($vendorModel) {
                    $vendorModel->update($data);
                    $result['status'] = 'updated';
                } else {
                    $result['status'] = 'not_found';
                }
            } else {
                $result['status'] = 'invalid_vendor_id';
            }
        } catch (\Exception $e) {
            Log::error('Vendor update error: ' . $e->getMessage());
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    public static function updateAccountSecurely($accountModel, $account, $FieldsEncrypt)
    {
        $result = [
            'status' => 'not_updated',
            'error' => null,
        ];

        try {
            if (!empty($account['acc_id']) && $accountModel) {
                $data = array_filter($account, fn($v) => !is_null($v));

                // Hash password if it's provided
                if (isset($data['password'])) {
                    $data['password'] = Hash::make($data['password']);
                }

                // Update account with encrypted values
                $accountModel->update([
                    'email' => $FieldsEncrypt['data']['email'],
                    'password' => $data['password'] ?? $accountModel->password,
                    'phone' => $FieldsEncrypt['data']['phone'],
                    'account_type' => $FieldsEncrypt['data']['account_type'],
                    'firstName' => $data['firstName'] ?? $accountModel->firstName,
                    'lastName' => $data['lastName'] ?? $accountModel->lastName,
                    'updated_at' => now()
                ]);

                $result['status'] = 'updated';
            } else {
                $result['status'] = 'account_model_missing_or_invalid_id';
            }
        } catch (\Exception $e) {
            Log::error('Account update error: ' . $e->getMessage());
            $result['error'] = $e->getMessage();
        }

        return $result;
    }


    public static function createVendorStateAndNotify($accountModel, $account, $vendor, $vendor_state, $FieldsEncrypt, $request)
    {
        $results = [
            'vendor_state_result' => 'not_created',
            'sub_results' => [],
            'error' => null,
        ];

        try {
            if ($accountModel && !empty($vendor['vend_id']) && !empty(array_filter($vendor_state))) {
                $newVendorState = VendorState::create([
                    'state_id' => Str::uuid()->toString(),
                    'acc_id' => $account['acc_id'],
                    'doer_acc_id' => $request->staff_id,
                    'vend_id' => $vendor['vend_id'],
                    'note' => $FieldsEncrypt['data']['note_vendor_state'],
                    'reason' => $FieldsEncrypt['data']['reason_vendor_state'],
                    'state_code' => strtoupper($vendor_state['state_name']) . 202515,
                    'state_name' => $FieldsEncrypt['data']['state_name_vendor_state'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $results['vendor_state_result'] = 'created';

                $vendorModelForStateFor = Vendor::where('vend_id', $vendor['vend_id'])->first();
                if ($vendorModelForStateFor) {
                    $vendorModelForStateFor->state_id = $newVendorState->state_id;
                    $vendorModelForStateFor->save();
                }

                $fullName = $accountModel->firstName . ' ' . $accountModel->lastName;
                $vendorName = $vendorModelForStateFor['firstName'] . ' ' . $vendorModelForStateFor['lastName'];
                $email = $decrypedAccountModel['data']['email'] ?? 'frzdg6@yahoo.com';
                $stateName = $vendor_state['state_name'];

                // Unified state definitions
                $states = [
                    "Approved" => [
                        'type' => 'Account State Approval',
                        'subject' => 'Your Account is Approved',
                        'message' => 'Great news! Your account has been verified and approved on Pickapart.ca. You can now enjoy full access to the Dashboard.',
                        'action' => 'Access Dashboard',
                        'upper_info' => 'Account State Notification',
                        'but_info' => 'Access Dashboard',
                    ],
                    "Pending" => [
                        'type' => 'Account State Pending',
                        'subject' => 'Your Account is Under Review',
                        'message' => 'Your account is currently under review. We’ll notify you as soon as it is approved.',
                        'action' => 'Thanks for your patience',
                        'upper_info' => 'Account State Notification',
                        'but_info' => 'Thanks for your patience, Pickapart.ca Team',
                    ],
                    "Rejected" => [
                        'type' => 'Account State Rejection',
                        'subject' => 'Account Approval Rejected',
                        'message' => "Unfortunately, we couldn't approve your account due to: " . ($account_state['reason'] ?? 'unspecified reasons') . ". Please review and re-submit the required information.",
                        'action' => 'Need help? Contact us at support@pickapart.ca',
                        'upper_info' => 'Account State Notification',
                        'but_info' => 'Need help? Contact us at support@pickapart.ca',
                    ],
                    "Amendment" => [
                        'type' => 'Account State Amendment',
                        'subject' => 'Action Required: Amend Your Submission',
                        'message' => 'Some details need correction before approval: Please log in and make the updates.',
                        'action' => 'Go to My Account',
                        'upper_info' => 'Account State Notification',
                        'but_info' => 'Go to My Account',
                    ],
                ];

                if (isset($states[$stateName])) {
                    $data = $states[$stateName];

                    // ⬅ Notification Payload
                    try {
                        NotificationServiceConnections::notify([
                            'acc_id' => $account['acc_id'],
                            'vend_id' => $vendor['vend_id'],
                            'type' => $data['type'],
                            'notifiable_id' => $request->staff_id,
                            'data' => [
                                'message' => $data['message'],
                                'subject' => $data['subject'],
                                'name' => $vendorName,
                                'action' => $data['action'],
                            ]
                        ]);
                        $results['sub_results']['notify_result'] = 'sent';
                    } catch (\Exception $e) {
                        Log::error('Notification send error: ' . $e->getMessage());
                        $results['sub_results']['notify_result'] = 'failed';
                    }

                    // ⬅ Email Payload (NO 'action')
                    try {
                        $mailPayload = [
                            'sender_id' => $request->staff_id,
                            'recipient_id' => $account['acc_id'],
                            'email' => $email,
                            'name' => $fullName,
                            'message' => $data['message'],
                            'subject' => $data['subject'],
                            'upper_info' => $data['upper_info'],
                            'but_info' => $data['but_info'], // or use something like 'Check your status'
                            'account_type' => $accountModel->account_type,
                            'data' => '' // optional: provide details for rendering
                        ];

                        $mailResponse = MailingServiceConnections::sendEmailVendor($mailPayload);
                        $results['sub_results']['mail_result'] = $mailResponse['status'] ? 'sent' : 'failed';
                    } catch (\Exception $e) {
                        Log::error('Mail send error: ' . $e->getMessage());
                        $results['sub_results']['mail_result'] = 'failed';
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('VendorState error: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }



    public static function createAccountMediaStatesAndNotify(
        $accountModel,
        $account,
        $vendor,
        $file_state,
        $encrypted_file_state,
        $request,
        $email
    ) {
        $results = [
            'file_state' => 'not_created',
            'error' => null,
        ];

        try {
            if ($accountModel && !empty($vendor['vend_id']) && !empty(array_filter($encrypted_file_state))) {
                $notifyPreData = [];

                foreach ($encrypted_file_state as $index => $encryptedItem) {
                    $stateItem = $file_state[$index] ?? $file_state[0] ?? null;

                    if (!$stateItem || !isset($encryptedItem['acc_media_id'])) {
                        continue;
                    }

                    // Create media state from encrypted data
                    $mediaState = AccountMediaState::create([
                        'acc_media_id' => $encryptedItem['acc_media_id'],
                        'acc_id' => $account['acc_id'],
                        'doer_id' => $request->staff_id,
                        'state_id' => Str::uuid()->toString(),
                        'vend_id' => $vendor['vend_id'],
                        'note' => $stateItem['note'] ?? '',
                        'reason' => $stateItem['reason'] ?? '',
                        'state_code' => strtoupper($stateItem['state_name'] ?? 'UNKNOWN') . 202514,
                        'state_name' => $stateItem['state_name'] ?? 'UNKNOWN',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Update media file with new state
                    $mediaFile = AccountMediaFile::where('acc_media_id', $encryptedItem['acc_media_id'])->first();
                    $fileName = $mediaFile->file_name ?? 'Unknown';

                    if ($mediaFile) {
                        Log::info('Updating state for file: ' . $fileName);
                        $mediaFile->state_id = $mediaState->state_id;
                        $mediaFile->save();
                    }

                    // Detect file type for notification label
                    $fileTypeLabel = 'Unknown';
                    if (str_contains($fileName, 'Em_Front_')) {
                        $fileTypeLabel = 'Driving licence';
                    } elseif (str_contains($fileName, 'Passport_')) {
                        $fileTypeLabel = 'Passport';
                    } elseif (str_contains($fileName, 'Proof_')) {
                        $fileTypeLabel = 'Proof of Location';
                    } elseif (str_contains($fileName, 'Registration_Certificate_')) {
                        $fileTypeLabel = 'Registration Certificate';
                    } elseif (str_contains($fileName, 'Trad_')) {
                        $fileTypeLabel = 'Trading License';
                    } elseif (str_contains($fileName, 'Tax_')) {
                        $fileTypeLabel = 'Tax Registration';
                    }

                    // Collect notification data from file_state
                    $notifyPreData[] = [
                        'reason' => $stateItem['reason'] ?? '',
                        'state_name' => $stateItem['state_name'] ?? '',
                        'file_name' => $fileTypeLabel,
                    ];
                }

                // Prepare message
                $messages = [];
                foreach ($notifyPreData as $fileDetail) {
                    $messages[] = "Your {$fileDetail['file_name']} has {$fileDetail['state_name']} . {$fileDetail['reason']}.";
                }
                $finalMessage = implode(" ", $messages);

                $fullName = $accountModel->firstName . ' ' . $accountModel->lastName;

                // Send notification
                $notifyDataFiles = [
                    'acc_id' => $account['acc_id'],
                    'vend_id' => $vendor['vend_id'],
                    'notifiable_id' => $request->staff_id,
                    'type' => 'Document State Approval',
                    'data' => [
                        'message' => $finalMessage,
                        'subject' => 'Document Status Update',
                        'name' => $fullName,
                        'action' => 'Please log in to update your documentation.',
                    ]
                ];
                NotificationServiceConnections::notify($notifyDataFiles);
                $results['notification'] = 'sent';

                // Send email
                $mailPayload = [
                    'sender_id' => $request->staff_id,
                    'recipient_id' => $account['acc_id'],
                    'email' => $email, // Replace with decrypted email if required
                    'name' => $fullName,
                    'message' => $finalMessage,
                    'subject' => 'Document Status Update',
                    'upper_info' => 'Account Media Status',
                    'but_info' => 'Please review your uploaded documents.',
                    'account_type' => 'Vendor',
                    'data' => ''
                ];
                MailingServiceConnections::sendEmailVendor($mailPayload);
                $results['mail'] = 'sent';

                $results['file_state'] = 'created';
            }
        } catch (\Exception $e) {
            Log::error('FileState error: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }


}
