<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evenement extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'type',
        'lieu',
        'ville',
        'date_debut',
        'date_fin',
        'capacite',
        'prix',
        'image',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
            'capacite' => 'integer',
            'prix' => 'decimal:2',
        ];
    }

    public const TYPES = [
        'atelier' => ['Atelier pratique', 'tools'],
        'formation' => ['Formation', 'mortarboard'],
        'conference' => ['Conférence', 'mic'],
        'sortie' => ['Sortie découverte', 'tree'],
    ];

    public const STATUTS = [
        'planifie' => ['Planifié', 'primary'],
        'annule' => ['Annulé', 'danger'],
        'termine' => ['Terminé', 'secondary'],
    ];

    /* ------------------------------------------------------------------
     | Relations
     * ------------------------------------------------------------------ */

    /**
     * Inscriptions à l'événement (1-N vers la table participations).
     */
    public function participations(): HasMany
    {
        return $this->hasMany(Participation::class);
    }

    /**
     * Membres inscrits : relation N-N via la table pivot "participations".
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'participations')
            ->withPivot(['id', 'statut', 'commentaire'])
            ->withTimestamps();
    }

    /* ------------------------------------------------------------------
     | Accesseurs
     * ------------------------------------------------------------------ */

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }

    public function getTypeIconeAttribute(): string
    {
        return self::TYPES[$this->type][1] ?? 'calendar-event';
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut][0] ?? $this->statut;
    }

    public function getStatutCouleurAttribute(): string
    {
        return self::STATUTS[$this->statut][1] ?? 'secondary';
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset($this->image) : null;
    }

    /**
     * Nombre d'inscrits actifs (hors annulations).
     */
    public function getNbInscritsAttribute(): int
    {
        return $this->participations_actives_count
            ?? $this->participations()->where('statut', '!=', 'annule')->count();
    }

    public function getPlacesRestantesAttribute(): int
    {
        return max(0, $this->capacite - $this->nb_inscrits);
    }

    public function getEstCompletAttribute(): bool
    {
        return $this->places_restantes === 0;
    }

    public function getEstPasseAttribute(): bool
    {
        return $this->date_fin->isPast();
    }

    public function getPrixLabelAttribute(): string
    {
        return (float) $this->prix > 0 ? number_format($this->prix, 2, ',', ' ').' DT' : 'Gratuit';
    }

    public function inscriptionsOuvertes(): bool
    {
        return $this->statut === 'planifie' && ! $this->est_passe && ! $this->est_complet;
    }

    /* ------------------------------------------------------------------
     | Scopes
     * ------------------------------------------------------------------ */

    public function scopeAVenir(Builder $query): Builder
    {
        return $query->where('date_debut', '>=', now())->where('statut', 'planifie');
    }

    public function scopeAvecNbInscrits(Builder $query): Builder
    {
        return $query->withCount(['participations as participations_actives_count' => fn ($q) => $q->where('statut', '!=', 'annule')]);
    }

    public function scopeFiltrer(Builder $query, array $filtres): Builder
    {
        return $query
            ->when($filtres['q'] ?? null, fn (Builder $q, $recherche) => $q->where(function (Builder $sous) use ($recherche) {
                $sous->where('titre', 'like', "%{$recherche}%")->orWhere('lieu', 'like', "%{$recherche}%");
            }))
            ->when($filtres['type'] ?? null, fn (Builder $q, $type) => $q->where('type', $type))
            ->when($filtres['statut'] ?? null, fn (Builder $q, $statut) => $q->where('statut', $statut))
            ->when($filtres['ville'] ?? null, fn (Builder $q, $ville) => $q->where('ville', 'like', "%{$ville}%"))
            ->when(($filtres['periode'] ?? null) === 'a_venir', fn (Builder $q) => $q->where('date_debut', '>=', now()))
            ->when(($filtres['periode'] ?? null) === 'passes', fn (Builder $q) => $q->where('date_fin', '<', now()));
    }
}
