<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;



class AccountMediaFile extends Model
{
    protected $table = 'accounts_media';

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
    ];

    public $timestamps = true;

    // public function account()
    //  {
    //     return $this->belongsTo(Vendor::class, 'vend_id'); // vend_id is the foreign key
    // }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'acc_id', 'acc_id');
    }

    public function state()
    {
        return $this->hasMany(AccountMediaState::class, 'acc_media_id', 'acc_media_id');
    }

    public function memberships()
    {
        return $this->hasMany(Memberships::class, 'acc_id', 'acc_id');
    }


}

