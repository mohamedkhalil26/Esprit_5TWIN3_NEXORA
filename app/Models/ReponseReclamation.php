<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReponseReclamation extends Model
{
    use HasFactory;

    protected $table = 'reponses_reclamations';

    protected $fillable = [
        'reclamation_id',
        'user_id',
        'message',
    ];

    /**
     * La réponse appartient à une réclamation (N-1).
     */
    public function reclamation(): BelongsTo
    {
        return $this->belongsTo(Reclamation::class);
    }

    /**
     * Auteur de la réponse : administrateur ou membre.
     */
    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
