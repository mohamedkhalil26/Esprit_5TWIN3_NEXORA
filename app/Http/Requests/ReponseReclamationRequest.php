<?php

namespace App\Http\Requests;

use App\Models\Reclamation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReponseReclamationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reclamation_id' => [$this->routeIs('admin.*') ? 'required' : 'nullable', 'integer', 'exists:reclamations,id'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
            'nouveau_statut' => ['nullable', Rule::in(array_keys(Reclamation::STATUTS))],
        ];
    }

    /**
     * On ne peut plus répondre à une réclamation fermée.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('reclamation_id') || $this->route('reponse')) {
                    return;
                }

                $reclamation = $this->route('reclamation') ?? Reclamation::find($this->input('reclamation_id'));

                if ($reclamation?->estFermee() && blank($this->input('nouveau_statut'))) {
                    $validator->errors()->add('message', 'Cette réclamation est fermée : il n\'est plus possible d\'y répondre.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'reclamation_id' => 'réclamation',
            'message' => 'réponse',
            'nouveau_statut' => 'nouveau statut',
        ];
    }

    public function messages(): array
    {
        return [
            'reclamation_id.required' => 'Veuillez choisir la réclamation concernée.',
            'message.required' => 'La réponse ne peut pas être vide.',
        ];
    }
}
