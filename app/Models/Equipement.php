<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipement extends Model
{
    use HasFactory;

    protected $fillable = [
        'categorie_id',
        'user_id',
        'nom',
        'marque',
        'description',
        'puissance_watts',
        'capacite_wh',
        'prix_jour',
        'caution',
        'etat',
        'ville',
        'disponible',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'prix_jour' => 'decimal:2',
            'caution' => 'decimal:2',
            'disponible' => 'boolean',
            'puissance_watts' => 'integer',
            'capacite_wh' => 'integer',
        ];
    }

    public const ETATS = [
        'neuf' => 'Neuf',
        'tres_bon' => 'Très bon état',
        'bon' => 'Bon état',
        'usage' => 'Usagé',
    ];

    /* ------------------------------------------------------------------
     | Relations
     * ------------------------------------------------------------------ */

    /**
     * L'équipement appartient à une catégorie (N-1).
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    /**
     * Le particulier qui propose l'équipement (N-1 vers users).
     */
    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* ------------------------------------------------------------------
     | Accesseurs
     * ------------------------------------------------------------------ */

    public function getEtatLabelAttribute(): string
    {
        return self::ETATS[$this->etat] ?? $this->etat;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset($this->image) : null;
    }

    /* ------------------------------------------------------------------
     | Scopes (recherche et filtres)
     * ------------------------------------------------------------------ */

    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('disponible', true);
    }

    /**
     * Applique les filtres envoyés par les formulaires de recherche.
     */
    public function scopeFiltrer(Builder $query, array $filtres): Builder
    {
        return $query
            ->when($filtres['q'] ?? null, function (Builder $q, $recherche) {
                $q->where(function (Builder $sous) use ($recherche) {
                    $sous->where('nom', 'like', "%{$recherche}%")
                        ->orWhere('marque', 'like', "%{$recherche}%")
                        ->orWhere('description', 'like', "%{$recherche}%");
                });
            })
            ->when($filtres['categorie'] ?? null, fn (Builder $q, $id) => $q->where('categorie_id', $id))
            ->when($filtres['ville'] ?? null, fn (Builder $q, $ville) => $q->where('ville', 'like', "%{$ville}%"))
            ->when($filtres['prix_max'] ?? null, fn (Builder $q, $prix) => $q->where('prix_jour', '<=', $prix))
            ->when(isset($filtres['disponible']) && $filtres['disponible'] !== '', fn (Builder $q) => $q->where('disponible', (bool) $filtres['disponible']));
    }
}
