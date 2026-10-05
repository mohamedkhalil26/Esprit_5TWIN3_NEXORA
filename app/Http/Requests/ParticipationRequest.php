<?php

namespace App\Http\Requests;

use App\Models\Evenement;
use App\Models\Participation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ParticipationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $participation = $this->route('participation');

        return [
            'evenement_id' => ['required', 'integer', 'exists:evenements,id'],
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                // Unicité du couple (événement, membre)
                Rule::unique('participations', 'user_id')
                    ->where('evenement_id', $this->input('evenement_id'))
                    ->ignore($participation?->id),
            ],
            'statut' => ['required', Rule::in(array_keys(Participation::STATUTS))],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Vérifie que l'événement n'est ni annulé ni complet.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['evenement_id', 'user_id']) || $this->input('statut') === 'annule') {
                    return;
                }

                $evenement = Evenement::find($this->input('evenement_id'));
                $participation = $this->route('participation');

                if ($evenement->statut === 'annule') {
                    $validator->errors()->add('evenement_id', 'Cet événement est annulé : aucune inscription possible.');
                }

                // Une place est déjà comptée si la participation existante est active sur le même événement
                $dejaCompte = $participation
                    && (int) $participation->evenement_id === $evenement->id
                    && $participation->statut !== 'annule';

                if (! $dejaCompte && $evenement->est_complet) {
                    $validator->errors()->add('evenement_id', "Cet événement est complet ({$evenement->capacite} places).");
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'evenement_id' => 'événement',
            'user_id' => 'membre',
            'statut' => 'statut',
            'commentaire' => 'commentaire',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.unique' => 'Ce membre est déjà inscrit à cet événement.',
            'evenement_id.required' => 'Veuillez choisir un événement.',
            'user_id.required' => 'Veuillez choisir un membre.',
        ];
    }
}
