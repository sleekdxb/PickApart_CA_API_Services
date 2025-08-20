<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HeaderValidationController extends Controller
{
    /**
     * Validate the required headers for the request.
     */
    public function validateHeaders(Request $request)
    {
        $headers = $request->headers;

        // Validate Content-Type header to check if it's multipart/form-data with a boundary
        $contentType = $headers->get('Content-Type');
        if (strpos($contentType, 'multipart/form-data') !== 0 || !preg_match('/boundary=([^;]+)/', $contentType)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Content-Type. Expected multipart/form-data with boundary.',
                'data' => []
            ], 400);
        }

        // Relaxed: Log User-Agent if present, but don't fail if missing
        $userAgent = $headers->get('User-Agent');
        if (empty($userAgent)) {
            Log::info('User-Agent header is missing or not exposed (likely browser security policy)');
        } else {
            Log::info('User-Agent: ' . $userAgent);
        }

        // Validate X-Request-ID header
        if (!$headers->has('X-Request-ID') || empty($headers->get('X-Request-ID'))) {
            return response()->json([
                'status' => false,
                'message' => 'X-Request-ID header is required.',
                'data' => []
            ], 400);
       }

        // Validate Cache-Control header
        if ($headers->get('Cache-Control') !== 'no-cache') {
            return response()->json([
                'status' => false,
                'message' => 'Cache-Control header must be set to no-cache.',
                'data' => []
            ], 400);
        }

        // Validate Authorization header
        if (
            !$headers->has('Authorization') ||
            empty($headers->get('Authorization')) ||
            !str_starts_with($headers->get('Authorization'), 'Bearer ')
        ) {
            return response()->json([
                'status' => false,
                'message' => 'Authorization header must be present and formatted as Bearer token.',
                'data' => []
            ], 400);
        }

        // If all other headers are valid, return null (no error).
        return null;
    }
}
