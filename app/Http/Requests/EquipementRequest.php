<?php

namespace App\Http\Requests;

use App\Models\Equipement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EquipementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'disponible' => $this->boolean('disponible'),
            'nom' => trim((string) $this->input('nom')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'min:3', 'max:150'],
            'categorie_id' => ['required', 'integer', 'exists:categories,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'marque' => ['nullable', 'string', 'max:80'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'puissance_watts' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'capacite_wh' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'prix_jour' => ['required', 'numeric', 'min:1', 'max:10000', 'decimal:0,2'],
            'caution' => ['nullable', 'numeric', 'min:0', 'max:100000', 'decimal:0,2'],
            'etat' => ['required', Rule::in(array_keys(Equipement::ETATS))],
            'ville' => ['required', 'string', 'min:2', 'max:100'],
            'disponible' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Règles métier supplémentaires, vérifiées après les règles de base.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (blank($this->input('puissance_watts')) && blank($this->input('capacite_wh'))) {
                    $validator->errors()->add(
                        'puissance_watts',
                        'Indiquez au moins la puissance (W) ou la capacité (Wh) de l\'équipement.'
                    );
                }

                if (filled($this->input('caution')) && is_numeric($this->input('caution'))
                    && is_numeric($this->input('prix_jour'))
                    && (float) $this->input('caution') > 0
                    && (float) $this->input('caution') < (float) $this->input('prix_jour')) {
                    $validator->errors()->add('caution', 'La caution ne peut pas être inférieure au prix d\'une journée de location.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'nom' => 'nom de l\'équipement',
            'categorie_id' => 'catégorie',
            'user_id' => 'propriétaire',
            'marque' => 'marque',
            'description' => 'description',
            'puissance_watts' => 'puissance',
            'capacite_wh' => 'capacité',
            'prix_jour' => 'prix par jour',
            'caution' => 'caution',
            'etat' => 'état',
            'ville' => 'ville',
            'image' => 'photo',
        ];
    }

    public function messages(): array
    {
        return [
            'categorie_id.required' => 'Veuillez sélectionner une catégorie.',
            'categorie_id.exists' => 'La catégorie choisie n\'existe pas.',
            'user_id.required' => 'Veuillez sélectionner le propriétaire.',
            'description.min' => 'La description doit faire au moins :min caractères pour bien présenter l\'équipement.',
            'prix_jour.decimal' => 'Le prix doit avoir au maximum 2 décimales.',
            'image.max' => 'La photo ne doit pas dépasser 2 Mo.',
        ];
    }
}
