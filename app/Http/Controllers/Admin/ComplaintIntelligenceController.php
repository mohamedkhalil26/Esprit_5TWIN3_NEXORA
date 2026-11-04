<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiFeatureRun;
use App\Models\Reclamation;
use App\Services\OpenRouterService;
use App\Services\Reclamations\ComplaintIntelligenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class ComplaintIntelligenceController extends Controller
{
    public function index(Request $request, ComplaintIntelligenceService $intelligence): View
    {
        $filters = $this->filters($request);

        return $this->page($request, $intelligence, $filters);
    }

    public function analyze(Request $request, ComplaintIntelligenceService $intelligence, OpenRouterService $openRouter): View|RedirectResponse
    {
        $filters = $this->filters($request);
        $categories = $intelligence->recurringCategories();
        if ($categories === []) {
            return $this->page($request, $intelligence, $filters, null, __('complaints.intelligence.insufficient_data'));
        }

        $dashboard = $intelligence->dashboard();
        $signals = [
            'total_reclamations' => $dashboard['total'],
            'categories' => collect($categories)->mapWithKeys(fn ($category) => [$category => $dashboard['by_category'][$category] ?? 0])->all(),
            'statuses_by_category' => Reclamation::query()->selectRaw('categorie, statut, COUNT(*) AS total')
                ->whereIn('categorie', $categories)->groupBy('categorie', 'statut')->get()
                ->groupBy('categorie')->map(fn ($rows) => $rows->mapWithKeys(fn ($row) => [$row->statut => (int) $row->total])->all())->all(),
            'period_months' => $dashboard['monthly'],
        ];

        $run = AiFeatureRun::create([
            'user_id' => $request->user()->id,
            'feature' => 'complaint_intelligence',
            'audience' => 'admin',
            'module' => 'reclamations',
            'provider' => 'openrouter',
            'model' => config('ai.model'),
            'locale' => app()->getLocale(),
            'status' => 'sent',
            'input_hash' => hash('sha256', json_encode($signals, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
        ]);
        $started = microtime(true);

        try {
            $result = $openRouter->generate(
                'Analyse les tendances récurrentes à partir exclusivement des agrégats SQL fournis. La liste recurring_categories doit contenir uniquement des clés de catégorie fournies et répétées. Ne crée aucun chiffre, nom, exemple de réclamation ou fait individuel. Le résumé et les recommandations ne doivent pas inclure de nombres; les compteurs vérifiés sont affichés séparément par Laravel. Réponds dans la langue de la locale précisée ci-dessous. JSON strict : summary (texte court), recurring_categories (liste de clés choisies parmi celles fournies), recommendations (liste de pistes prudentes, 1 à 5). Locale : '.app()->getLocale(),
                ['statistiques_reelles' => $signals],
                [
                    'summary' => 'required|string|min:10|max:800',
                    'recurring_categories' => 'present|array|max:'.count($categories),
                    'recurring_categories.*' => ['required', 'distinct', Rule::in($categories)],
                    'recommendations' => 'required|array|min:1|max:5',
                    'recommendations.*' => 'required|string|min:5|max:240',
                ],
            );
            $run->update(['status' => 'completed', 'duration_ms' => (int) round((microtime(true) - $started) * 1000)]);
            $result['recurring_categories'] = collect($result['recurring_categories'])->unique()->map(fn ($key) => [
                'key' => $key,
                'label' => __(Reclamation::CATEGORIES[$key][0]),
                'count' => (int) ($dashboard['by_category'][$key] ?? 0),
            ])->values()->all();
        } catch (RuntimeException $exception) {
            $run->update([
                'status' => 'provider_error',
                'error_code' => 'provider_unavailable',
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);

            return back()->withInput()->with('error', $exception->getMessage());
        }

        return $this->page($request, $intelligence, $filters, $result);
    }

    private function page(Request $request, ComplaintIntelligenceService $intelligence, array $filters, ?array $analysis = null, ?string $analysisMessage = null): View
    {
        $reclamations = Reclamation::query()->with('auteur')->withCount('reponses')
            ->when(filled($filters['q'] ?? null), fn ($query) => $query->where(function ($q) use ($filters) {
                $term = '%'.trim($filters['q']).'%';
                $q->where('sujet', 'like', $term)->orWhere('description', 'like', $term)
                    ->orWhereHas('auteur', fn ($user) => $user->where('name', 'like', $term));
            }))
            ->when(filled($filters['statut'] ?? null), fn ($query) => $query->where('statut', $filters['statut']))
            ->when(filled($filters['categorie'] ?? null), fn ($query) => $query->where('categorie', $filters['categorie']))
            ->when(filled($filters['priorite'] ?? null), fn ($query) => $query->where('priorite', $filters['priorite']))
            ->when(filled($filters['du'] ?? null), fn ($query) => $query->whereDate('created_at', '>=', $filters['du']))
            ->when(filled($filters['au'] ?? null), fn ($query) => $query->whereDate('created_at', '<=', $filters['au']))
            ->latest()->paginate(15)->withQueryString();
        $statistics = $intelligence->dashboard();

        return view('admin.reclamations.intelligence', [
            'reclamations' => $reclamations,
            'statistics' => $statistics,
            'filters' => $filters,
            'analysis' => $analysis,
            'analysisMessage' => $analysisMessage,
        ]);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:160'],
            'statut' => ['nullable', Rule::in(array_keys(Reclamation::STATUTS))],
            'categorie' => ['nullable', Rule::in(array_keys(Reclamation::CATEGORIES))],
            'priorite' => ['nullable', Rule::in(array_keys(Reclamation::PRIORITES))],
            'du' => ['nullable', 'date_format:Y-m-d'],
            'au' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:du'],
        ]);
    }
}
