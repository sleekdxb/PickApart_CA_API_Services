<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    use HasFactory;
    // The name of the table
    protected $table = 'garages';

    // Set the primary key for the model (if different from the default 'id')
    protected $primaryKey = 'id';

    // The attributes that are mass assignable
    protected $fillable = [
        'id',
        'gra_id',
        'acc_id',
        'profile_state_doc_array_id',
        'business_name',
        'location',
        'community',
        'office_building',
        'official_email',
        'official_phone',
        'owner_id_number',
        'owner_id_full_name',
        'trade_license_number',
        'profile_doc_state_array_id',
        'state_position',
        'isOwner',
        'created_at',
        'updated_at',
    ];
}
