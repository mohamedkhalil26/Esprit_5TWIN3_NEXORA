<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inscription d'un membre à un événement (table pivot de la relation N-N).
 */
class Participation extends Model
{
    use HasFactory;

    protected $fillable = [
        'evenement_id',
        'user_id',
        'statut',
        'commentaire',
    ];

    public const STATUTS = [
        'inscrit' => ['Inscrit', 'primary'],
        'present' => ['Présent', 'success'],
        'absent' => ['Absent', 'warning'],
        'annule' => ['Annulé', 'secondary'],
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut][0] ?? $this->statut;
    }

    public function getStatutCouleurAttribute(): string
    {
        return self::STATUTS[$this->statut][1] ?? 'secondary';
    }
}
