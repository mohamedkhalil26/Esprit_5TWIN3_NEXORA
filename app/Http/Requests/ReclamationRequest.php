<?php

namespace App\Http\Requests;

use App\Models\Reclamation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation d'une réclamation (back office et espace membre).
 */
class ReclamationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function estAdmin(): bool
    {
        return $this->routeIs('admin.*');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sujet' => trim((string) $this->input('sujet')),
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'sujet' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'categorie' => ['required', Rule::in(array_keys(Reclamation::CATEGORIES))],
            'priorite' => ['required', Rule::in(array_keys(Reclamation::PRIORITES))],
            'piece_jointe' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];

        if ($this->estAdmin()) {
            $rules['user_id'] = ['required', 'integer', 'exists:users,id'];
            $rules['statut'] = ['required', Rule::in(array_keys(Reclamation::STATUTS))];
        }

        return $rules;
    }

    /**
     * Un membre ne peut pas avoir plus de 3 réclamations ouvertes en même temps.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->estAdmin() || ! $this->isMethod('post')) {
                    return;
                }

                $ouvertes = Reclamation::where('user_id', $this->user()->id)
                    ->whereIn('statut', ['ouverte', 'en_cours'])
                    ->count();

                if ($ouvertes >= 3) {
                    $validator->errors()->add('sujet', 'Vous avez déjà 3 réclamations en cours de traitement. Merci de patienter avant d\'en déposer une nouvelle.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'membre',
            'sujet' => 'sujet',
            'description' => 'description',
            'categorie' => 'catégorie',
            'priorite' => 'priorité',
            'statut' => 'statut',
            'piece_jointe' => 'pièce jointe',
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'Décrivez votre problème en au moins :min caractères pour que nous puissions vous aider.',
            'piece_jointe.mimes' => 'La pièce jointe doit être une image (JPG, PNG) ou un PDF.',
            'piece_jointe.max' => 'La pièce jointe ne doit pas dépasser 4 Mo.',
            'categorie.required' => 'Veuillez choisir le type de réclamation.',
        ];
    }
}
