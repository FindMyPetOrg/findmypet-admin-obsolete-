<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrivateMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'receiver_id', 'user_id', 'description', 'seen'
    ];

    public function sender(): BelongsTo {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
