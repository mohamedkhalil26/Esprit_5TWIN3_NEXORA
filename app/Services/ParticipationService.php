<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\Participation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParticipationService
{
    public function save(array $data, ?Participation $participation = null): Participation
    {
        return DB::transaction(function () use ($data, $participation) {
            $event = Evenement::lockForUpdate()->findOrFail($data['evenement_id']);
            $active = ($data['statut'] ?? 'inscrit') !== 'annule';
            $count = $event->participations()->where('statut', '!=', 'annule')->when($participation, fn ($q) => $q->whereKeyNot($participation->id))->count();
            if ($active && ($event->statut !== 'planifie' || $event->date_debut->isPast() || $count >= $event->capacite)) {
                throw ValidationException::withMessages(['evenement_id' => 'Les inscriptions sont fermées ou la capacité est atteinte.']);
            }
            if ($participation) {
                $participation->update($data);

                return $participation;
            }

            return Participation::create($data);
        }, 3);
    }

    public function register(Evenement $event, int $userId, ?string $comment): Participation
    {
        return DB::transaction(function () use ($event, $userId, $comment) {
            Evenement::lockForUpdate()->findOrFail($event->id);
            $existing = Participation::where('evenement_id', $event->id)->where('user_id', $userId)->first();
            if ($existing && $existing->statut !== 'annule') {
                return $existing;
            }

            return $this->save(['evenement_id' => $event->id, 'user_id' => $userId, 'statut' => 'inscrit', 'commentaire' => $comment], $existing);
        }, 3);
    }
}
