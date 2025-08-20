<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AccountFileState extends Model
{
    protected $table = 'account_file_state';

    protected $fillable = [
        'acc_media_id',
        'acc_id',
        'state_id',
        'doer_id',
        'linked_to_id',
        'state_name',
        'state_code',
        'note',
        'reason',
    ];

    // Optional: define which fields are encrypted
    protected array $decryptable = [
        'note',
        'reason',
    ];

    // Relationship
    public function file()
    {
        return $this->belongsTo(AccountMediaFile::class, 'acc_media_id');
    }

    // Accessors to automatically decrypt when retrieving
    public function getNoteAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function getReasonAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function getStateNameAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    protected function decryptIfNeeded($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // Return original if not decryptable
        }
    }
}
