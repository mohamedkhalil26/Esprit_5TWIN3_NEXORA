<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\Reclamation;
use App\Models\Reservation;
use App\Services\Ai\HubCatalog;
use App\Services\Ai\Scoring\RecommendationScorer;
use App\Services\OpenRouterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AiController extends Controller
{
    public const MODULES = ['equipements' => 'Choix d’équipement', 'locations' => 'Aide à la réservation', 'reclamations' => 'Analyse de réclamation', 'evenements' => 'Programme d’événement', 'blog' => 'Assistant de rédaction'];

    public function index(Request $request, HubCatalog $hubCatalog)
    {
        $modules = $hubCatalog->modules();

        return view('ai.index', compact('modules'));
    }

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
        if ($module === 'reclamations' && request()->filled('id') && is_numeric(request('id'))) {
            $selected = Reclamation::whereKey((int) request('id'))->pluck('sujet', 'id');
            $items = $selected->union($items);
        }

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
            'ville' => 'nullable|string|max:120',
            'puissance_min' => 'nullable|numeric|min:0|max:1000000',
            'capacite_min' => 'nullable|numeric|min:0|max:1000000',
            'etat' => ['nullable', Rule::in(array_keys(Equipement::ETATS))],
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
            $preferences = array_filter($data, static fn ($value, $key): bool => in_array($key, ['ville', 'puissance_min', 'capacite_min', 'etat'], true) && $value !== null && $value !== '', ARRAY_FILTER_USE_BOTH);
            $scorer = app(RecommendationScorer::class);
            $items = $items->map(function (Equipement $item) use ($module, $preferences, $scorer) {
                $score = $module === 'locations'
                    ? $scorer->scoreBooking($item, $preferences)
                    : $scorer->scoreEquipment($item, $preferences);
                $item->setAttribute('ai_score', $score['score']);
                $item->setAttribute('ai_confidence', $score['confidence']);
                $item->setAttribute('ai_score_components', $score['components']);
                $item->setAttribute('ai_score_missing', $score['missing']);

                return $item;
            })->sortByDesc('ai_score')->values();
            $context += ['catalogue' => $items->toArray(), 'budget' => $data['budget'], 'jours' => $days, 'date_debut' => $data['date_debut'] ?? null, 'date_fin' => $data['date_fin'] ?? null];
            $task = 'Choisis jusqu’à trois identifiants pertinents exclusivement dans le catalogue fourni. Format exact : {"equipement_ids":[1,2]}. Aucune autre propriété.';
            $rules = ['equipement_ids' => 'present|array|max:3', 'equipement_ids.*' => ['integer', 'distinct', Rule::in($items->pluck('id')->all())]];
        } else {
            [$record, $task, $rules, $editUrl] = match ($module) {
                'reclamations' => [Reclamation::findOrFail($data['id']),
                    'Analyse brièvement la réclamation. Rends exactement un objet JSON à six champs : categorie (clé exacte parmi les catégories autorisées), priorite (clé exacte parmi les priorités autorisées), resume (une phrase courte), reponse (réponse administrative courte de 60 mots maximum), informations_manquantes (liste de 0 à 6 éléments), explication (raison concise des suggestions). Réponds dans la langue demandée : '.app()->getLocale().'. Aucun fait inventé, aucune résolution automatique.',
                    ['categorie' => ['required', Rule::in(array_keys(Reclamation::CATEGORIES))], 'priorite' => ['required', Rule::in(array_keys(Reclamation::PRIORITES))], 'resume' => 'required|string|max:500', 'reponse' => 'required|string|max:500', 'informations_manquantes' => 'required|array|max:6', 'informations_manquantes.*' => 'required|string|max:200', 'explication' => 'required|string|max:500'], route('admin.reclamations.edit', $data['id'])],
                'evenements' => [Evenement::findOrFail($data['id']),
                    'Rédige une annonce claire destinée aux participants et un programme concis. N’inclus aucune mention de test, de données fictives ou de plateforme. N’invente pas de lieu, prix, intervenant ou date. Rends exactement un objet JSON : description doit être une chaîne de 30 à 1200 caractères; programme doit être un tableau de 1 à 8 chaînes simples, chacune de 3 à 180 caractères. Chaque élément de programme est une chaîne, jamais un objet, tableau, numéro ou valeur nulle. Exemple : {"description":"Atelier pratique...","programme":["Accueil des participants","Démonstration solaire"]}.',
                    ['description' => 'required|string|min:30|max:1200', 'programme' => 'required|array|min:1|max:8', 'programme.*' => 'required|string|min:3|max:180'], route('admin.evenements.edit', $data['id'])],
                'blog' => [Article::findOrFail($data['id']),
                    'Résume et propose des améliorations sans ajouter de faits externes. Format : {"titre":"titre proposé","resume":"résumé","mots_cles":["mot"],"ameliorations":["suggestion"]}.',
                    ['titre' => 'required|string|max:180', 'resume' => 'required|string|max:300', 'mots_cles' => 'required|array|max:10', 'mots_cles.*' => 'string|max:80', 'ameliorations' => 'required|array|max:8', 'ameliorations.*' => 'string|max:800'], route('admin.articles.edit', $data['id'])],
            };
            $context['contenu'] = $record->only(match ($module) {
                'reclamations' => ['sujet', 'description', 'categorie', 'priorite'],
                'evenements' => ['titre', 'description', 'type', 'lieu', 'ville', 'date_debut', 'date_fin', 'prix', 'capacite'],
                'blog' => ['titre', 'resume', 'contenu', 'categorie'],
            });
            if ($module === 'reclamations') {
                $context['contenu']['sujet'] = $this->redactContactDetails($context['contenu']['sujet'] ?? '');
                $context['contenu']['description'] = $this->redactContactDetails($context['contenu']['description'] ?? '');
            }
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

    private function redactContactDetails(string $text): string
    {
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu', '[adresse e-mail masquée]', $text) ?? $text;

        return preg_replace('/(?<!\d)(?:\+?216[\s.-]?)?(?:\d[\s.-]?){8}(?!\d)/u', '[numéro masqué]', $text) ?? $text;
    }
}
