<?php

namespace App\Http\Requests;

use App\Models\Categorie;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nom' => trim((string) $this->input('nom')),
        ]);
    }

    public function rules(): array
    {
        // En modification, on ignore la catégorie courante pour la règle "unique"
        $categorie = $this->route('categorie');

        return [
            'nom' => [
                'required',
                'string',
                'min:3',
                'max:100',
                Rule::unique('categories', 'nom')->ignore($categorie?->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'icone' => ['required', Rule::in(array_keys(Categorie::ICONES))],
        ];
    }

    public function attributes(): array
    {
        return [
            'nom' => 'nom de la catégorie',
            'description' => 'description',
            'icone' => 'icône',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Veuillez donner un nom à la catégorie.',
            'nom.unique' => 'Cette catégorie existe déjà.',
            'icone.in' => 'Veuillez choisir une icône dans la liste.',
        ];
    }
}
