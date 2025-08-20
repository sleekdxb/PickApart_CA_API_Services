<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Membership extends Model implements JWTSubject
{
    use HasFactory;

    // The table associated with the model
    protected $table = 'memberships';

    // The primary key associated with the table
    protected $primaryKey = 'id';

    // The "type" of the auto-incrementing ID (bigint in your case)
    protected $keyType = 'int';

    //protected $guard = 'vendor-guard';

    // Define which attributes are mass assignable
    protected $fillable = [
        'transaction_id',
        'acc_id',
        'start_date',
        'end_date',
        'type',
        'status',
        'allowance_level',
    ];

    // Define which attributes should be cast to specific data types
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'allowance_level' => 'integer',
    ];

    // Optionally, if you want to disable the timestamps (created_at and updated_at), uncomment below line
    // public $timestamps = false;

    /**
     * Get the identifier for the JWT.
     * This is usually the primary key of the model (e.g. `id`).
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        // Typically, it returns the primary key, i.e., the ID of the model.
        return $this->getKey();  // This returns the value of the 'id' attribute by default.
    }

    /**
     * Get the custom claims for the JWT.
     * This is used to add extra information to the JWT payload.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        // You can add any custom claims you want here, like roles or permissions.
        return [
            'membership_type' => $this->type,  // Example of adding the membership type to the JWT claims
            'status' => $this->status,         // Example of adding the membership status to the JWT claims
        ];
    }
}
