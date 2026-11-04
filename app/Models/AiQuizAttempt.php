<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiQuizAttempt extends Model
{
    protected $fillable = ['ai_quiz_definition_id', 'user_id', 'score', 'total', 'answers', 'completed_at'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'completed_at' => 'datetime'];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(AiQuizDefinition::class, 'ai_quiz_definition_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
