<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiQuizDefinition extends Model
{
    protected $fillable = ['article_id', 'version', 'locale', 'questions', 'answer_key', 'status', 'published_at'];

    protected $hidden = ['answer_key'];

    protected function casts(): array
    {
        return ['questions' => 'array', 'answer_key' => 'encrypted:array', 'published_at' => 'datetime'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AiQuizAttempt::class);
    }
}
