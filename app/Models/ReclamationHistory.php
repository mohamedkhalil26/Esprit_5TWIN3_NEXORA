<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
