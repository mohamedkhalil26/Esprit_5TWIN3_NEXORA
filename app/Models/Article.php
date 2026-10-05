<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'titre',
        'slug',
        'resume',
        'contenu',
        'categorie',
        'image',
        'statut',
        'publie_le',
        'vues',
    ];

    protected function casts(): array
    {
        return [
            'publie_le' => 'datetime',
            'vues' => 'integer',
        ];
    }

    public const CATEGORIES = [
        'solaire' => ['Énergie solaire', 'sun'],
        'eolien' => ['Éolien', 'wind'],
        'stockage' => ['Stockage & batteries', 'battery-charging'],
        'economies' => ['Économies d\'énergie', 'piggy-bank'],
        'actualites' => ['Actualités', 'newspaper'],
    ];

    public const STATUTS = [
        'brouillon' => ['Brouillon', 'secondary'],
        'publie' => ['Publié', 'success'],
    ];

    protected static function booted(): void
    {
        static::saving(function (Article $article) {
            // Slug unique généré à partir du titre
            if ($article->isDirty('titre') || blank($article->slug)) {
                $article->slug = static::slugUnique($article->titre, $article->id);
            }

            // Date de publication renseignée automatiquement à la première publication
            if ($article->statut === 'publie' && blank($article->publie_le)) {
                $article->publie_le = now();
            }
        });
    }

    public static function slugUnique(string $titre, ?int $ignorerId = null): string
    {
        $base = Str::slug($titre);
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignorerId, fn ($q) => $q->whereKeyNot($ignorerId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /* ------------------------------------------------------------------
     | Relations
     * ------------------------------------------------------------------ */

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Un article reçoit plusieurs commentaires (1-N).
     */
    public function commentaires(): HasMany
    {
        return $this->hasMany(Commentaire::class)->latest();
    }

    public function commentairesApprouves(): HasMany
    {
        return $this->commentaires()->where('approuve', true);
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
        return self::CATEGORIES[$this->categorie][1] ?? 'journal';
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
     * Temps de lecture estimé (200 mots par minute).
     */
    public function getTempsLectureAttribute(): int
    {
        return max(1, (int) ceil(count(preg_split('/\s+/u', trim(strip_tags($this->contenu)))) / 200));
    }

    /* ------------------------------------------------------------------
     | Scopes
     * ------------------------------------------------------------------ */

    public function scopePublies(Builder $query): Builder
    {
        return $query->where('statut', 'publie')->where('publie_le', '<=', now());
    }

    public function scopeFiltrer(Builder $query, array $filtres): Builder
    {
        return $query
            ->when($filtres['q'] ?? null, fn (Builder $q, $recherche) => $q->where(function (Builder $sous) use ($recherche) {
                $sous->where('titre', 'like', "%{$recherche}%")
                    ->orWhere('resume', 'like', "%{$recherche}%");
            }))
            ->when($filtres['categorie'] ?? null, fn (Builder $q, $categorie) => $q->where('categorie', $categorie))
            ->when($filtres['statut'] ?? null, fn (Builder $q, $statut) => $q->where('statut', $statut));
    }
}
