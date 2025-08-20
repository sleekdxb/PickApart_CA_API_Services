<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HeaderValidationController extends Controller
{
    /**
     * Validate the required headers for the request.
     */
    public function validateHeaders(Request $request)
    {
        $headers = $request->headers;

        // Validate Content-Type header
        if ($headers->get('Content-Type') !== 'application/json') {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Content-Type. Expected application/json.',
                'data' => []
            ], 400);
        }

        // Validate User-Agent header
        if (!$headers->has('User-Agent') || empty($headers->get('User-Agent'))) {
            return response()->json([
                'status' => false,
                'message' => 'User-Agent header is required.',
                'data' => []
            ], 400);
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
        if (!$headers->has('Authorization') || empty($headers->get('Authorization')) || !str_starts_with($headers->get('Authorization'), 'Bearer ')) {
            return response()->json([
                'status' => false,
                'message' => 'Authorization header must be present and formatted as Bearer token.',
                'data' => []
            ], 400);
        }

        // If all headers are valid, return null (no error).
        return null;
    }
}
