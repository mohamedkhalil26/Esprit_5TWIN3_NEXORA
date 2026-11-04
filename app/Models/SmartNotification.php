<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartNotification extends Model
{
    protected $fillable = ['user_id', 'category', 'priority', 'status', 'title', 'content', 'resource_type', 'resource_id', 'dedupe_key', 'read_at', 'sent_at', 'expires_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'sent_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
