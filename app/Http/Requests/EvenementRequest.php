<?php

namespace App\Http\Requests;

use App\Models\Evenement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EvenementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creation = $this->isMethod('post');

        return [
            'titre' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:30', 'max:5000'],
            'type' => ['required', Rule::in(array_keys(Evenement::TYPES))],
            'lieu' => ['required', 'string', 'max:150'],
            'ville' => ['required', 'string', 'max:100'],
            'date_debut' => array_filter(['required', 'date', $creation ? 'after:now' : null]),
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'capacite' => ['required', 'integer', 'min:1', 'max:500'],
            'prix' => ['required', 'numeric', 'min:0', 'max:1000', 'decimal:0,2'],
            'statut' => ['required', Rule::in(array_keys(Evenement::STATUTS))],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['date_debut', 'date_fin', 'capacite'])) {
                    return;
                }

                $debut = Carbon::parse($this->input('date_debut'));
                $fin = Carbon::parse($this->input('date_fin'));
                if ($debut->diffInDays($fin) > 3) {
                    $validator->errors()->add('date_fin', 'Un événement ne peut pas durer plus de 3 jours.');
                }

                // En modification : la capacité ne peut pas descendre sous le nombre d'inscrits
                $evenement = $this->route('evenement');
                if ($evenement && (int) $this->input('capacite') < $evenement->nb_inscrits) {
                    $validator->errors()->add(
                        'capacite',
                        "La capacité ne peut pas être inférieure au nombre d'inscrits actuels ({$evenement->nb_inscrits})."
                    );
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'titre' => 'titre',
            'description' => 'description',
            'type' => 'type d\'événement',
            'lieu' => 'lieu',
            'ville' => 'ville',
            'date_debut' => 'date de début',
            'date_fin' => 'date de fin',
            'capacite' => 'capacité',
            'prix' => 'prix',
            'statut' => 'statut',
            'image' => 'affiche',
        ];
    }

    public function messages(): array
    {
        return [
            'date_debut.after' => 'L\'événement doit commencer dans le futur.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
            'capacite.min' => 'L\'événement doit accueillir au moins 1 participant.',
        ];
    }
}
