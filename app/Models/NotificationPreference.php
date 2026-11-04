<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'locale', 'categories', 'channels', 'frequency', 'enabled'];

    protected function casts(): array
    {
        return ['categories' => 'array', 'channels' => 'array', 'enabled' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
