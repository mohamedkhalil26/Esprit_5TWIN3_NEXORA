<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiFeatureRun extends Model
{
    protected $fillable = ['user_id', 'feature', 'audience', 'module', 'provider', 'model', 'prompt_version', 'locale', 'status', 'error_code', 'input_hash', 'duration_ms', 'source_type', 'source_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(AiFeedback::class);
    }
}
