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
        $ipAddress = $request->input('ip') ?? $request->ip();
        $agentInfo = $request->input('agent_info') ?? $request->userAgent();
        $fcmToken = (string) $request->input('fcm_token', '') ?? 'nullTokenWeb';

        if (empty($accIdIn) && empty($emailIn)) {
            return response()->json([
                'status' => false,
                'message' => 'Email or account ID is required.',
                'code' => 422,
            ], 422);
        }

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

        $agentSnapshot = [
            'ip' => $ipAddress,
            'user_agent' => $agentInfo,
            'fcm_token' => $fcmToken ?: null,
        ];

        // Load states (one query)
        $stateIds = array_values(array_filter([$account->action_state_id, $account->system_state_id]));
        $states = collect();
        if ($stateIds) {
            $states = Account_state::query()->whereIn('state_id', $stateIds)->get()->keyBy('state_id');
        }

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

        // Choose session model by account_type (normalize to UPPER)
        $resolvedType = strtoupper((string) $account->account_type);

        $vend = Vendor::where('acc_id', $account->acc_id)->oldest('created_at')->first();
        $vend_id = $vend->vend_id ?? 'No Vendor';

        $sessionMeta = [
            'kind' => 'generic',
            'model' => StrSession::class,
            'fk' => ['acc_id' => $account->acc_id],
        ];

        if ($resolvedType === 'STR' && !empty($account->acc_id)) {
            $sessionMeta = ['kind' => 'str', 'model' => StrSession::class, 'fk' => ['acc_id' => $account->acc_id]];
        } elseif ($resolvedType === 'VENDOR' && !empty($account->acc_id)) {
            $sessionMeta = ['kind' => 'vendor', 'model' => VendorSession::class, 'fk' => ['acc_id' => $account->acc_id]];
        } elseif ($resolvedType === 'GARAGE' && !empty($account->acc_id)) {
            $sessionMeta = ['kind' => 'garage', 'model' => GarageSession::class, 'fk' => ['acc_id' => $account->acc_id]];
        }

        // Check for existing active MAIN session to decide between MAIN vs SUB
        $hasMain = ($sessionMeta['model'])::query()
            ->where($sessionMeta['fk'])
            ->where('isActive', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('end_time')->orWhere('end_time', '>', $now);
            })
            ->where(function ($q) {
                $q->where('session_type', 'MAIN')->orWhereNull('session_type');
            })
            ->exists();

        $sessionType = $hasMain ? 'SUB' : 'MAIN';

        // Create session (always generate JWT), but only RETURN it if MAIN
        [$sessionId, $jwtStored, $jwtForResponse, $otpPayload] = DB::transaction(function () use ($account, $ipAddress, $agentSnapshot, $now, $sessionMeta, $sessionType, $fcmToken) {
            $sessionId = (string) Str::ulid();

            // Always generate a JWT (with helpful claims)
            JWTAuth::factory()->setTTL(config('jwt.ttl'));
            $claims = [
                'sid' => $sessionId,
                'stype' => $sessionType,
                'otp_required' => $sessionType === 'SUB',
            ];
            $jwt = JWTAuth::claims($claims)->fromUser($account);


            $enPayload = EncryptionServiceConnections::encryptData(['fcmToken' => $fcmToken, 'jwt' => $jwt]);
            $jwt = $enPayload['data']['jwt'];
            $fcmToken = $enPayload['data']['fcmToken'];
            $base = [
                'session_id' => $sessionId,
                'ipAddress' => $ipAddress,
                'isActive' => 1,
                'start_time' => $now,
                'end_time' => $now->copy()->addDays(7),
                'life_time' => config('jwt.ttl'),
                'created_at' => $now,
                'updated_at' => $now,
                'lastAccessed' => $now,
                'session_type' => $sessionType,   // "MAIN" | "SUB"
                'access_token' => $jwt,           // persist for both MAIN & SUB
                'fcm_token' => $fcmToken ?: null,
            ];

            $otpPayload = null;
            $sessionData = $agentSnapshot;

            if ($sessionType === 'SUB') {
                // Generate secure 6-digit OTP
                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otpExpiresAt = $now->copy()->addMinutes(10);

                // Hash the OTP before storing (salted)
                $otpHash = hash('sha256', $otp . '|' . $sessionId . '|' . config('app.key'));

                // Persist OTP details
                $base['otp_code_hash'] = $otpHash;
                $base['otp_expires_at'] = $otpExpiresAt;

                // JSON-only metadata (never raw OTP)
                $sessionData['otp_meta'] = ['expires_at' => $otpExpiresAt->toIso8601String()];

                // Your existing OTP helper (kept as-is)
                $otpPayload = OtpHelper::generateOtp($account->acc_id, '', 'ACTVAUTH451EM');
            }

            // Add auth meta
            $sessionData['auth_meta'] = [
                'issued_jwt' => true,
                'session_kind' => $sessionMeta['kind'],
            ];

            $base['sessionData'] = json_encode($sessionData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // Create the session row
            ($sessionMeta['model'])::create(array_merge($base, $sessionMeta['fk']));

            // Only return the token to client if MAIN
            $jwtForResponse = ($sessionType === 'MAIN') ? $jwt : null;

            return [$sessionId, $jwt, $jwtForResponse, $otpPayload];
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
                'vend_id' => $vend_id ?: 'No Vendor',
                'account_type' => $actionState['account_type']
                    ?? $systemState['account_type']
                    ?? $account->account_type,
                'action_state' => $actionState,
                'system_state' => $systemState,
                'access_array' => [],
            ],
        ], 200);
    }



    // Function to handle logout
    public static function logout(Request $request): JsonResponse
    {
        // 1) Quick input checks (keep it simple, no Validator facade)
        $sessionId = (string) $request->input('session_id', '');
        $typeIn = strtoupper((string) $request->input('account_type', ''));

        if ($sessionId === '' || $typeIn === '') {
            return response()->json([
                'status' => false,
                'message' => 'session_id and account_type are required.',
                'code' => 422,
            ], 422);
        }

        // 2) Choose the correct session model by account_type
        $modelMap = [
            'STR' => StrSession::class,
            'VENDOR' => VendorSession::class,   // change to StrSession::class if you want Vendor to share STR sessions
            'GARAGE' => GarageSession::class,
        ];

        if (!isset($modelMap[$typeIn])) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid account_type. Allowed: STR, VENDOR, GARAGE.',
                'code' => 422,
            ], 422);
        }

        $modelClass = $modelMap[$typeIn];

        // 3) Find the session by session_id
        /** @var \Illuminate\Database\Eloquent\Model|null $session */
        $session = $modelClass::where('session_id', $sessionId)->first();

        if (!$session) {
            return response()->json([
                'status' => false,
                'message' => 'Session not found.',
                'code' => 404,
            ], 404);
        }

        // 4) Delete the session record
        $session->delete();

        // 5) Invalidate JWT if the request has a token
        try {
            if (JWTAuth::parser()->setRequest($request)->hasToken()) {
                JWTAuth::invalidate(JWTAuth::getToken());

                return response()->json([
                    'status' => true,
                    'message' => 'Logout successful. Session deleted and token invalidated.',
                    'code' => 200,
                ], 200);
            }

            // No token present — still a successful logout of the session
            return response()->json([
                'status' => true,
                'message' => 'Logout successful. Session deleted (no token provided).',
                'code' => 200,
            ], 200);

        } catch (JWTException $e) {
            // Token might already be expired/blacklisted — session is already removed
            return response()->json([
                'status' => true,
                'message' => 'Session deleted. Token could not be invalidated (maybe already expired/invalid).',
                'code' => 200,
            ], 200);
        }
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
