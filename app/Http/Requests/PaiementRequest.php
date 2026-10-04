<?php

namespace App\Http\Requests;

use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference' => $this->filled('reference') ? strtoupper(trim($this->input('reference'))) : null,
        ]);
    }

    public function rules(): array
    {
        $paiement = $this->route('paiement');

        return [
            'reservation_id' => ['required', 'integer', 'exists:reservations,id'],
            'montant' => ['required', 'numeric', 'min:0.01', 'max:100000', 'decimal:0,2'],
            'methode' => ['required', Rule::in(array_keys(Paiement::METHODES))],
            'statut' => ['required', Rule::in(array_keys(Paiement::STATUTS))],
            'reference' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[A-Z0-9\-]+$/',
                Rule::unique('paiements', 'reference')->ignore($paiement?->id),
            ],
            'date_paiement' => ['required', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * Un paiement validé ne peut pas dépasser le reste à payer de la réservation.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['reservation_id', 'montant']) || $this->input('statut') !== 'valide') {
                    return;
                }

                $reservation = Reservation::with('paiements')->find($this->input('reservation_id'));
                $dejaPaye = $reservation->paiements
                    ->where('statut', 'valide')
                    ->where('id', '!=', $this->route('paiement')?->id)
                    ->sum('montant');
                $reste = round((float) $reservation->montant_total - $dejaPaye, 2);

                if ((float) $this->input('montant') > $reste) {
                    $validator->errors()->add(
                        'montant',
                        'Le montant dépasse le reste à payer de la réservation ('.number_format($reste, 2, ',', ' ').' DT).'
                    );
                }

                if ($reservation->statut === 'annulee') {
                    $validator->errors()->add('reservation_id', 'Impossible d\'encaisser un paiement sur une réservation annulée.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'reservation_id' => 'réservation',
            'montant' => 'montant',
            'methode' => 'méthode de paiement',
            'statut' => 'statut',
            'reference' => 'référence',
            'date_paiement' => 'date du paiement',
        ];
    }

    public function messages(): array
    {
        return [
            'reservation_id.required' => 'Veuillez choisir la réservation concernée.',
            'reference.regex' => 'La référence ne doit contenir que des lettres majuscules, chiffres et tirets.',
            'reference.unique' => 'Cette référence de paiement existe déjà.',
            'date_paiement.before_or_equal' => 'La date du paiement ne peut pas être dans le futur.',
        ];
    }
}
