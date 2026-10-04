<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categorie extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'nom',
        'description',
        'icone',
    ];

    /**
     * Icônes Bootstrap Icons proposées dans le formulaire.
     */
    public const ICONES = [
        'bi-sun' => 'Soleil',
        'bi-battery-charging' => 'Batterie',
        'bi-wind' => 'Vent',
        'bi-lightning-charge' => 'Éclair',
        'bi-plug' => 'Prise',
        'bi-lamp' => 'Lampe',
        'bi-tools' => 'Outils',
        'bi-box-seam' => 'Kit',
    ];

    /**
     * Une catégorie regroupe plusieurs équipements (1-N).
     */
    public function equipements(): HasMany
    {
        return $this->hasMany(Equipement::class);
    }
}
