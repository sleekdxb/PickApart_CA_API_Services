<?php

namespace App\Helpers;
use Illuminate\Http\JsonResponse;
use App\Models\Account;
use App\Models\StrSession;
use App\Models\VendorSession;
use App\Models\GarageSession;
use Illuminate\Http\Request;
use App\Models\Session;
use App\Models\Account_state;  // Make sure AccountState model is imported
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\EncryptionServiceConnections;
use Laravel\Passport\Passport;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;


class LoginHelper
{
    // Function to authenticate user and create a session
    /**
     * Optimized login:
     * - Reads inputs from $request (acc_id/email/password/ip/agent_info)
     * - Resolves account via acc_id or email_hash
     * - Verifies password
     * - Loads action/system states in one go and decrypts fields
     * - Creates a session with ULID and issues JWT in a single transaction
     */
    public static function login(Request $request)
    {
        $now = now();
        $accIdIn = $request->input('acc_id');
        $emailIn = $request->input('email');
        $password = (string) $request->input('password');
        $ipInput = $request->input('ip');                      // optional override
        $agentInfo = $request->input('agent_info') ?? $request->userAgent();
        $fcmToken = (string) ($request->input('fcm_token', '') ?? 'nullTokenWeb');

        // --- Basic validation ---
        if (empty($accIdIn) && empty($emailIn)) {
            return response()->json([
                'status' => false,
                'message' => 'Email or account ID is required.',
                'code' => 422,
            ], 422);
        }

        // --- Fetch account ---
        $selectCols = [
            'acc_id',
            'password',
            'firstName',
            'lastName',
            'profile_url',
            'email',
            'phone',
            'account_type',
            'action_state_id',
            'system_state_id',
        ];
        if (!empty($accIdIn)) {
            $account = Account::query()->select($selectCols)->where('acc_id', $accIdIn)->first();
        } else {
            $emailPlain = mb_strtolower(trim($emailIn));
            $emailHash = hash('sha256', $emailPlain);
            $account = Account::query()->select($selectCols)->where('email_hash', $emailHash)->first();
        }

        if (!$account) {
            return response()->json([
                'status' => false,
                'message' => "You don’t have an account yet. Please register to access Pickapart.ca's features.",
                'code' => 401,
            ], 401);
        }

        if (!Hash::check($password, $account->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Incorrect password.',
                'code' => 401,
            ], 401);
        }

