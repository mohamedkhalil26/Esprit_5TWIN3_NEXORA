<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReclamationHistory extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['changes' => 'array'];
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reclamation(): BelongsTo
    {
        return $this->belongsTo(Reclamation::class);
    }
}
