<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentEmail extends Model
{
    use HasFactory;

    // Specify the table associated with the model
    protected $table = 'sent_emails';

    // Specify the primary key if it differs from the default 'id'
    protected $primaryKey = 'id';

    // Indicate if the model should be timestamped
    public $timestamps = true;

    // Specify the fillable fields for mass assignment
    protected $fillable = [
        'recipient_id', 
        'sender_id', 
        'recipient_email',
        'recipient_name', 
        'subject', 
        'body', 
        'status', 
        'sent_at'
    ];

    // Optionally define relationships with other models
     public function recipient() {
         return $this->belongsTo(Account::class, 'recipient_id');
     }
}
