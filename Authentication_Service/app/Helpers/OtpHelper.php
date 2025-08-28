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
use App\Models\StrSession;
use App\Models\VendorSession;
use App\Models\GarageSession;
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


    public static function verifyOtp($otp_id, $otp, $device_info = null, $operation_code = null, $session_id = null): array
    {
        $now = Carbon::now();

        // 1) Locate active, unused OTP
        $otpRecord = Otp::where('otp_id', $otp_id)
            ->where('otp', $otp)
            ->where('is_used', 0)
            ->first();

        if (!$otpRecord) {
            Log::warning('OTP not found or already used', ['otp_id' => $otp_id]);
            return ['ok' => false, 'message' => 'Invalid OTP.'];
        }

        // 2) Check expiry
        if ($now->gt($otpRecord->expires_at)) {
            Log::warning('OTP expired', ['otp_id' => $otp_id, 'expires_at' => $otpRecord->expires_at]);
            return ['ok' => false, 'message' => 'Expired OTP.'];
        }

        try {
            // ------------------ EMAIL VERIFICATION ------------------
            if ($operation_code === 'ACTV451EM') {
                $account = Account::where('acc_id', $otpRecord->acc_id)->first();
                if (!$account) {
                    Log::warning('Account not found during email verification', ['acc_id' => $otpRecord->acc_id]);
                    return ['ok' => false, 'message' => 'Account not found.'];
                }

                $payload = [
                    'state_code' => 'SYSV4512',
                    'state_name' => 'Verified',
                    'note' => 'Account email is verified.',
                    'reason' => 'Account email verification.',
                ];

                $enc = EncryptionServiceConnections::encryptData($payload);
                $d = $enc['data'] ?? [];

                $accountState = Account_state::create([
                    'state_id' => (string) Str::ulid(),
                    'acc_id' => $account->acc_id,
                    'doer_acc_id' => $account->acc_id,
                    'state_code' => $d['state_code'] ?? null,
                    'state_name' => $d['state_name'] ?? null,
                    'note' => $d['note'] ?? null,
                    'reason' => $d['reason'] ?? null,
                    'time_period' => $now->copy()->addYears(99),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $account->update(['system_state_id' => $accountState->state_id]);

                // 4) Mark OTP as used (after successful verification)
                $otpRecord->is_used = 1;
                $otpRecord->save();

                return ['ok' => true, 'message' => 'Email verified'];
            }

            // ------------------ AUTH VERIFICATION (RETURN TOKEN) ------------------
            if ($operation_code === 'ACTVAUTH451EM') {
                // OTP is valid & unexpired at this point; proceed conditionally
                // 3a) Load account and DECRYPT account_type before using it
                $account = Account::where('acc_id', $otpRecord->acc_id)
                    ->first(['acc_id', 'account_type']);

                if (!$account) {
                    Log::warning('Account not found during auth verification', ['acc_id' => $otpRecord->acc_id]);
                    return ['ok' => false, 'message' => 'Account not found.'];
                }

                $accountTypeRaw = (string) ($account->account_type ?? '');
                try {
                    // If stored encrypted, decrypt; otherwise this is a no-op/fallback
                    $dec = EncryptionServiceConnections::decryptData(['account_type' => $accountTypeRaw]);
                    $accountTypeRaw = (string) ($dec['data']['account_type'] ?? $accountTypeRaw);
                } catch (\Throwable $e) {
                    Log::warning('account_type_decrypt_failed', [
                        'acc_id' => $account->acc_id,
                        'msg' => $e->getMessage(),
                    ]);
                }
                $resolvedType = strtoupper($accountTypeRaw);

                // 3b) Choose session model AFTER we have the verified & decrypted account_type
                $sessionModel = StrSession::class; // default STR/generic
                if ($resolvedType === 'Vendor') {
                    $sessionModel = VendorSession::class;
                } elseif ($resolvedType === 'Garage') {
                    $sessionModel = GarageSession::class;
                } // else STR stays default

                // 3c) Find the session (only after OTP verified)
                $sessionQuery = $sessionModel::query()
                    ->where('acc_id', $account->acc_id)
                    ->where('isActive', 1)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('end_time')->orWhere('end_time', '>', $now);
                    });

                if (!empty($session_id)) {
                    // Enforce that provided session_id belongs to this account
                    $sessionQuery->where('session_id', $session_id);
                } else {
                    // No session_id passed → prefer SUB, else MAIN, most recent
                    $sessionQuery->whereIn('session_type', ['SUB', 'MAIN'])
                        ->orderByDesc('lastAccessed')
                        ->orderByDesc('created_at');
                }

                $session = $sessionQuery->first(['session_id', 'access_token']);

                if (!$session) {
                    Log::warning('Active session not found for OTP auth', [
                        'acc_id' => $account->acc_id,
                        'type' => $resolvedType,
                        'session_id' => $session_id,
                    ]);
                    return ['ok' => false, 'message' => 'No active session found.'];
                }

                // 3d) Decrypt JWT if stored encrypted
                $jwtOut = $session->access_token;
                try {
                    $dec = EncryptionServiceConnections::decryptData(['jwt' => $jwtOut]);
                    $jwtOut = $dec['data']['jwt'] ?? $jwtOut;
                } catch (\Throwable $e) {
                    Log::warning('jwt_decrypt_failed', [
                        'acc_id' => $account->acc_id,
                        'sid' => $session->session_id,
                        'msg' => $e->getMessage(),
                    ]);
                }

                // Optional: record trusted device info
                if (is_array($device_info) && !empty($device_info)) {
                    Log::info('ACTVAUTH451EM device_info', [
                        'acc_id' => $account->acc_id,
                        'session_id' => $session->session_id,
                        'device_info' => $device_info,
                    ]);
                }

                // 4) Mark OTP as used (only AFTER everything above succeeds)
                $otpRecord->is_used = 1;
                $otpRecord->save();

                // 5) Return token & session (conditional to verified OTP)
                return [
                    'ok' => true,
                    'message' => 'OTP verified successfully',
                    'access_token' => $jwtOut,
                    'account_type' => $resolvedType,
                    'session_id' => $session->session_id,
                ];
            }

            // Unknown/unsupported operation
            Log::warning('Unsupported operation_code for OTP', ['operation_code' => $operation_code]);
            return ['ok' => false, 'message' => 'Unsupported operation.'];

        } catch (\Throwable $e) {
            Log::error('Exception during OTP verification', [
                'otp_id' => $otp_id,
                'operation_code' => $operation_code,
                'error' => $e->getMessage(),
            ]);
            return ['ok' => false, 'message' => 'Verification failed.'];
        }
    }
}
