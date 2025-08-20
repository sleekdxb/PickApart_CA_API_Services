<?php

namespace App\Helpers;

use Google\Client as GoogleClient;
// SDK is optional; we only use it if installed. 'use' is harmless if not present.
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\FieldValue;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FirebaseMessagingHelper
{
    /**
     * Read project id from config, fail fast if missing.
     */
    protected static function projectId(): string
    {
        $projectId = (string) config('services.fcm.project_id');
        if ($projectId === '') {
            throw new \RuntimeException('FCM project_id is not configured.');
        }
        return $projectId;
    }

    /**
     * Read service account path from config, ensure readable.
     */
    protected static function credentialsPath(): string
    {
        $path = (string) config('services.fcm.credentials_path');

        try {
            if ($path === '') {
                throw new \RuntimeException('config("services.fcm.credentials_path") is empty');
            }

            // Resolve relative paths against the project root
            $isAbsolute = (bool) preg_match('#^([A-Za-z]:[\\/]|/)#', $path);
            $resolved = $isAbsolute ? $path : base_path($path);

            if (!is_readable($resolved)) {
                Log::error('FCM credentials not readable', [
                    'configured' => $path,
                    'resolved' => $resolved,
                    'exists' => file_exists($resolved),
                    'readable' => is_readable($resolved),
                    'realpath' => realpath($resolved) ?: null,
                    'cwd' => getcwd(),
                ]);

                // Fallback for "app/json/..." -> storage/app/json/...
                if (strpos($path, 'app/json/') === 0) {
                    $alt = storage_path(substr($path, strlen('app/')));
                    if (is_readable($alt)) {
                        return $alt;
                    }
                    Log::error('FCM credentials fallback also unreadable', [
                        'fallback' => $alt,
                        'exists' => file_exists($alt),
                        'readable' => is_readable($alt),
                    ]);
                }

                // Probe for native warning (for logs)
                set_error_handler(function ($severity, $message) use (&$resolved) {
                    Log::error('FCM credentials PHP file error', [
                        'resolved' => $resolved,
                        'message' => $message,
                        'severity' => $severity,
                    ]);
                    return true;
                });
                @file_get_contents($resolved);
                restore_error_handler();

                throw new \RuntimeException("Unreadable credentials file: {$resolved}");
            }

            return $resolved;

        } catch (\Throwable $e) {
            Log::error($e->getMessage(), ['configured' => $path]);
            throw $e;
        }
    }

    /**
     * Cache TTL for both FCM & Firestore tokens.
     */
    protected static function cacheTtl(): int
    {
        return (int) config('services.fcm.cache_ttl', 3000);
    }

    /**
     * Collection to log into (main).
     */
    protected static function firestoreCollection(): string
    {
        return (string) config('services.fcm.firestore_collection', 'fcm_messages');
    }

    /**
     * OPTIONAL secondary mirror path when acc_id exists.
     * Example: users/{acc_id}/messages
     */
    protected static function userMessagesPath(string $accId): string
    {
        return "users/{$accId}/messages";
    }

    /* =========================
     * Firestore via SDK (optional)
     * ========================= */

    protected static function firestore(): FirestoreClient
    {
        $transport = env('FIRESTORE_TRANSPORT'); // 'grpc' | 'rest' | null
        if ($transport === null) {
            $transport = extension_loaded('grpc') ? 'grpc' : 'rest';
        }

        $args = [
            'projectId' => self::projectId(),
            'keyFilePath' => self::credentialsPath(),
            'transport' => $transport,
        ];

        if ($endpoint = env('FIRESTORE_API_ENDPOINT')) {
            $args['apiEndpoint'] = $endpoint; // e.g. "firestore.googleapis.com:443"
        }

        return new FirestoreClient($args);
    }

    /* =========================
     * Firestore via REST (no SDK / no gRPC)
     * ========================= */

    protected static function firestoreDatabase(): string
    {
        return (string) config('services.fcm.firestore_database', '(default)');
    }

    protected static function firestoreBaseUrl(): string
    {
        return rtrim((string) config('services.fcm.firestore_base_url', 'https://firestore.googleapis.com'), '/');
    }

    protected static function getFirestoreToken(): string
    {
        $cacheKey = 'firestore_access_token_' . self::projectId();

        return Cache::remember($cacheKey, self::cacheTtl(), function () {
            $client = new GoogleClient();
            $client->setAuthConfig(self::credentialsPath());
            // Narrow scope works; cloud-platform also OK.
            $client->addScope('https://www.googleapis.com/auth/datastore');
            $client->refreshTokenWithAssertion();
            $token = $client->getAccessToken();

            if (empty($token['access_token'])) {
                throw new \RuntimeException('Failed to obtain Firestore access token');
            }

            return $token['access_token'];
        });
    }

    protected static function toFirestoreValue($val): array
    {
        if (is_null($val)) {
            return ['nullValue' => null];
        }
        if (is_bool($val)) {
            return ['booleanValue' => $val];
        }
        if (is_int($val)) {
            return ['integerValue' => (string) $val];
        }
        if (is_float($val)) {
            return ['doubleValue' => $val];
        }
        if (is_string($val)) {
            return ['stringValue' => $val];
        }
        if (is_array($val)) {
            $isList = array_keys($val) === range(0, count($val) - 1);
            if ($isList) {
                return ['arrayValue' => ['values' => array_map([self::class, 'toFirestoreValue'], $val)]];
            } else {
                $fields = [];
                foreach ($val as $k => $v) {
                    $fields[(string) $k] = self::toFirestoreValue($v);
                }
                return ['mapValue' => ['fields' => $fields]];
            }
        }
        return ['stringValue' => json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    }

    protected static function firestoreCommitCreate(string $collectionPath, array $fields, ?string $docId = null): array
    {
        $docId = $docId ?: (string) Str::uuid();
        $project = self::projectId();
        $db = self::firestoreDatabase();

        $docName = sprintf(
            'projects/%s/databases/%s/documents/%s/%s',
            $project,
            $db,
            trim($collectionPath, '/'),
            $docId
        );

        $fsFields = [];
        foreach ($fields as $k => $v) {
            $fsFields[(string) $k] = self::toFirestoreValue($v);
        }

        $writes = [
            ['update' => ['name' => $docName, 'fields' => $fsFields]],
            [
                'transform' => [
                    'document' => $docName,
                    'fieldTransforms' => [
                        [
                            'fieldPath' => 'created_at',
                            'setToServerValue' => 'REQUEST_TIME',
                        ]
                    ],
                ]
            ],
        ];

        $url = sprintf('%s/v1/projects/%s/databases/%s/documents:commit', self::firestoreBaseUrl(), $project, $db);
        $resp = Http::withToken(self::getFirestoreToken())
            ->acceptJson()->asJson()->timeout(15)
            ->post($url, ['writes' => $writes]);

        if ($resp->successful()) {
            Log::info('Firestore REST write OK', ['collection' => $collectionPath, 'docId' => $docId]);
            return ['ok' => true, 'status' => $resp->status(), 'response' => $resp->json(), 'docId' => $docId];
        }

        Log::error('Firestore REST write FAILED', ['status' => $resp->status(), 'body' => $resp->body()]);
        return ['ok' => false, 'status' => $resp->status(), 'response' => $resp->json(), 'docId' => $docId];
    }

    /**
     * Unified writer: use SDK if available, else REST.
     */
    protected static function firestoreWriteDoc(string $collectionPath, array $doc, ?string $docId = null): array
    {
        $hasSdk = class_exists(FirestoreClient::class) && class_exists(FieldValue::class);

        if ($hasSdk) {
            try {
                $db = self::firestore();
                $ref = $docId
                    ? $db->collection($collectionPath)->document($docId)
                    : $db->collection($collectionPath)->newDocument();

                $doc['created_at'] = FieldValue::serverTimestamp();
                $ref->set($doc);

                // Try several ways to get the ID
                $id = $docId;
                if (method_exists($ref, 'id')) {
                    $id = $ref->id();
                } elseif (method_exists($ref, 'name')) {
                    $name = $ref->name();
                    $id = is_string($name) ? basename($name) : $docId;
                } elseif (method_exists($ref, 'path')) {
                    $path = $ref->path();
                    $id = is_string($path) ? basename($path) : $docId;
                }

                Log::info('Firestore SDK write OK', ['collection' => $collectionPath, 'docId' => $id]);
                return ['ok' => true, 'status' => 200, 'response' => ['docId' => $id], 'docId' => $id];
            } catch (\Throwable $e) {
                Log::warning('Firestore SDK write failed; falling back to REST', ['error' => $e->getMessage()]);
                // fall through to REST
            }
        }

        // REST fallback
        return self::firestoreCommitCreate($collectionPath, $doc, $docId);
    }

    /* =========================
     * Capture your exact call-site fields
     * ========================= */

    protected static function extractIndexFields(array $payload): array
    {
        $msg = $payload['message'] ?? [];
        $notif = $msg['notification'] ?? [];
        $data = $msg['data'] ?? [];

        return [
            'title' => isset($notif['title']) ? (string) $notif['title'] : null,
            'body' => isset($notif['body']) ? (string) $notif['body'] : null,
            'notification_id' => isset($data['notification_id']) ? (string) $data['notification_id'] : null,
            'type' => isset($data['type']) ? (string) $data['type'] : null,
            'acc_id' => isset($data['acc_id']) ? (string) $data['acc_id'] : null,
            'vend_id' => isset($data['vend_id']) ? (string) $data['vend_id'] : null,
        ];
    }

    /**
     * Ensure data keys/values are strings (FCM requirement) and match your call-site merge:
     * - keep keys even if null (cast to empty string)
     * - JSON encode non-scalars with UNESCAPED_UNICODE/SLASHES
     */
    protected static function stringifyValues(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if ($v === null) {
                $out[(string) $k] = '';
                continue;
            }
            $out[(string) $k] = is_scalar($v)
                ? (string) $v
                : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return $out;
    }

    /* =========================
     * FCM access token
     * ========================= */

    public static function getAccessToken(): string
    {
        $cacheKey = 'fcm_access_token_' . self::projectId();

        return Cache::remember($cacheKey, self::cacheTtl(), function () {
            $client = new GoogleClient();
            $client->setAuthConfig(self::credentialsPath());
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->refreshTokenWithAssertion();
            $token = $client->getAccessToken();

            if (empty($token['access_token'])) {
                throw new \RuntimeException('Failed to obtain FCM access token');
            }

            return $token['access_token'];
        });
    }

    /* =========================
     * Public send methods
     * ========================= */

    /**
     * Send to a single device token (and log to Firestore).
     */
    public static function sendToToken(
        string $token,
        array $notification,
        array $data = [],
        array $android = [],
        array $apns = []
    ): array {
        $payload = [
            'message' => array_filter([
                'token' => $token,
                'notification' => $notification ?: null,
                'data' => self::stringifyValues($data),
                'android' => $android ?: null,
                'apns' => $apns ?: null,
            ]),
        ];

        $result = self::dispatch($payload);

        // Build extra fields for top-level storage
        $extra = [
            'notification' => $notification ?: null,
            'data' => !empty($data) ? self::stringifyValues($data) : null,
            'android' => $android ?: null,
            'apns' => $apns ?: null,
        ];

        self::logMessageToFirestore('token', $token, $payload, $result, $extra);

        return $result;
    }

    /**
     * Send to a topic (and log to Firestore).
     */
    public static function sendToTopic(
        string $topic,
        array $notification,
        array $data = [],
        array $android = [],
        array $apns = []
    ): array {
        $payload = [
            'message' => array_filter([
                'topic' => $topic,
                'notification' => $notification ?: null,
                'data' => self::stringifyValues($data),
                'android' => $android ?: null,
                'apns' => $apns ?: null,
            ]),
        ];

        $result = self::dispatch($payload);

        $extra = [
            'notification' => $notification ?: null,
            'data' => !empty($data) ? self::stringifyValues($data) : null,
            'android' => $android ?: null,
            'apns' => $apns ?: null,
        ];

        self::logMessageToFirestore('topic', $topic, $payload, $result, $extra);

        return $result;
    }

    /**
     * Send to multiple device tokens (fan-out) and log each one.
     */
    public static function sendMulticast(
        array $tokens,
        array $notification,
        array $data = [],
        array $android = [],
        array $apns = []
    ): array {
        $results = [];
        foreach ($tokens as $t) {
            $payload = [
                'message' => array_filter([
                    'token' => $t,
                    'notification' => $notification ?: null,
                    'data' => self::stringifyValues($data),
                    'android' => $android ?: null,
                    'apns' => $apns ?: null,
                ]),
            ];

            try {
                $res = self::dispatch($payload);
                $results[$t] = $res;

                $extra = [
                    'notification' => $notification ?: null,
                    'data' => !empty($data) ? self::stringifyValues($data) : null,
                    'android' => $android ?: null,
                    'apns' => $apns ?: null,
                ];
                self::logMessageToFirestore('token', $t, $payload, $res, $extra);
            } catch (\Throwable $e) {
                $err = [
                    'ok' => false,
                    'status' => null,
                    'response' => null,
                    'error' => $e->getMessage(),
                    'traceId' => (string) Str::uuid(),
                ];
                $results[$t] = $err;

                $extra = [
                    'notification' => $notification ?: null,
                    'data' => !empty($data) ? self::stringifyValues($data) : null,
                    'android' => $android ?: null,
                    'apns' => $apns ?: null,
                ];
                self::logMessageToFirestore('token', $t, $payload, $err, $extra);
            }
        }
        return $results;
    }

    /* =========================
     * Core HTTP to FCM
     * ========================= */

    protected static function dispatch(array $payload): array
    {
        $accessToken = self::getAccessToken();
        $url = "https://fcm.googleapis.com/v1/projects/" . self::projectId() . "/messages:send";

        $resp = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->post($url, $payload);

        if ($resp->successful()) {
            return [
                'ok' => true,
                'status' => $resp->status(),
                'response' => $resp->json(),
            ];
        }

        throw new \RuntimeException(sprintf(
            'FCM error (%d): %s',
            $resp->status(),
            $resp->body()
        ));
    }

    /* =========================
     * Firestore logging wrapper
     * ========================= */

    protected static function logMessageToFirestore(
        string $targetType,
        string $target,
        array $payload,
        array $result,
        array $extra = []
    ): void {
        try {
            // Pull the important fields from the same structure you send
            $idx = self::extractIndexFields($payload);

            // Build the Firestore document
            $doc = array_merge([
                'project_id' => self::projectId(),
                'target_type' => $targetType,              // 'token' | 'topic'
                'target' => $target,
                'title' => $idx['title'],
                'body' => $idx['body'],
                'account_type' => $idx['account_type'],
                'notification_id' => $idx['notification_id'],
                'type' => $idx['type'],
                'acc_id' => $idx['acc_id'],
                'vend_id' => $idx['vend_id'],
                'data' => $extra,
                'message' => $payload['message'] ?? [],  // full payload (incl. data)
                'ok' => $result['ok'] ?? false,
                'http_status' => $result['status'] ?? null,
                'fcm_response' => $result['response'] ?? null,
                'message_id' => $result['response']['name'] ?? null, // FCM v1 "name"
            ], $extra);

            // Prefer a stable docId so re-sends overwrite the same notification
            $preferredDocId = $idx['notification_id'] ?: null;

            // 1) Main collection
            $main = self::firestoreWriteDoc(self::firestoreCollection(), $doc, $preferredDocId);
            if (!$main['ok']) {
                Log::warning('Firestore main write non-OK', $main);
            }

            // 2) Optional mirror to users/{acc_id}/messages if acc_id is present
            if (!empty($idx['acc_id'])) {
                $mirror = self::firestoreWriteDoc(self::userMessagesPath((string) $idx['acc_id']), $doc, $preferredDocId);
                if (!$mirror['ok']) {
                    Log::warning('Firestore user mirror write non-OK', $mirror);
                }
            }

        } catch (\Throwable $e) {
            Log::error('Firestore log failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'project_id' => self::projectId(),
            ]);

            if (config('services.fcm.firestore_hard_fail', false)) {
                throw $e;
            }
        }
    }

    /* =========================
     * Healthcheck (optional)
     * ========================= */

    public static function firestoreHealthcheck(): array
    {
        try {
            $doc = ['healthcheck' => true, 'note' => 'hello from healthcheck'];
            $res = self::firestoreWriteDoc(self::firestoreCollection(), $doc);
            return [
                'ok' => $res['ok'] ?? false,
                'id' => $res['docId'] ?? null,
                'via' => (class_exists(FirestoreClient::class) ? 'sdk' : 'rest'),
            ];
        } catch (\Throwable $e) {
            Log::error('Firestore healthcheck FAILED', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
