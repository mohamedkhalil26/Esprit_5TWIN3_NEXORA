<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventBlueprint extends Model
{
    protected $fillable = ['user_id', 'title', 'status', 'proposal_data'];

    protected function casts(): array
    {
        return ['proposal_data' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
