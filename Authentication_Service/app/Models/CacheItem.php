<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
class CacheItem extends Model
{
    protected $table = 'cache'; // The table to interact with
    
    protected $fillable = [
        'key', 'value', 'expires_at'
    ];

    // Mutator for the value attribute to decode if it's stored as JSON
    public function getValueAttribute($value)
    {
        // If you are storing data as JSON, decode it to return an array or object
        return json_decode($value, true); // Decode JSON to an array
    }

    // Mutator to encode the value before saving it as JSON
    public function setValueAttribute($value)
    {
        // If you're storing data as JSON, encode it before saving it
        $this->attributes['value'] = json_encode($value); // Store as JSON string
    }

    // Method to check if the cache item has expired
    public function isExpired()
    {
        return $this->expires_at && Carbon::now()->gt($this->expires_at);
    }

    // Custom method to retrieve cache value and delete it if expired
    public static function getCacheValue($key)
    {
        // Retrieve the cache item by key
        $cacheItem = self::where('key', $key);
        Log::info($cacheItem);
        // If the cache item exists, check if it is expired
        if ($cacheItem) {
            // If expired, delete it and return null
           
               
                return $cacheItem; // Cache is expired, so return null
        
                // If not expired, return the value
           
        }

        // Return null if the cache item does not exist
        return null;
    }

    // Custom method to add or update a cache entry (TTL in minutes)
    public static function setCacheValue($key, $value, $ttlInMinutes)
    {
        // Calculate the expiration time based on TTL in minutes
        $expires_at = Carbon::now()->addMinutes($ttlInMinutes); // Add minutes to current time

        // Check if the cache item already exists, update it if it does, or create a new one
        $cacheItem = self::updateOrCreate(
            ['key' => $key], // Find by key, or create if it doesn't exist
            [
                'value' => $value, // Store the cache value
                'expires_at' => $expires_at, // Set the expiration time
            ]
        );

        return $cacheItem;
    }
}
