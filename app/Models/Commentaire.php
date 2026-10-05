<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commentaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'user_id',
        'contenu',
        'approuve',
    ];

    protected function casts(): array
    {
        return [
            'approuve' => 'boolean',
        ];
    }

    /**
     * Le commentaire appartient à un article (N-1).
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
