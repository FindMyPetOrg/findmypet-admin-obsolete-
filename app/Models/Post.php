<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'description', 'lat', 'lng', 'reward', 'type', 'images', 'tags'
    ];

    protected $casts = [
        'tags' => 'array',
        'images' => 'array'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
