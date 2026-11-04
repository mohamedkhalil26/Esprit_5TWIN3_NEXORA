<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reclamation extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updated(function (Reclamation $record) {
            $changes = [];
            foreach (['statut', 'priorite', 'categorie'] as $field) {
                if ($record->wasChanged($field)) {
                    $changes[$field] = ['avant' => $record->getRawOriginal($field), 'apres' => $record->$field];
                }
            }
            if ($changes) {
                ReclamationHistory::create(['reclamation_id' => $record->id, 'user_id' => auth()->id(), 'changes' => $changes]);
            }
        });
    }

    public function historique(): HasMany
    {
        return $this->hasMany(ReclamationHistory::class)->with('auteur')->latest('id');
    }

    protected $fillable = [
        'user_id',
        'sujet',
        'description',
        'categorie',
        'priorite',
        'statut',
        'piece_jointe',
    ];

    public const CATEGORIES = [
        'technique' => ['Problème technique', 'gear'],
        'paiement' => ['Paiement', 'credit-card'],
        'equipement' => ['Équipement défectueux', 'lightning-charge'],
        'comportement' => ['Comportement d\'un membre', 'person-exclamation'],
        'autre' => ['Autre', 'question-circle'],
    ];

    public const PRIORITES = [
        'basse' => ['Basse', 'secondary'],
        'moyenne' => ['Moyenne', 'info'],
        'haute' => ['Haute', 'warning'],
        'urgente' => ['Urgente', 'danger'],
    ];

    public const STATUTS = [
        'ouverte' => ['Ouverte', 'danger'],
        'en_cours' => ['En cours de traitement', 'warning'],
        'resolue' => ['Résolue', 'success'],
        'fermee' => ['Fermée', 'secondary'],
    ];

    /* ------------------------------------------------------------------
     | Relations
     * ------------------------------------------------------------------ */

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Une réclamation reçoit plusieurs réponses (1-N), dans l'ordre chronologique.
     */
    public function reponses(): HasMany
    {
        return $this->hasMany(ReponseReclamation::class)->oldest();
    }

    /* ------------------------------------------------------------------
     | Accesseurs
     * ------------------------------------------------------------------ */

    public function getCategorieLabelAttribute(): string
    {
        return self::CATEGORIES[$this->categorie][0] ?? $this->categorie;
    }

    public function getCategorieIconeAttribute(): string
    {
        return self::CATEGORIES[$this->categorie][1] ?? 'question-circle';
    }

    public function getPrioriteLabelAttribute(): string
    {
        return self::PRIORITES[$this->priorite][0] ?? $this->priorite;
    }

    public function getPrioriteCouleurAttribute(): string
    {
        return self::PRIORITES[$this->priorite][1] ?? 'secondary';
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut][0] ?? $this->statut;
    }

    public function getStatutCouleurAttribute(): string
    {
        return self::STATUTS[$this->statut][1] ?? 'secondary';
    }

    public function getPieceJointeUrlAttribute(): ?string
    {
        return $this->piece_jointe ? route('reclamations.attachment', $this) : null;
    }

    public function estFermee(): bool
    {
        return $this->statut === 'fermee';
    }

    /* ------------------------------------------------------------------
     | Scopes
     * ------------------------------------------------------------------ */

    public function scopeFiltrer(Builder $query, array $filtres): Builder
    {
        return $query
            ->when($filtres['q'] ?? null, function (Builder $q, $recherche) {
                $q->where(function (Builder $sous) use ($recherche) {
                    $sous->where('sujet', 'like', "%{$recherche}%")
                        ->orWhereHas('auteur', fn (Builder $u) => $u->where('name', 'like', "%{$recherche}%"));
                });
            })
            ->when($filtres['statut'] ?? null, fn (Builder $q, $statut) => $q->where('statut', $statut))
            ->when($filtres['priorite'] ?? null, fn (Builder $q, $priorite) => $q->where('priorite', $priorite))
            ->when($filtres['categorie'] ?? null, fn (Builder $q, $categorie) => $q->where('categorie', $categorie));
    }
}
