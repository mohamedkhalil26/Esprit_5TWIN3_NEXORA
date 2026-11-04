<?php

namespace App\Http\Requests;

use App\Models\Equipement;
use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation d'une réservation.
 * Utilisée par le back office (admin choisit le locataire et le statut)
 * et par le front office (le locataire connecté réserve pour lui-même).
 */
class ReservationRequest extends FormRequest
{
    public const DUREE_MAX_JOURS = 30;

    public function authorize(): bool
    {
        return true;
    }

    private function estAdmin(): bool
    {
        return $this->routeIs('admin.*');
    }

    public function rules(): array
    {
        $creation = $this->isMethod('post');

        $rules = [
            'equipement_id' => ['required', 'integer', 'exists:equipements,id'],
            'date_debut' => array_filter(['required', 'date', $creation ? 'after_or_equal:today' : null]),
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->estAdmin()) {
            $rules['user_id'] = ['required', 'integer', 'exists:users,id'];
            $rules['statut'] = ['required', Rule::in(array_keys(Reservation::STATUTS))];
        }

        return $rules;
    }

    /**
     * Règles métier : durée maximale, disponibilité et chevauchement de dates.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['equipement_id', 'date_debut', 'date_fin'])) {
                    return;
                }

                $jours = Reservation::calculerJours($this->input('date_debut'), $this->input('date_fin'));
                if ($jours > self::DUREE_MAX_JOURS) {
                    $validator->errors()->add('date_fin', 'La durée de location ne peut pas dépasser '.self::DUREE_MAX_JOURS.' jours.');
                }

                $equipement = Equipement::find($this->input('equipement_id'));
                if ($this->isMethod('post') && ! $equipement->disponible) {
                    $validator->errors()->add('equipement_id', 'Cet équipement n\'est pas disponible à la location actuellement.');
                }

                if (! $this->estAdmin() && $equipement->user_id == $this->user()->id) {
                    $validator->errors()->add('equipement_id', 'Vous ne pouvez pas réserver votre propre équipement.');
                }

                $statut = $this->estAdmin() ? $this->input('statut', 'en_attente') : 'en_attente';
                $chevauchement = Reservation::chevauchant(
                    $equipement->id,
                    $this->input('date_debut'),
                    $this->input('date_fin'),
                    $this->route('reservation')?->id
                )->exists();

                if ($chevauchement && in_array($statut, Reservation::STATUTS_ACTIFS, true)) {
                    $validator->errors()->add('date_debut', 'Cet équipement est déjà réservé sur tout ou partie de cette période.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'equipement_id' => 'équipement',
            'user_id' => 'locataire',
            'date_debut' => 'date de début',
            'date_fin' => 'date de fin',
            'statut' => 'statut',
            'message' => 'message',
        ];
    }

    public function messages(): array
    {
        return [
            'date_debut.after_or_equal' => 'La date de début ne peut pas être dans le passé.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'equipement_id.required' => 'Veuillez choisir un équipement.',
            'user_id.required' => 'Veuillez choisir le locataire.',
        ];
    }
}
