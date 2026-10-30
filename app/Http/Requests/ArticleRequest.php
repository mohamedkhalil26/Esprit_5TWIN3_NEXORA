<?php

namespace App\Http\Requests;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ArticleRequest extends FormRequest
{
    public const MOTS_MIN_PUBLICATION = 80;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'titre' => trim((string) $this->input('titre')),
        ]);
    }

    public function rules(): array
    {
        $article = $this->route('article');

        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'titre' => ['required', 'string', 'min:10', 'max:180', Rule::unique('articles', 'titre')->ignore($article?->id)],
            'resume' => ['required', 'string', 'min:30', 'max:300'],
            'contenu' => ['required', 'string', 'min:100'],
            'categorie' => ['required', Rule::in(array_keys(Article::CATEGORIES))],
            'statut' => ['required', Rule::in(array_keys(Article::STATUTS))],
            'publie_le' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Un article publié doit être suffisamment complet.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('statut') !== 'publie' || $validator->errors()->has('contenu')) {
                    return;
                }

                $mots = count(preg_split('/\s+/u', trim(strip_tags((string) $this->input('contenu'))), -1, PREG_SPLIT_NO_EMPTY));
                if ($mots < self::MOTS_MIN_PUBLICATION) {
                    $validator->errors()->add(
                        'contenu',
                        'Un article publié doit contenir au moins '.self::MOTS_MIN_PUBLICATION." mots (actuellement {$mots}). Enregistrez-le comme brouillon en attendant."
                    );
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'auteur',
            'titre' => 'titre',
            'resume' => 'résumé',
            'contenu' => 'contenu',
            'categorie' => 'catégorie',
            'statut' => 'statut',
            'publie_le' => 'date de publication',
            'image' => 'image de couverture',
        ];
    }

    public function messages(): array
    {
        return [
            'titre.unique' => 'Un article porte déjà ce titre.',
            'resume.min' => 'Le résumé doit faire au moins :min caractères : il apparaît dans la liste des articles.',
        ];
    }
}
