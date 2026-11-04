<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReclamationRequest;
use App\Http\Requests\ReponseReclamationRequest;
use App\Models\AiFeatureRun;
use App\Models\Reclamation;
use App\Services\OpenRouterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Espace "Mes réclamations" du membre connecté.
 */
class ReclamationController extends Controller
{
    public function index(Request $request): View
    {
        $reclamations = Reclamation::withCount('reponses')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(8);

        return view('modules.reclamations.front.index', compact('reclamations'));
    }

    public function create(): View
    {
        return view('modules.reclamations.front.create', [
            'assistantCategories' => Reclamation::CATEGORIES,
            'assistantPriorities' => Reclamation::PRIORITES,
        ]);
    }

    public function improve(Request $request, OpenRouterService $openRouter): JsonResponse
    {
        $data = $request->validate([
            'sujet' => ['nullable', 'string', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'categorie' => ['nullable', Rule::in(array_keys(Reclamation::CATEGORIES))],
            'priorite' => ['nullable', Rule::in(array_keys(Reclamation::PRIORITES))],
        ]);
        $run = AiFeatureRun::create([
            'user_id' => $request->user()->id,
            'feature' => 'complaint_doctor',
            'audience' => 'front',
            'module' => 'reclamations',
            'provider' => 'openrouter',
            'model' => config('ai.model'),
            'locale' => app()->getLocale(),
            'status' => 'sent',
            'input_hash' => hash('sha256', ($data['sujet'] ?? '')."\n".$data['description']),
            'source_type' => 'reclamation_draft',
        ]);
        $started = microtime(true);

        try {
            $result = $openRouter->generate(
                'Aide une personne à rédiger une réclamation claire à partir uniquement du texte fourni. Corrige la langue et reformule sans ajouter de faits. Utilise uniquement les catégories et priorités autorisées fournies. Détecte les informations factuelles manquantes sans les inventer. Le sujet et la description améliorés doivent rester fidèles au texte. Réponds dans la locale suivante : '.app()->getLocale().'. Retourne exactement ces champs JSON : improved_subject, improved_description, category, priority, summary, missing_information (liste de 0 à 6 éléments) et explanation. Aucun envoi, aucune action métier.',
                [
                    'texte_original' => [
                        'sujet' => $this->redactContactDetails($data['sujet'] ?? ''),
                        'description' => $this->redactContactDetails($data['description']),
                    ],
                    'categories_autorisees' => array_keys(Reclamation::CATEGORIES),
                    'priorites_autorisees' => array_keys(Reclamation::PRIORITES),
                    'locale' => app()->getLocale(),
                ],
                [
                    'improved_subject' => 'required|string|min:5|max:150',
                    'improved_description' => 'required|string|min:20|max:3000',
                    'category' => ['required', Rule::in(array_keys(Reclamation::CATEGORIES))],
                    'priority' => ['required', Rule::in(array_keys(Reclamation::PRIORITES))],
                    'summary' => 'required|string|min:5|max:500',
                    'missing_information' => 'required|array|max:6',
                    'missing_information.*' => 'required|string|min:2|max:160',
                    'explanation' => 'required|string|min:5|max:800',
                ],
            );
            $run->update(['status' => 'completed', 'duration_ms' => (int) round((microtime(true) - $started) * 1000)]);

            return response()->json(['suggestions' => $result]);
        } catch (RuntimeException $exception) {
            $run->update([
                'status' => 'provider_error',
                'error_code' => 'provider_unavailable',
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);

            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    private function redactContactDetails(string $text): string
    {
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu', '[contact masqué]', $text) ?? $text;

        return preg_replace('/(?<!\d)(?:\+?216[\s.-]?)?(?:\d[\s.-]?){8}(?!\d)/u', '[contact masqué]', $text) ?? $text;
    }

    public function store(ReclamationRequest $request): RedirectResponse
    {
        $donnees = $request->safe()->except('piece_jointe');

        if ($request->hasFile('piece_jointe')) {
            $donnees['piece_jointe'] = $request->file('piece_jointe')->store('reclamations', 'local');
        }

        $reclamation = Reclamation::create([
            ...$donnees,
            'user_id' => $request->user()->id,
            'statut' => 'ouverte',
        ]);

        return redirect()
            ->route('front.reclamations.show', $reclamation)
            ->with('success', 'Votre réclamation a bien été envoyée. Notre équipe vous répondra rapidement.');
    }

    public function show(Request $request, Reclamation $reclamation): View
    {
        abort_unless((int) $reclamation->user_id === $request->user()->id, 403);

        $reclamation->load('reponses.auteur');

        return view('modules.reclamations.front.show', compact('reclamation'));
    }

    public function repondre(ReponseReclamationRequest $request, Reclamation $reclamation): RedirectResponse
    {
        abort_unless((int) $reclamation->user_id === $request->user()->id, 403);

        $reclamation->reponses()->create([
            'user_id' => $request->user()->id,
            'message' => $request->validated('message'),
        ]);

        // Le membre relance : la réclamation résolue est rouverte
        if ($reclamation->statut === 'resolue') {
            $reclamation->update(['statut' => 'en_cours']);
        }

        return back()->with('success', 'Votre message a été ajouté.');
    }
}
