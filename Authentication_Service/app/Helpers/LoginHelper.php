<?php

namespace App\Helpers;

use App\Models\Account;
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
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginHelper
{
    // Function to authenticate user and create a session
    public static function login($acc_id, $email, $password, $ipAddress, $agentInfo, $request)
    {
        // ✅ Step 1: Find the account by acc_id or decrypted email
        $account = $acc_id ? Account::where('acc_id', $acc_id)->first() : null;

        if (!$account && $email) {
            $account = Account::whereNotNull('email')->get()->first(function ($acc) use ($email) {
                $decrypted = EncryptionServiceConnections::decryptData(['email' => $acc->email]);
                return isset($decrypted['data']['email']) && $decrypted['data']['email'] === $email;
            });
        }

        // ✅ Step 2: Check if account exists
        if (!$account) {
            return response()->json([
                'status' => false,
                'message' => "You don’t have an account yet. Please register to access Pickapart.ca's features.",
                'code' => 401
            ], 401);
        }

        // ✅ Step 3: Verify password
        if (!Hash::check($password, $account->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Incorrect password.',
                'code' => 401
            ], 401);
        }
        $agentSnapshot = self::buildAgentSnapshot($request, (string) $account->acc_id);

        // ✅ Step 4: Create session
        $session = Session::create([
            'session_id' => Hash::make($agentInfo . now()),
            'acc_id' => $account->acc_id,
            'ipAddress' => $ipAddress,
            'isActive' => 1,
            'start_time' => now(),
            'end_time' => now()->addDays(2),
            'life_time' => 2880,
            'created_at' => now(),
            'updated_at' => now(),
            'lastAccessed' => now(),
            'sessionData' => json_encode($agentSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        // ✅ Step 5: Generate token
        $token = JWTAuth::fromUser($account);

        // ✅ Step 6: Get state details
        $actionStateDetails = $account->action_state_id ? Account_state::where('state_id', $account->action_state_id)->first() : null;
        $systemStateDetails = Account_state::where('state_id', $account->system_state_id)->first();

        // ✅ Step 7: Decrypt Action State
        $actionState = [];
        if ($actionStateDetails) {
            $actionDecrypt = EncryptionServiceConnections::decryptData([
                'reason' => $actionStateDetails->reason,
                'state_code' => $actionStateDetails->state_code,
                'state_name' => $actionStateDetails->state_name,
                'account_type' => $account->account_type,
            ]);

            if (isset($actionDecrypt['data'])) {
                $actionState = [
                    'reason' => $actionDecrypt['data']['reason'] ?? null,
                    'state_code' => $actionDecrypt['data']['state_code'] ?? null,
                    'state_name' => $actionDecrypt['data']['state_name'] ?? null,
                    'account_type' => $actionDecrypt['data']['account_type'] ?? null,
                    'time_period' => $actionStateDetails->time_period ?? null,
                ];
            }
        }

        // ✅ Step 8: Decrypt System State
        $systemState = [];
        if ($systemStateDetails) {
            $systemDecrypt = EncryptionServiceConnections::decryptData([
                'reason' => $systemStateDetails->reason,
                'state_code' => $systemStateDetails->state_code,
                'state_name' => $systemStateDetails->state_name,
                'account_type' => $account->account_type,
                'email' => $account->email,
                'phone' => $account->phone,
            ]);

            if (isset($systemDecrypt['data'])) {
                $systemState = [
                    'reason' => $systemDecrypt['data']['reason'] ?? null,
                    'state_code' => $systemDecrypt['data']['state_code'] ?? null,
                    'state_name' => $systemDecrypt['data']['state_name'] ?? null,
                    'account_type' => $systemDecrypt['data']['account_type'] ?? null,
                    'email' => $systemDecrypt['data']['email'] ?? null,
                    'phone' => $systemDecrypt['data']['phone'] ?? null,
                    'time_period' => $systemStateDetails->time_period ?? null,
                ];
            }
        }

        // ✅ Step 9: Get Vendor ID
        $vend = Vendor::where('acc_id', $account->acc_id)->oldest('created_at')->first();
        $vend_id = $vend->vend_id ?? 'No Vendor';

        // ✅ Step 10: Return the response
        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'code' => 200,
            'token' => $token,
            'session_id' => $session->session_id,
            'account' => [
                'firstName' => $account->firstName,
                'lastName' => $account->lastName,
                'profile_url' => json_encode($account->profile_url),
                'email' => $systemState['email'] ?? $account->email,
                'phone' => $systemState['phone'] ?? $account->phone,
                'acc_id' => $account->acc_id,
                'vend_id' => $vend_id,
                'account_type' => $actionState['account_type'] ?? $systemState['account_type'] ?? $account->account_type,
                'action_state' => $actionState,
                'system_state' => $systemState,
                'access_array' => [],
            ],
        ], 200);
    }


    // Function to handle logout
    public static function logout(Request $request)
    {
        // Delete the session
        $session = Session::where('session_id', $request->session_id)->first();
        if (!$session) {
            return response()->json([
                'status' => false,
                'message' => 'Session not found.',
                'code' => 404
            ], 404);
        }

        $session->delete(); // Delete the session record

        try {
            // Invalidate the JWT token
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'status' => true,
                'message' => 'Logout successful. Session deleted and token invalidated.',
                'code' => 200
            ], 200);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to invalidate token. Maybe already expired?',
                'code' => 500
            ], 500);
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
