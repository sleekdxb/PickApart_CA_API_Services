<?php

namespace App\Helpers;

use App\Models\Otp;
use App\Models\Account;
use App\Models\SubVendor;
use App\Models\Account_state;
use App\Services\EncryptionServiceConnections;
use App\Services\MailingServiceConnections;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OtpHelper
{
    /**
     * Create an OTP, store it, try to email it (direct call), and ALWAYS return otp_id.
     *
     * @param  string      $acc_id
     * @param  string|null $sub_vend_id
     * @param  string|null $action  One of: ACTV451EM, ACTVAUTH451EM, RPASS451EMA, RPASS451EMSV
     * @return array{status:bool,message:string,email_sent:bool,otp_id:string}
     */
    public static function generateOtp($acc_id, $sub_vend_id = null, $action = null): array
    {
        // 1) Create OTP & persist (secure RNG; ~6 minutes expiry)
        $otp = random_int(100000, 999999);
        $otpId = (string) Str::ulid();

        $data = [
            'otp_id' => $otpId,
            'acc_id' => $acc_id,
            'otp' => $otp,
            'is_used' => 0,
            'expires_at' => Carbon::now()->addMinutes(6),
        ];
        if (!empty($sub_vend_id)) {
            $data['sub_vend_id'] = $sub_vend_id;
        }
        Otp::create($data);

        // 2) Resolve recipient (Account first; if not, SubVendor when provided)
        $emailCipher = null;
        $account = Account::where('acc_id', $acc_id)->first();
        $subVendor = null;

        if ($account && !empty($account->email)) {
            $emailCipher = $account->email;
        }
        if (!$emailCipher && !empty($sub_vend_id)) {
            $subVendor = SubVendor::where('sub_ven_id', $sub_vend_id)->first();
            if ($subVendor && !empty($subVendor->email)) {
                $emailCipher = $subVendor->email;
            }
        }

        // 3) Decrypt email if available
        $emailPlain = null;
        if ($emailCipher) {
            try {
                $dec = EncryptionServiceConnections::decryptData(['email' => $emailCipher]);
                $emailPlain = $dec['data']['email'] ?? null;
            } catch (\Throwable $e) {
                Log::warning('Email decryption failed for OTP', ['acc_id' => $acc_id, 'error' => $e->getMessage()]);
            }
        }

        // 4) Build email digits payload
        $digits = array_map('intval', str_split((string) $otp));

        // 5) Send directly (no queue). Don’t fail OTP if email fails.
        $emailSent = false;
        try {
            if ($emailPlain) {
                if ($action === 'ACTV451EM' && $account) {
                    $mail = [
                        'sender_id' => $acc_id,
                        'recipient_id' => $acc_id,
                        'email' => $emailPlain,
                        'name' => trim($account->firstName . ' ' . $account->lastName),
                        'message' => 'Thank you for creating your PickaPart.ae account. Please enter this code to verify your email address.',
                        'subject' => 'Verify Your Email Address',
                        'data' => $digits,
                    ];
                    MailingServiceConnections::sendEmail($mail);
                    $emailSent = true;
                }

                if ($action === 'ACTVAUTH451EM' && $account) {
                    // New device / suspicious login
                    $authMail = [
                        'sender_id' => $acc_id,
                        'recipient_id' => $acc_id,
                        'email' => $emailPlain,
                        'name' => trim($account->firstName . ' ' . $account->lastName),
                        'message' => 'Your account was accessed from a new device. If this was you, confirm by entering the OTP.',
                        'subject' => 'Suspicious Login Detected',
                        'data' => $digits,
                    ];
                    MailingServiceConnections::authSendEmail($authMail);
                    $emailSent = true;
                }

                if ($action === 'RPASS451EMA' && $account) {
                    $mail = [
                        'sender_id' => $acc_id,
                        'recipient_id' => $acc_id,
                        'email' => $emailPlain,
                        'name' => trim($account->firstName . ' ' . $account->lastName),
                        'message' => "You requested a password change. If this wasn't you, please ignore this email.",
                        'subject' => 'Change your Password',
                        'data' => $digits,
                    ];
                    MailingServiceConnections::sendEmail($mail);
                    $emailSent = true;
                }

                if ($action === 'RPASS451EMSV' && $subVendor) {
                    $mail = [
                        'sender_id' => $sub_vend_id,
                        'recipient_id' => $sub_vend_id,
                        'email' => $emailPlain,
                        'name' => trim($subVendor->first_name . ' ' . $subVendor->last_name),
                        'message' => "You requested a password change. If this wasn't you, please ignore this email.",
                        'subject' => 'Change your Password',
                        'data' => $digits,
                    ];
                    MailingServiceConnections::sendEmail($mail);
                    $emailSent = true;
                }
            } else {
                Log::info('OTP email not sent: empty decrypted email', ['acc_id' => $acc_id, 'action' => $action]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send OTP email', ['acc_id' => $acc_id, 'action' => $action, 'error' => $e->getMessage()]);
            $emailSent = false;
        }

        // 6) Always return otp_id so client can proceed to /verify-otp
        return [
            'status' => true,
            'message' => $emailSent ? 'OTP sent.' : 'OTP created. Email not sent.',
            'email_sent' => $emailSent,
            'otp_id' => $otpId,
        ];
    }

    /**
     * Verify OTP (all operations). Marks OTP used on success.
     * Returns true on success, false otherwise.
     *
     * @param  string      $otp_id
     * @param  string|int  $otp
     * @param  mixed       $device_info
     * @param  string|null $operation_code
     * @return bool
     */
    public static function verifyOtp($otp_id, $otp, $device_info = null, $operation_code = null): bool
    {
        // 1) Locate active OTP
        $otpRecord = Otp::where('otp_id', $otp_id)
            ->where('otp', $otp)
            ->where('is_used', 0)
            ->first();

        if (!$otpRecord) {
            Log::warning('OTP not found or already used', ['otp_id' => $otp_id]);
            return false;
        }

        // 2) Check expiry
        if (Carbon::now()->gt($otpRecord->expires_at)) {
            Log::warning('OTP expired', ['otp_id' => $otp_id, 'expires_at' => $otpRecord->expires_at]);
            return false;
        }

        // 3) Operation-specific side effects
        if ($operation_code === 'ACTV451EM') {
            // Email verification → write a verified Account_state and update system_state_id
            $account = Account::where('acc_id', $otpRecord->acc_id)->first();
            if (!$account) {
                Log::warning('Account not found during email verification', ['acc_id' => $otpRecord->acc_id]);
                return false;
            }

            $payload = [
                'state_code' => 'SYSV4512',
                'state_name' => 'Verified',
                'note' => 'Account email is verified.',
                'reason' => 'Account email verification.',
            ];

            try {
                $enc = EncryptionServiceConnections::encryptData($payload);
                $d = $enc['data'];

                $accountState = Account_state::create([
                    'state_id' => Hash::make(Carbon::now()), // ensure your column can store this size; consider Str::ulid()
                    'acc_id' => $account->acc_id,
                    'doer_acc_id' => $account->acc_id,
                    'state_code' => $d['state_code'] ?? null,
                    'state_name' => $d['state_name'] ?? null,
                    'note' => $d['note'] ?? null,
                    'reason' => $d['reason'] ?? null,
                    'time_period' => Carbon::now()->addYears(99),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                $account->update(['system_state_id' => $accountState->state_id]);
            } catch (\Throwable $e) {
                Log::error('Exception during account verification', ['acc_id' => $otpRecord->acc_id, 'error' => $e->getMessage()]);
                return false;
            }
        }

        // You can add more side-effects for:
        // - ACTVAUTH451EM: trust device / log device_info
        // - RPASS451EMA / RPASS451EMSV: mark for next step, etc.

        // 4) Mark OTP as used
        $otpRecord->is_used = 1;
        $otpRecord->save();

        return true;
    }
}
