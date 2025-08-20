<?php

namespace App\Models;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;


class Staff extends Authenticatable implements JWTSubject
{
    use HasApiTokens, Notifiable;
    // Table name (optional if it matches the plural form)
    protected $table = 'staff';

    // Primary key
    protected $primaryKey = 'id';

    // Non-incrementing and non-numeric primary key


    // Mass-assignable fields
    protected $fillable = [
        'staff_id',
        'first_name',
        'last_name',
        'work_email',
        'password',
        'job_title',
        'phone',
        'passport_number',
        'passport_expire_date',
        'salary',
        'working_shift',
        'department',
        'job_description',
        'shift_start_time',
        'shift_end_time',
    ];



    // Dates (optional, Laravel handles timestamps by default)
    protected $dates = [
        'passport_expire_date',
        'shift_start_time',
        'shift_end_time',
        'created_at',
        'updated_at',
    ];


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
