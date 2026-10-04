<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipement_id',
        'user_id',
        'date_debut',
        'date_fin',
        'nb_jours',
        'montant_total',
        'statut',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'nb_jours' => 'integer',
            'montant_total' => 'decimal:2',
        ];
    }

    /**
     * Statuts possibles : [libellé, couleur du badge Bootstrap].
     */
    public const STATUTS = [
        'en_attente' => ['En attente', 'warning'],
        'confirmee' => ['Confirmée', 'info'],
        'en_cours' => ['En cours', 'primary'],
        'terminee' => ['Terminée', 'success'],
        'annulee' => ['Annulée', 'secondary'],
    ];

    /**
     * Statuts qui bloquent l'équipement sur la période.
     */
    public const STATUTS_ACTIFS = ['en_attente', 'confirmee', 'en_cours'];

    /* ------------------------------------------------------------------
     | Relations
     * ------------------------------------------------------------------ */

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class);
    }

    public function locataire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Une réservation possède plusieurs paiements (1-N).
     */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class)->latest('date_paiement');
    }

    /* ------------------------------------------------------------------
     | Accesseurs
     * ------------------------------------------------------------------ */

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut][0] ?? $this->statut;
    }

    public function getStatutCouleurAttribute(): string
    {
        return self::STATUTS[$this->statut][1] ?? 'secondary';
    }

    public function getMontantPayeAttribute(): float
    {
        return (float) $this->paiements->where('statut', 'valide')->sum('montant');
    }

    public function getResteAPayerAttribute(): float
    {
        return max(0, round((float) $this->montant_total - $this->montant_paye, 2));
    }

    public function getPeriodeAttribute(): string
    {
        return $this->date_debut->format('d/m/Y').' → '.$this->date_fin->format('d/m/Y');
    }

    /* ------------------------------------------------------------------
     | Règles métier
     * ------------------------------------------------------------------ */

    /**
     * Nombre de jours facturés (le jour de début et le jour de fin comptent).
     */
    public static function calculerJours(string|Carbon $debut, string|Carbon $fin): int
    {
        return (int) Carbon::parse($debut)->startOfDay()->diffInDays(Carbon::parse($fin)->startOfDay()) + 1;
    }

    /**
     * Réservations actives d'un équipement qui chevauchent la période donnée.
     */
    public function scopeChevauchant(Builder $query, int $equipementId, string $debut, string $fin, ?int $ignorerId = null): Builder
    {
        return $query
            ->where('equipement_id', $equipementId)
            ->whereIn('statut', self::STATUTS_ACTIFS)
            ->whereDate('date_debut', '<=', $fin)
            ->whereDate('date_fin', '>=', $debut)
            ->when($ignorerId, fn (Builder $q) => $q->whereKeyNot($ignorerId));
    }

    public function scopeFiltrer(Builder $query, array $filtres): Builder
    {
        return $query
            ->when($filtres['q'] ?? null, function (Builder $q, $recherche) {
                // Recherche dans les tables liées (équipement et locataire)
                $q->where(function (Builder $sous) use ($recherche) {
                    $sous->whereHas('equipement', fn (Builder $e) => $e->where('nom', 'like', "%{$recherche}%"))
                        ->orWhereHas('locataire', fn (Builder $u) => $u->where('name', 'like', "%{$recherche}%"));
                });
            })
            ->when($filtres['statut'] ?? null, fn (Builder $q, $statut) => $q->where('statut', $statut))
            ->when($filtres['du'] ?? null, fn (Builder $q, $du) => $q->whereDate('date_debut', '>=', $du))
            ->when($filtres['au'] ?? null, fn (Builder $q, $au) => $q->whereDate('date_fin', '<=', $au));
    }
}
