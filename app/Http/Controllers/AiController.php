<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\Reclamation;
use App\Models\Reservation;
use App\Services\OpenRouterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AiController extends Controller
{
    public const MODULES = ['equipements' => 'Choix d’équipement', 'locations' => 'Aide à la réservation', 'reclamations' => 'Analyse de réclamation', 'evenements' => 'Programme d’événement', 'blog' => 'Assistant de rédaction'];

    private function authorizeModule(Request $request, string $module): void
    {
        abort_unless(array_key_exists($module, self::MODULES), 404);
        abort_if(in_array($module, ['reclamations', 'evenements', 'blog']) && ! $request->user()->isAdmin(), 403);
    }

    public function apply(Request $request, string $module, int $id)
    {
        $this->authorizeModule($request, $module);
        abort_unless($request->user()->isAdmin() && in_array($module, ['reclamations', 'evenements', 'blog']), 403);
        if ($module === 'reclamations') {
            $record = Reclamation::findOrFail($id);
            $data = $request->validate([
                'categorie' => ['required', Rule::in(array_keys(Reclamation::CATEGORIES))],
                'priorite' => ['required', Rule::in(array_keys(Reclamation::PRIORITES))],
                'reponse' => 'nullable|string|min:5|max:2000',
            ]);
            DB::transaction(function () use ($record, $data, $request) {
                $locked = Reclamation::lockForUpdate()->findOrFail($record->id);
                if ($locked->estFermee() && ! empty($data['reponse'])) {
                    throw ValidationException::withMessages(['reponse' => 'Cette réclamation est fermée. Rouvrez-la avant de répondre.']);
                }
                $locked->update(collect($data)->only('categorie', 'priorite')->all());
                if (! empty($data['reponse'])) {
                    $locked->reponses()->create(['user_id' => $request->user()->id, 'message' => $data['reponse']]);
                }
            });
            $url = route('admin.reclamations.show', $record);
        } elseif ($module === 'evenements') {
            $record = Evenement::findOrFail($id);
            $data = $request->validate(['description' => 'required|string|min:30|max:5000', 'programme' => 'required|string|max:5000']);
            $description = $data['description']."\n\nProgramme proposé et validé :\n".$data['programme'];
            if (mb_strlen($description) > 5000) {
                throw ValidationException::withMessages(['description' => 'La description et le programme réunis doivent rester sous 5 000 caractères.']);
            }
            $record->update(['description' => $description]);
            $url = route('admin.evenements.show', $record);
        } else {
            $record = Article::findOrFail($id);
            $data = $request->validate([
                'titre' => ['required', 'string', 'min:10', 'max:180', Rule::unique('articles', 'titre')->ignore($id)],
                'resume' => 'required|string|min:30|max:300',
            ]);
            $record->update($data);
            $url = route('admin.articles.show', $record);
        }

        $request->session()->forget('ai_preview');

        return redirect($url)->with('success', 'Vos propositions relues et validées ont été enregistrées.');
    }

    public function show(Request $request, string $module)
    {
        $this->authorizeModule($request, $module);
        $preview = $request->session()->get('ai_preview');
        if ($request->old('ai_validation') && $preview && $preview['module'] === $module) {
            return view('ai.assistant', $preview + ['items' => collect()]);
        }
        $items = match ($module) {
            'reclamations' => Reclamation::latest()->limit(100)->pluck('sujet', 'id'),
            'evenements' => Evenement::latest()->limit(100)->pluck('titre', 'id'),
            'blog' => Article::latest()->limit(100)->pluck('titre', 'id'),
            default => collect(),
        };

        return view('ai.assistant', compact('module', 'items'));
    }

    public function generate(Request $request, string $module, OpenRouterService $ai)
    {
        $this->authorizeModule($request, $module);
        $data = $request->validate([
            'besoin' => 'required|string|min:10|max:2000',
            'id' => [in_array($module, ['reclamations', 'evenements', 'blog']) ? 'required' : 'nullable', 'integer'],
            'budget' => [in_array($module, ['equipements', 'locations']) ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100000'],
            'date_debut' => [$module === 'locations' ? 'required' : 'nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'date_fin' => [$module === 'locations' ? 'required' : 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_debut'],
        ]);
        $items = collect();
        $context = ['besoin' => $data['besoin']];
        $editUrl = null;
        if (in_array($module, ['equipements', 'locations'])) {
            $days = $module === 'locations' ? Reservation::calculerJours($data['date_debut'], $data['date_fin']) : 1;
            if ($days > 30) {
                throw ValidationException::withMessages(['date_fin' => 'La location ne peut pas dépasser 30 jours.']);
            }
            $items = Equipement::disponibles()->where('user_id', '!=', $request->user()->id)
                ->where('prix_jour', '<=', $data['budget'] / $days)
                ->when($module === 'locations', fn ($q) => $q->whereNotIn('id', Reservation::query()
                    ->select('equipement_id')->whereIn('statut', Reservation::STATUTS_ACTIFS)
                    ->whereDate('date_debut', '<=', $data['date_fin'])->whereDate('date_fin', '>=', $data['date_debut'])))
                ->orderBy('prix_jour')->limit(40)->get(['id', 'nom', 'marque', 'description', 'puissance_watts', 'capacite_wh', 'prix_jour', 'ville', 'caution']);
            if ($items->isEmpty()) {
                return back()->withInput()->with('error', 'Aucun équipement disponible ne correspond à ces contraintes. Aucun appel IA effectué.');
            }
            $context += ['catalogue' => $items->toArray(), 'budget' => $data['budget'], 'jours' => $days, 'date_debut' => $data['date_debut'] ?? null, 'date_fin' => $data['date_fin'] ?? null];
            $task = 'Choisis jusqu’à trois identifiants pertinents exclusivement dans le catalogue fourni. Format exact : {"equipement_ids":[1,2]}. Aucune autre propriété.';
            $rules = ['equipement_ids' => 'present|array|max:3', 'equipement_ids.*' => ['integer', 'distinct', Rule::in($items->pluck('id')->all())]];
        } else {
            [$record, $task, $rules, $editUrl] = match ($module) {
                'reclamations' => [Reclamation::findOrFail($data['id']),
                    'Analyse la réclamation. Format : {"categorie":"technique|paiement|equipement|comportement|autre","priorite":"basse|moyenne|haute|urgente","resume":"résumé du problème","reponse":"suggestion de réponse administrative"}. Choisis une seule valeur de chaque énumération.',
                    ['categorie' => ['required', Rule::in(array_keys(Reclamation::CATEGORIES))], 'priorite' => ['required', Rule::in(array_keys(Reclamation::PRIORITES))], 'resume' => 'required|string|max:1500', 'reponse' => 'required|string|max:2000'], route('admin.reclamations.edit', $data['id'])],
                'evenements' => [Evenement::findOrFail($data['id']),
                    'Propose une description et un programme à relire sans inventer de lieu, prix, intervenant ou date. Format : {"description":"texte","programme":["étape"]}.',
                    ['description' => 'required|string|min:30|max:5000', 'programme' => 'required|array|min:1|max:10', 'programme.*' => 'required|string|max:500'], route('admin.evenements.edit', $data['id'])],
                'blog' => [Article::findOrFail($data['id']),
                    'Résume et propose des améliorations sans ajouter de faits externes. Format : {"titre":"titre proposé","resume":"résumé","mots_cles":["mot"],"ameliorations":["suggestion"]}.',
                    ['titre' => 'required|string|max:180', 'resume' => 'required|string|max:300', 'mots_cles' => 'required|array|max:10', 'mots_cles.*' => 'string|max:80', 'ameliorations' => 'required|array|max:8', 'ameliorations.*' => 'string|max:800'], route('admin.articles.edit', $data['id'])],
            };
            $context['contenu'] = $record->only(match ($module) {
                'reclamations' => ['sujet', 'description', 'categorie', 'priorite'],
                'evenements' => ['titre', 'description', 'type', 'lieu', 'ville', 'date_debut', 'date_fin', 'prix', 'capacite'],
                'blog' => ['titre', 'resume', 'contenu', 'categorie'],
            });
            if (strlen(json_encode($context)) > 45000) {
                throw ValidationException::withMessages(['id' => 'Ce contenu est trop volumineux pour l’assistant (45 Ko maximum).']);
            }
        }
        try {
            $result = $ai->generate($task, $context, $rules);
        } catch (\RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }
        if (isset($result['equipement_ids'])) {
            $items = $items->whereIn('id', $result['equipement_ids']);
        } else {
            $request->session()->put('ai_preview', compact('module', 'result', 'editUrl', 'data'));
        }

        return view('ai.assistant', compact('module', 'items', 'result', 'editUrl', 'data'));
    }
}