        // --- Resolve client IPs (headers + server) ---
        $cfIp = $request->header('CF-Connecting-IP');
        $xffRaw = $request->header('X-Forwarded-For');
        $xri = $request->header('X-Real-IP');
        $xff = $xffRaw ? trim(explode(',', $xffRaw)[0]) : null; // left-most
        $remote = $request->ip();
        $validIp = fn($ip) => (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)) ? $ip : null;
        $effectiveIp = $validIp($ipInput)
            ?? $validIp($cfIp)
            ?? $validIp($xff)
            ?? $validIp($xri)
            ?? $validIp($remote)
            ?? '0.0.0.0';

        $agentSnapshot = [
            'user_agent' => $agentInfo,
            'fcm_token' => $fcmToken ?: null,
            'ip_sources' => [
                'input' => $validIp($ipInput),
                'cf_connecting_ip' => $validIp($cfIp),
                'x_forwarded_for_0' => $validIp($xff),
                'x_real_ip' => $validIp($xri),
                'remote_addr' => $validIp($remote),
                'effective' => $effectiveIp,
            ],
        ];

        // --- States (1 query) ---
        $stateIds = array_values(array_filter([$account->action_state_id, $account->system_state_id]));
        $states = $stateIds
            ? Account_state::query()->whereIn('state_id', $stateIds)->get()->keyBy('state_id')
            : collect();

        $decryptState = function (?Account_state $state, array $extra = []) {
            if (!$state)
                return [];
            $payload = array_merge([
                'reason' => $state->reason,
                'state_code' => $state->state_code,
                'state_name' => $state->state_name,
            ], $extra);
            $dec = EncryptionServiceConnections::decryptData($payload);
            $d = $dec['data'] ?? [];
            return [
                'reason' => $d['reason'] ?? null,
                'state_code' => $d['state_code'] ?? null,
                'state_name' => $d['state_name'] ?? null,
                'account_type' => $d['account_type'] ?? ($extra['account_type'] ?? null),
                'time_period' => $state->time_period ?? null,
            ];
        };

        $actionState = $decryptState($states->get($account->action_state_id), ['account_type' => $account->account_type]);
        $systemState = $decryptState($states->get($account->system_state_id), ['account_type' => $account->account_type]);

        // --- Session model by account_type ---
        $resolvedType = strtoupper((string) $account->account_type);
        $sessionMeta = ['kind' => 'generic', 'model' => StrSession::class, 'fk' => ['acc_id' => $account->acc_id]];
        if ($resolvedType === 'STR' && !empty($account->acc_id))
            $sessionMeta = ['kind' => 'str', 'model' => StrSession::class, 'fk' => ['acc_id' => $account->acc_id]];
        if ($resolvedType === 'VENDOR' && !empty($account->acc_id))
            $sessionMeta = ['kind' => 'vendor', 'model' => VendorSession::class, 'fk' => ['acc_id' => $account->acc_id]];
        if ($resolvedType === 'GARAGE' && !empty($account->acc_id))
            $sessionMeta = ['kind' => 'garage', 'model' => GarageSession::class, 'fk' => ['acc_id' => $account->acc_id]];

        // --- Optional: vendor id quick lookup ---
        $vend_id = Vendor::query()->where('acc_id', $account->acc_id)->orderBy('created_at')->value('vend_id') ?? 'No Vendor';

        // --- Device fingerprint & previous session comparison ---
        $deviceFp = hash('sha256', ($agentInfo ?? '') . '|' . $effectiveIp);

        $prevSession = ($sessionMeta['model'])::query()
            ->where($sessionMeta['fk'])
            ->orderByDesc('lastAccessed')
            ->orderByDesc('created_at')
            ->first(['session_id', 'session_type', 'sessionData', 'access_token', 'isActive', 'end_time', 'fcm_token']);

        $prevFp = null;
        if ($prevSession && !empty($prevSession->sessionData)) {
            $psd = json_decode($prevSession->sessionData, true);
            $prevFp = $psd['security']['device_fp'] ?? null;
        }

        // treat first-ever login as "same device" (no OTP)
        $newDevice = $prevFp ? ($prevFp !== $deviceFp) : false;

        // Check for an active MAIN session that already matches this device fingerprint (to reuse)
        $activeMainSameDevice = null;
        if (!$newDevice) {
            $activeMainSameDevice = ($sessionMeta['model'])::query()
                ->where($sessionMeta['fk'])
                ->where('isActive', 1)
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_time')->orWhere('end_time', '>', $now);
                })
                ->where('session_type', 'MAIN')
                ->orderByDesc('lastAccessed')
                ->get(['session_id', 'sessionData', 'access_token', 'fcm_token'])
                ->first(function ($row) use ($deviceFp) {
                    $sd = json_decode($row->sessionData ?? '{}', true);
                    return ($sd['security']['device_fp'] ?? null) === $deviceFp;
                });
        }

        // If same device and there is an active MAIN for this device → reuse it (return its token; refresh lastAccessed & fcm)
        if (!$newDevice && $activeMainSameDevice) {
            // Optionally refresh FCM & lastAccessed
            try {
                $enc = EncryptionServiceConnections::encryptData(['fcmToken' => $fcmToken]);
                $fcmEnc = $enc['data']['fcmToken'] ?? null;
            } catch (\Throwable $e) {
                $fcmEnc = null;
            }

            ($sessionMeta['model'])::where('session_id', $activeMainSameDevice->session_id)
                ->update([
                    'lastAccessed' => $now,
                    'fcm_token' => $fcmEnc ?? $activeMainSameDevice->fcm_token,
                    'ipAddress' => $effectiveIp, // update current IP used
                    'updated_at' => $now,
                ]);

            // Decrypt JWT if stored encrypted
            $jwtOut = $activeMainSameDevice->access_token;
            try {
                $dec = EncryptionServiceConnections::decryptData(['jwt' => $jwtOut]);
                $jwtOut = $dec['data']['jwt'] ?? $jwtOut;
            } catch (\Throwable $e) {
                // keep stored value
            }

            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'code' => 200,
                'token' => $jwtOut,
                'session_id' => $activeMainSameDevice->session_id,
                'otp_id' => null,
                'account' => [
                    'firstName' => $account->firstName,
                    'lastName' => $account->lastName,
                    'profile_url' => $account->profile_url,
                    'acc_id' => $account->acc_id,
                    'vend_id' => $vend_id,
                    'account_type' => $actionState['account_type']
                        ?? $systemState['account_type']
                        ?? $account->account_type,
                    'action_state' => $actionState,
                    'system_state' => $systemState,
                    'access_array' => [],
                ],
            ], 200);
        }

        // Otherwise: create a new session.
        // If device is different → SUB (OTP). If same device (no reusable MAIN) → MAIN with token.
        $sessionType = $newDevice ? 'SUB' : 'MAIN';

        [$sessionId, $jwtStored, $jwtForResponse, $otpPayload] = DB::transaction(function () use ($account, $effectiveIp, $agentSnapshot, $now, $sessionMeta, $sessionType, $fcmToken, $deviceFp, $newDevice) {
            $sessionId = (string) Str::ulid();

            // JWT
            JWTAuth::factory()->setTTL(config('jwt.ttl'));
            $claims = [
                'sid' => $sessionId,
                'stype' => $sessionType,
                'otp_required' => $sessionType === 'SUB',
            ];
            $jwt = JWTAuth::claims($claims)->fromUser($account);

            // Encrypt & persist JWT + FCM
            $enPayload = EncryptionServiceConnections::encryptData(['fcmToken' => $fcmToken, 'jwt' => $jwt]);
            $jwtEnc = $enPayload['data']['jwt'];
            $fcmEnc = $enPayload['data']['fcmToken'];

            $base = [
                'session_id' => $sessionId,
                'ipAddress' => $effectiveIp,
                'isActive' => 1,
                'start_time' => $now,
                'end_time' => $now->copy()->addDays(7),
                'life_time' => config('jwt.ttl'),
                'created_at' => $now,
                'updated_at' => $now,
                'lastAccessed' => $now,
                'session_type' => $sessionType,   // MAIN | SUB
                'access_token' => $jwtEnc,        // persist for both MAIN & SUB
                'fcm_token' => $fcmEnc ?: null,
            ];

            $sessionData = [
                'agent' => ['ua' => $agentSnapshot['user_agent'] ?? null],
                'ip_sources' => $agentSnapshot['ip_sources'],
                'auth_meta' => ['issued_jwt' => true, 'session_kind' => $sessionMeta['kind']],
                'security' => [
                    'device_fp' => $deviceFp,
                    'new_device' => $newDevice,
                ],
            ];

            $otpPayload = null;
            if ($sessionType === 'SUB') {
                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otpExp = $now->copy()->addMinutes(10);
                $otpHash = hash('sha256', $otp . '|' . $sessionId . '|' . config('app.key'));
                $base['otp_code_hash'] = $otpHash;
                $base['otp_expires_at'] = $otpExp;
                $sessionData['otp_meta'] = ['expires_at' => $otpExp->toIso8601String()];
                // send/queue OTP via helper (SUB flow)
                $otpPayload = OtpHelper::generateOtp($account->acc_id, '', 'ACTVAUTH451EM');
            }

            $base['sessionData'] = json_encode($sessionData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // Insert session
            ($sessionMeta['model'])::create(array_merge($base, $sessionMeta['fk']));

            // Only return token if MAIN
            $jwtForResponse = ($sessionType === 'MAIN') ? $jwtEnc : null;

            return [$sessionId, $jwtEnc, $jwtForResponse, $otpPayload];
        });

        return response()->json([
            'status' => true,
            'message' => $sessionType === 'MAIN' ? 'Login successful' : 'OTP required',
            'code' => 200,
            'token' => $jwtForResponse,   // null for SUB (until OTP verified)
            'session_id' => $sessionId,
            'otp_id' => $otpPayload['otp_id'] ?? null,
            'account' => [
                'firstName' => $account->firstName,
                'lastName' => $account->lastName,
                'profile_url' => $account->profile_url,
                'acc_id' => $account->acc_id,
                'vend_id' => $vend_id,
                'account_type' => $actionState['account_type']
                    ?? $systemState['account_type']
                    ?? $account->account_type,
                'action_state' => $actionState,
                'system_state' => $systemState,
                'access_array' => [],
            ],
        ], 200);
    }





    public static function logout(Request $request): JsonResponse
    {
        // 1) Inputs
        $sessionId = trim((string) $request->input('session_id', ''));
        $typeIn = strtoupper(trim((string) $request->input('account_type', '')));

        if ($sessionId === '' || $typeIn === '') {
            return response()->json([
                'status' => false,
                'message' => 'session_id and account_type are required.',
                'code' => 422,
            ], 422);
        }

        // 2) Map account_type -> (model FQCN as string, table name)
        //    Using strings avoids fatal errors if a class doesn't exist.
        $map = [
            'STR' => ['\\App\\Models\\StrSession', 'STR_Sessions'],
            'VENDOR' => ['\\App\\Models\\VendorSession', 'Vendor_Sessions'],
            'GARAGE' => ['\\App\\Models\\GarageSession', 'Garage_Sessions'],
        ];

        if (!isset($map[$typeIn])) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid account_type. Allowed: STR, VENDOR, GARAGE.',
                'code' => 422,
            ], 422);
        }

        [$modelFqcn, $table] = $map[$typeIn];

        // 3) Find the session by session_id in the intended store
        $session = null;     // Eloquent model instance (if class exists)
        $sessionRow = null;     // stdClass row from DB (if model class missing)
        $usingModel = false;
        $usingTable = false;

        if (class_exists($modelFqcn)) {
            // Eloquent lookup
            /** @var \Illuminate\Database\Eloquent\Model|null $found */
            $found = $modelFqcn::where('session_id', $sessionId)->first();
            if ($found) {
                $session = $found;
                $usingModel = true;
            }
        }

        if (!$session) {
            // Fallback to query builder (no model class or not found in expected table)
            $row = DB::table($table)->where('session_id', $sessionId)->first();
            if ($row) {
                $sessionRow = $row;
                $usingTable = true;
            }
        }

        // 3b) If still not found, scan all other session tables (handles mismatched account_type)
        if (!$session && !$sessionRow) {
            foreach ($map as $typeKey => [$fqcn, $tbl]) {
                if ($typeKey === $typeIn)
                    continue;
                if (class_exists($fqcn)) {
                    $probe = $fqcn::where('session_id', $sessionId)->first();
                    if ($probe) {
                        $session = $probe;
                        $usingModel = true;
                        $typeIn = $typeKey;
                        [$modelFqcn, $table] = [$fqcn, $tbl];
                        break;
                    }
                } else {
                    $row = DB::table($tbl)->where('session_id', $sessionId)->first();
                    if ($row) {
                        $sessionRow = $row;
                        $usingTable = true;
                        $typeIn = $typeKey;
                        [$modelFqcn, $table] = [$fqcn, $tbl];
                        break;
                    }
                }
            }
        }

        if (!$session && !$sessionRow) {
            return response()->json([
                'status' => false,
                'message' => 'Session not found.',
                'code' => 404,
            ], 404);
        }

        // 4) Get stored token (works for either model or row), then decrypt if needed
        $storedTokenEnc = $usingModel ? $session->access_token : ($sessionRow->access_token ?? '');
        $storedToken = $storedTokenEnc;

        try {
            $dec = EncryptionServiceConnections::decryptData(['jwt' => $storedTokenEnc]);
            $storedToken = $dec['data']['jwt'] ?? $storedTokenEnc;
        } catch (\Throwable $e) {
            // best effort; keep raw stored token
        }

        // 5) Invalidate JWT(s)
        $invalidatedStored = false;
        $invalidatedHeader = false;

        if (is_string($storedToken) && $storedToken !== '') {
            try {
                JWTAuth::setToken($storedToken)->invalidate(true);
                $invalidatedStored = true;
            } catch (JWTException $e) {
                // maybe already expired/blacklisted
            }
        }

        try {
            if (JWTAuth::parser()->setRequest($request)->hasToken()) {
                $headerToken = (string) JWTAuth::getToken();
                if ($headerToken !== '' && $headerToken !== $storedToken) {
                    try {
                        JWTAuth::setToken($headerToken)->invalidate(true);
                        $invalidatedHeader = true;
                    } catch (JWTException $e) {
                        // ignore
                    }
                }
            }
        } catch (JWTException $e) {
            // ignore parser errors
        }

        // 6) Remove the session (Eloquent if model exists, else DB)
        try {
            if ($usingModel) {
                $session->delete();
            } else {
                DB::table($table)->where('session_id', $sessionId)->delete();
            }
        } catch (\Throwable $e) {
            // if deletion fails, still report token invalidation status below
        }

        // 7) Respond
        $parts = ['Logout successful. Session deleted.'];
        if ($invalidatedStored || $invalidatedHeader) {
            $parts[] = 'Token invalidated.';
        } else {
            $parts[] = 'Token could not be invalidated (maybe already expired/invalid).';
        }

        return response()->json([
            'status' => true,
            'message' => implode(' ', $parts),
            'code' => 200,
        ], 200);
    }

    public static function buildAgentSnapshot(Request $request, string $accountId): array
    {
        // Useful headers for device/browser inference (add/remove as you like)
        $whitelist = [
            'user-agent',
            'accept-language',
            'accept',
            'accept-encoding',
            'dnt',
            'sec-ch-ua',
            'sec-ch-ua-mobile',
            'sec-ch-ua-platform',
            'sec-fetch-site',
            'sec-fetch-mode',
            'sec-fetch-dest',
            'x-requested-with',
            'referer',
            'origin',
            'x-real-ip',
            'x-forwarded-for',
            'cf-connecting-ip',
            'cf-ipcountry',
        ];

        // Collect whitelisted headers (stringify and trim length)
        $headers = [];
        foreach ($whitelist as $key) {
            $val = $request->headers->get($key);
            if ($val !== null) {
                $headers[$key] = Str::limit(
                    is_array($val) ? implode(', ', $val) : (string) $val,
                    512,
                    '…'
                );
            }
        }

        // IPs (client + chain)
        $clientIp = $request->ip();
        $xfwdChain = array_filter(array_map('trim', explode(',', (string) ($headers['x-forwarded-for'] ?? ''))));
        $realIp = $headers['x-real-ip'] ?? null;
        $cfConnIp = $headers['cf-connecting-ip'] ?? null;
        $allIps = array_values(array_unique(array_filter([$clientIp, $realIp, $cfConnIp, ...$xfwdChain])));

        // Deterministic fingerprint (don’t use bcrypt/Hash::make here; we need stable value)
        $fingerprintSource = implode('|', [
            $accountId,
            $clientIp,
            $headers['user-agent'] ?? '',
            $headers['accept-language'] ?? '',
            $headers['sec-ch-ua'] ?? '',
            $headers['sec-ch-ua-platform'] ?? '',
            $headers['sec-ch-ua-mobile'] ?? '',
        ]);
        $deviceFingerprint = hash('sha256', $fingerprintSource);

        // Minimal device summary (best-effort, no UA parser dependency)
        $deviceSummary = [
            'is_mobile_hint' => isset($headers['sec-ch-ua-mobile']) && stripos($headers['sec-ch-ua-mobile'], '?1') !== false,
            'platform_hint' => $headers['sec-ch-ua-platform'] ?? null,
            'browser_hint' => $headers['sec-ch-ua'] ?? null,
        ];

        return [
            'user_agent' => $request->userAgent(),
            'headers' => $headers,
            'ips' => [
                'client' => $clientIp,
                'chain' => $allIps,
            ],
            'device_summary' => $deviceSummary,
            'device_fingerprint' => $deviceFingerprint,
            'requested_at' => now()->toIso8601String(),
            'path' => $request->path(),
            'method' => $request->method(),
        ];
    }
}
