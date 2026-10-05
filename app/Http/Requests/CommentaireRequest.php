<?php

namespace App\Http\Requests;

use App\Models\Commentaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

/**
 * Validation d'un commentaire (back office et formulaire public du blog).
 */
class CommentaireRequest extends FormRequest
{
    /**
     * Mots interdits (modération automatique simple).
     */
    private const MOTS_INTERDITS = ['arnaque', 'idiot', 'nul', 'spam', 'casino'];

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
            'contenu' => trim((string) $this->input('contenu')),
            'approuve' => $this->boolean('approuve'),
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'contenu' => ['required', 'string', 'min:3', 'max:1000'],
        ];

        if ($this->estAdmin()) {
            $rules['article_id'] = ['required', 'integer', 'exists:articles,id'];
            $rules['user_id'] = ['required', 'integer', 'exists:users,id'];
            $rules['approuve'] = ['boolean'];
        }

        return $rules;
    }

    /**
     * Anti-spam : liens, mots interdits et délai entre deux commentaires.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('contenu')) {
                    return;
                }

                $contenu = Str::lower($this->input('contenu'));

                if (substr_count($contenu, 'http') > 1) {
                    $validator->errors()->add('contenu', 'Un commentaire ne peut pas contenir plus d\'un lien.');
                }

                foreach (self::MOTS_INTERDITS as $mot) {
                    if (preg_match('/\b'.preg_quote($mot, '/').'\b/u', $contenu)) {
                        $validator->errors()->add('contenu', 'Votre commentaire contient des termes inappropriés.');
                        break;
                    }
                }

                if (! $this->estAdmin()) {
                    $dernier = Commentaire::where('user_id', $this->user()->id)->latest()->first();
                    if ($dernier && $dernier->created_at->gt(now()->subSeconds(30))) {
                        $validator->errors()->add('contenu', 'Merci de patienter 30 secondes entre deux commentaires.');
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'article_id' => 'article',
            'user_id' => 'auteur',
            'contenu' => 'commentaire',
            'approuve' => 'approbation',
        ];
    }

    public function messages(): array
    {
        return [
            'contenu.required' => 'Le commentaire ne peut pas être vide.',
            'article_id.required' => 'Veuillez choisir l\'article commenté.',
        ];
    }
}
