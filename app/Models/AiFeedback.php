<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFeedback extends Model
{
    protected $table = 'ai_feedback';

    protected $fillable = ['user_id', 'ai_feature_run_id', 'feature', 'feedback_type', 'rating', 'comment', 'resource_type', 'resource_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AiFeatureRun::class, 'ai_feature_run_id');
    }
}
