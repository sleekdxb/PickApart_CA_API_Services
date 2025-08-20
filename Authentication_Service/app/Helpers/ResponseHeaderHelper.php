<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Config;

class ResponseHeaderHelper
{
    /**
     * Set dynamic response headers on the provided response object.
     *
     * @param \Illuminate\Http\Response $response
     * @param string|null $connection
     * @param string|null $cacheControl
     * @param string|null $contentType
     * @param string|null $date
     * @return \Illuminate\Http\Response
     */
    public static function setResponseHeaders(
        $response,
        $connection = 'close',  // Default to 'close'
        $cacheControl = 'no-cache',  // Default to 'no-cache'
        $contentType = 'application/json',  // Default to 'application/json'
        $date = null  // Default to null (will be set to current date if not passed)
    ) {
        // If date is not provided, set it to the current date in GMT
        $date = $date ?: gmdate('D, d M Y H:i:s') . ' GMT';

        // Get dynamic rate limit values
        $rateLimitKey = Request::ip(); // Assuming we're rate-limiting by IP address
        $rateLimitLimit = RateLimiter::remaining($rateLimitKey, 60); // Default limit is 60
        $rateLimitRemaining = RateLimiter::remaining($rateLimitKey, 60);

        // Retrieve Access-Control-Allow-Origin value from config (default: '*')
        $allowedOrigins = Config::get('cors.allowed_origins', '*'); // Default to '*' if not configured

        // Set headers directly on the response object
        $response->headers->set('Host', Request::getHost());
        $response->headers->set('Connection', $connection);
        $response->headers->set('X-Powered-By', 'PHP/' . phpversion());
        $response->headers->set('Cache-Control', $cacheControl);
        $response->headers->set('Date', $date);
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('X-RateLimit-Limit', $rateLimitLimit);
        $response->headers->set('X-RateLimit-Remaining', $rateLimitRemaining);
        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigins);

        // Return the modified response object
        return $response;
    }
}
