<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\Account_state;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use App\Services\VendorServiceConnections;
use App\Services\EncryptionServiceConnections;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Jobs\GenerateOtpJob;
use App\Models\CacheItem;
use App\Jobs\SetVendorProfileJob;
use App\Services\MailingServiceConnections;
use Illuminate\Support\Str;
use App\Models\AccountNotifictionschannle;
use App\Models\GarageNotifictionsChannle;
use InvalidArgumentException;
use Illuminate\Support\Facades\Schema;

class RegistrationHelper
{
    /**
     * Create a new account:
     * - Validates inputs
     * - Encrypts PII with encryption microservice (single call)
     * - Enforces email uniqueness via email_hash (constant-time lookup)
     * - Creates initial "Unverified" system state
     * - Generates OTP (activation) and sends welcome email (non-blocking best-effort)
     */
    public static function createAccount(Request $request)
    {
        $now = Carbon::now();

        // ---------------------------------------------------
        // 0) Validate upfront (keeps code simple & safe)
        // ---------------------------------------------------
        $validated = validator($request->all(), [
            'firstName' => ['required', 'string', 'max:120'],
            'lastName' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:64'],
            'account_type' => ['required', 'string', 'max:64'],
        ])->validate();

        // Normalize email & compute deterministic hash for O(1) uniqueness checks
        $emailPlain = mb_strtolower(trim($validated['email']));
        $emailHash = hash('sha256', $emailPlain);

        // If you've added this column (recommended), we can check duplicates instantly
        if (Account::where('email_hash', $emailHash)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'An account with this email already exists.',
                'code' => 409,
            ], 409);
        }

        // Prepare system default "Unverified" state (3 months window)
        $unverifiedUntil = $now->copy()->addMonths(3);

        // ---------------------------------------------------
        // 1) Encrypt PII in one shot (single network call)
        // ---------------------------------------------------
        $dataToEncrypt = [
            'email' => $emailPlain,
            'phone' => $validated['phone'] ?? '',
            'account_type' => $validated['account_type'],
            'state_code' => 'SYSUV4512',
            'state_name' => 'Unverified',
            'note' => 'Account created but email unverified.',
            'reason' => 'Account email is pending verification.',
        ];

        try {
            $enc = EncryptionServiceConnections::encryptData($dataToEncrypt)['data'];

            // Use encrypted values when provided; fallback to plaintext defaults
            $encryptedEmail = $enc['email'] ?? $dataToEncrypt['email'];
            $encryptedPhone = $enc['phone'] ?? $dataToEncrypt['phone'];
            $encryptedAccountType = $enc['account_type'] ?? $dataToEncrypt['account_type'];
            $encryptedStateCode = $enc['state_code'] ?? $dataToEncrypt['state_code'];
            $encryptedStateName = $enc['state_name'] ?? $dataToEncrypt['state_name'];
            $encryptedNote = $enc['note'] ?? $dataToEncrypt['note'];
            $encryptedReason = $enc['reason'] ?? $dataToEncrypt['reason'];

            // ---------------------------------------------------
            // 2) Create Account + initial Account_state in ONE TX
            // ---------------------------------------------------
            DB::beginTransaction();

            // Use ULID for compact, sortable IDs (stable across environments)
            $accId = (string) Str::ulid();
            $stateId = (string) Str::ulid();

            $account = Account::create([
                'acc_id' => $accId,
                'firstName' => $validated['firstName'],
                'lastName' => $validated['lastName'],
                'email' => $encryptedEmail,       // encrypted at rest
                'phone' => $encryptedPhone,       // encrypted at rest
                'account_type' => $encryptedAccountType, // encrypted at rest
                'password' => Hash::make($validated['password']),
                'email_hash' => $emailHash,            // for O(1) lookups
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $accountState = Account_state::create([
                'state_id' => $stateId,
                'acc_id' => $accId,
                'doer_acc_id' => $accId, // creator = self
                'state_code' => $encryptedStateCode,
                'state_name' => $encryptedStateName,
                'note' => $encryptedNote,
                'reason' => $encryptedReason,
                'time_period' => $unverifiedUntil,      // keep as timestamp for logic
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Link account to its initial system state
            $account->update([
                'system_state_id' => $stateId,
            ]);

            DB::commit();

            // ---------------------------------------------------
            // 3) Post-commit side effects (don’t block success)
            // ---------------------------------------------------

            // Decrypt a small subset for the client (human-readable)
            $decryptedData = [];
            try {
                $decResp = EncryptionServiceConnections::decryptData([
                    'state_code' => $accountState->state_code,
                    'state_name' => $accountState->state_name,
                ]);
                $decryptedData = $decResp['data'] ?? [];
            } catch (\Throwable $t) {
                Log::warning('createAccount: decrypt state failed', ['err' => $t->getMessage()]);
            }

            // OTP for activation (cache-backed)
            $otpResponse = OtpHelper::generateOtp($accId, $request->input('device_info', ''), 'ACTV451EM');

            // Create user’s notification channel (fire-and-forget)


            // Send welcome email (best-effort, do not fail the flow)
            try {
                $mailingData = [
                    'sender_id' => 'SYSTEM',
                    'recipient_id' => $accId,
                    'email' => $emailPlain, // send to plaintext email
                    'name' => trim($validated['firstName'] . ' ' . $validated['lastName']),
                    'message' => 'You can now explore verified auto parts, connect with vendors, and enjoy a safe and convenient experience.',
                    'subject' => 'Welcome to Pick-a-part.ca!',
                    'data' => '',
                ];
                MailingServiceConnections::sendEmailRgestration($mailingData);
            } catch (\Throwable $t) {
                Log::warning('createAccount: welcome email failed', ['err' => $t->getMessage()]);
            }

            // ---------------------------------------------------
            // 4) Return success (201 Created)
            // ---------------------------------------------------
            return response()->json([
                'status' => true,
                'message' => 'Account created successfully.',
                'code' => 200,
                'data' => [
                    'state' => $decryptedData,
                    'acc_id' => $accId,
                    'otp_id' => $otpResponse['otp_id'] ?? null,
                ],
            ], 200);

        } catch (\Throwable $e) {
            // Ensure we roll back if TX started
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Registration failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error during registration: ' . $e->getMessage(),
                'code' => 500,
                'data' => null,
            ], 500);
        }
    }

    public static function createNotificationChannel(string|int $accId, string $accountType, array $extraData = [])
    {
        $type = strtoupper(trim($accountType));

        // Pick the model based on type
        $modelClass = match ($type) {
            'STR' => AccountNotifictionschannle::class,
            'Garage' => GarageNotifictionsChannle::class,
            default => throw new InvalidArgumentException("Unsupported account_type: {$accountType}"),
        };

        // If STR, check if a channel already exists for this acc_id
        if ($type === 'STR') {
            $existing = $modelClass::where('acc_id', $accId)->first();
            if ($existing) {
                return $existing; // Return the existing record without creating
            }
        }

        // Generate a unique 7-digit channel_frequency
        do {
            $frequency = (string) random_int(1000000, 9999999);
        } while ($modelClass::where('channel_frequency', $frequency)->exists());

        // Generate a unique channel_name based on type + random characters (e.g., STR-AB12CD)
        do {
            $channelName = $type . '-' . Str::upper(Str::random(6));
        } while ($modelClass::where('channel_name', $channelName)->exists());

        // Create the record
        $now = Carbon::now();
        $record = $modelClass::create([
            'channel_name' => $channelName,
            'acc_id' => (string) $accId,  // use acc_id
            'channel_frequency' => $frequency,
            'latest_data' => null,             // force null
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $record->fresh();
    }
}
