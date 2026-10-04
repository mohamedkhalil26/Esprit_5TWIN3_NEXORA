<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Paiement extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'montant',
        'methode',
        'statut',
        'reference',
        'date_paiement',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_paiement' => 'datetime',
        ];
    }

    public const METHODES = [
        'carte' => ['Carte bancaire', 'credit-card'],
        'especes' => ['Espèces', 'cash-coin'],
        'virement' => ['Virement', 'bank'],
        'mobile' => ['Paiement mobile', 'phone'],
    ];

    public const STATUTS = [
        'en_attente' => ['En attente', 'warning'],
        'valide' => ['Validé', 'success'],
        'echoue' => ['Échoué', 'danger'],
        'rembourse' => ['Remboursé', 'secondary'],
    ];

    protected static function booted(): void
    {
        // Référence unique générée automatiquement si elle n'est pas saisie
        static::creating(function (Paiement $paiement) {
            if (blank($paiement->reference)) {
                $paiement->reference = self::genererReference();
            }
        });
    }

    public static function genererReference(): string
    {
        do {
            $reference = 'PAY-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Le paiement appartient à une réservation (N-1).
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function getMethodeLabelAttribute(): string
    {
        return self::METHODES[$this->methode][0] ?? $this->methode;
    }

    public function getMethodeIconeAttribute(): string
    {
        return self::METHODES[$this->methode][1] ?? 'wallet2';
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
