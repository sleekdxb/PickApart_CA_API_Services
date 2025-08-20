<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountsMedia extends Model
{
    use HasFactory;

    // Specify the table name (optional if it follows Laravel's convention)
    protected $table = 'accounts_media';

    // Define the primary key (optional if it's 'id')
    protected $primaryKey = 'id';

    // Define the columns that are mass assignable
    protected $fillable = [
        'acc_media_id',
        'acc_id',
        'gra_id',
        'sub_ven_id',
        'vend_id',
        'file_name',
        'file_path',
        'file_size',
        'media_type',
        'upload_date',
        'expiry_date',
        'created_at',
        'updated_at',
    ];

    // Timestamps are enabled by default in Laravel
    public $timestamps = true;

    // Optionally, if the primary key is not auto-incrementing (e.g., UUID), you can set this:
    public $incrementing = true;

    // Define the data type of the primary key (default is 'int')
    protected $keyType = 'int'; // 'bigint' is the default for bigIncrements

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vend_id'); // vend_id is the foreign key
    }

    public function state()
    {
        return $this->hasMany(AccountFileState::class, 'acc_media_id', 'acc_media_id');
    }
}
