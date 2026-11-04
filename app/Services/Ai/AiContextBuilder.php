<?php

namespace App\Services\Ai;

use App\Models\Article;
use App\Models\Categorie;
use App\Models\Commentaire;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\Paiement;
use App\Models\Participation;
use App\Models\Reclamation;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Ai\Scoring\RecommendationScorer;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class AiContextBuilder
{
    public const MODULES = ['equipements', 'locations', 'reclamations', 'evenements', 'blog'];

    public function __construct(private readonly RecommendationScorer $scorer) {}

    public function build(string $module, string $audience, User $user, string $question, array $input, array $history): array
    {
        abort_unless(in_array($module, self::MODULES, true), 404);
        abort_unless(in_array($audience, ['front', 'admin'], true), 404);
        abort_if($audience === 'admin' && ! $user->isAdmin(), 403);

        if ($audience === 'admin') {
            return $this->adminContext($module, $question, $history);
        }

        return $this->frontContext($module, $user, $question, $input, $history);
    }

    public function references(string $module, array $ids, User $user, array $context = []): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false ? (int) $id : null,
            $ids,
        ), static fn ($id) => $id !== null)));
        if ($ids === []) {
            return collect();
        }

        return match ($module) {
            'equipements', 'locations' => Equipement::disponibles()->where('user_id', '!=', $user->id)->whereKey($ids)
                ->when(isset($context['budget']), function ($query) use ($module, $context) {
                    $days = $module === 'locations' && ! empty($context['date_debut']) && ! empty($context['date_fin'])
                        ? Reservation::calculerJours($context['date_debut'], $context['date_fin']) : 1;

                    return $query->where('prix_jour', '<=', (float) $context['budget'] / max(1, $days));
                })
                ->when($module === 'locations' && ! empty($context['date_debut']) && ! empty($context['date_fin']), fn ($query) => $query->whereNotIn('id', Reservation::query()
                    ->select('equipement_id')->whereIn('statut', Reservation::STATUTS_ACTIFS)
                    ->whereDate('date_debut', '<=', $context['date_fin'])->whereDate('date_fin', '>=', $context['date_debut'])))
                ->get(),
            'evenements' => Evenement::aVenir()->avecNbInscrits()->whereKey($ids)->get(),
            'blog' => Article::publies()->whereKey($ids)->get(),
            default => collect(),
        };
    }

    private function frontContext(string $module, User $user, string $question, array $input, array $history): array
    {
        $records = collect();
        $context = ['demande' => $question, 'historique_recent' => $history];

        if (in_array($module, ['equipements', 'locations'], true)) {
            $days = 1;
            if ($module === 'locations' && ! empty($input['date_debut']) && ! empty($input['date_fin'])) {
                $days = Reservation::calculerJours($input['date_debut'], $input['date_fin']);
            }
            $records = Equipement::disponibles()->where('user_id', '!=', $user->id)
                ->when(isset($input['budget']), fn ($query) => $query->where('prix_jour', '<=', (float) $input['budget'] / $days))
                ->when($module === 'locations' && ! empty($input['date_debut']) && ! empty($input['date_fin']), fn ($query) => $query->whereNotIn('id', Reservation::query()
                    ->select('equipement_id')->whereIn('statut', Reservation::STATUTS_ACTIFS)
                    ->whereDate('date_debut', '<=', $input['date_fin'])->whereDate('date_fin', '>=', $input['date_debut'])))
                ->orderBy('prix_jour')->limit(25)
                ->get(['id', 'nom', 'marque', 'description', 'puissance_watts', 'capacite_wh', 'prix_jour', 'ville', 'caution']);
            $preferences = array_filter($input, static fn ($value, $key): bool => in_array($key, ['ville', 'puissance_min', 'capacite_min', 'etat'], true) && $value !== null && $value !== '', ARRAY_FILTER_USE_BOTH);
            $records = $records->map(function (Equipement $item) use ($module, $preferences) {
                $score = $module === 'locations' ? $this->scorer->scoreBooking($item, $preferences) : $this->scorer->scoreEquipment($item, $preferences);
                $item->setAttribute('ai_score', $score['score']);
                $item->setAttribute('ai_confidence', $score['confidence']);
                $item->setAttribute('ai_score_components', $score['components']);
                $item->setAttribute('ai_score_missing', $score['missing']);

                return $item;
            })->sortByDesc('ai_score')->values();
            $context += [
                'budget_total_dt' => $input['budget'] ?? null,
                'dates' => ['debut' => $input['date_debut'] ?? null, 'fin' => $input['date_fin'] ?? null],
                'jours_factures' => $days,
                'candidats_reellement_disponibles' => $records->map(fn (Equipement $item) => $item->only(['id', 'nom', 'marque', 'description', 'puissance_watts', 'capacite_wh', 'prix_jour', 'ville', 'caution', 'ai_score', 'ai_confidence', 'ai_score_components', 'ai_score_missing']))->all(),
            ];
            if ($module === 'locations') {
                $context['mes_reservations_recentes'] = Reservation::where('user_id', $user->id)->with('equipement:id,nom')->latest()->limit(5)->get()
                    ->map(fn (Reservation $reservation) => [
                        'reference' => $reservation->id,
                        'equipement' => $reservation->equipement?->nom,
                        'date_debut' => $reservation->date_debut?->toDateString(),
                        'date_fin' => $reservation->date_fin?->toDateString(),
                        'statut' => $reservation->statut,
                        'montant_total_dt' => (float) $reservation->montant_total,
                    ])->all();
            }
        } elseif ($module === 'reclamations') {
            $records = Reclamation::where('user_id', $user->id)->when(! empty($input['claim_id']), fn ($query) => $query->whereKey($input['claim_id']))->latest()->limit(5)->get(['id', 'sujet', 'categorie', 'priorite', 'statut', 'created_at']);
            $context['mes_reclamations'] = $records->map(fn (Reclamation $record) => [
                'reference' => $record->id,
                'sujet' => $record->sujet,
                'categorie' => $record->categorie,
                'priorite' => $record->priorite,
                'statut' => $record->statut,
                'date' => $record->created_at?->toDateString(),
            ])->all();
        } elseif ($module === 'evenements') {
            $records = Evenement::aVenir()->when(! empty($input['event_id']), fn ($query) => $query->whereKey($input['event_id']))->avecNbInscrits()->orderBy('date_debut')->limit(25)->get();
            $preferences = array_filter($input, static fn ($value, $key): bool => in_array($key, ['type', 'ville', 'date_debut'], true) && $value !== null && $value !== '', ARRAY_FILTER_USE_BOTH);
            $records = $records->map(function (Evenement $event) use ($preferences) {
                $score = $this->scorer->scoreEvent($event, $preferences);
                $event->setAttribute('ai_score', $score['score']);
                $event->setAttribute('ai_confidence', $score['confidence']);

                return $event;
            })->sortByDesc('ai_score')->values();
            $context['evenements_a_venir'] = $records->map(fn (Evenement $event) => [
                'id' => $event->id,
                'titre' => $event->titre,
                'description' => mb_substr(strip_tags($event->description), 0, 500),
                'type' => $event->type,
                'ville' => $event->ville,
                'date_debut' => $event->date_debut?->toIso8601String(),
                'prix' => $event->prix,
                'capacite' => $event->capacite,
                'places_inscrites' => $event->participations_actives_count,
                'places_restantes' => max(0, $event->capacite - $event->participations_actives_count),
                'score' => $event->ai_score,
                'confiance' => $event->ai_confidence,
            ])->all();
        } else {
            $records = Article::publies()->when(! empty($input['article_id']), fn ($query) => $query->whereKey($input['article_id']))->latest('publie_le')->limit(25)->get(['id', 'titre', 'slug', 'resume', 'contenu', 'categorie', 'publie_le']);
            $context['articles_publies'] = $records->map(fn (Article $article) => [
                'id' => $article->id,
                'titre' => $article->titre,
                'slug' => $article->slug,
                'resume' => $article->resume,
                'extrait' => mb_substr(strip_tags($article->contenu), 0, 500),
                'categorie' => $article->categorie,
                'publie_le' => $article->publie_le?->toDateString(),
            ])->all();
        }

        [$task, $rules] = $this->frontTask($module, $records);

        return compact('task', 'context', 'rules', 'records');
    }

    private function adminContext(string $module, string $question, array $history): array
    {
        $context = ['question_administrateur' => $question, 'historique_recent' => $history];

        if ($module === 'equipements') {
            $demand = Reservation::query()->selectRaw('equipement_id, COUNT(*) AS reservations_count')
                ->groupBy('equipement_id')->orderByDesc('reservations_count')->limit(10)->get();
            $popularIds = $demand->pluck('equipement_id')->all();
            $context['indicateurs_catalogue'] = [
                'equipements_total' => Equipement::count(),
                'equipements_disponibles' => Equipement::disponibles()->count(),
                'categories_total' => Categorie::count(),
                'exemples_catalogue' => Equipement::orderByDesc('created_at')->limit(20)->get(['id', 'nom', 'description', 'categorie_id', 'puissance_watts', 'capacite_wh', 'prix_jour', 'disponible'])
                    ->map(fn (Equipement $item) => ['id' => $item->id, 'nom' => $item->nom, 'description' => mb_substr(strip_tags($this->redactContactDetails($item->description ?? '')), 0, 500), 'categorie_id' => $item->categorie_id, 'puissance_watts' => $item->puissance_watts, 'capacite_wh' => $item->capacite_wh, 'prix_jour' => (float) $item->prix_jour, 'disponible' => $item->disponible])->all(),
                'demandes_par_equipement' => Equipement::whereIn('id', $popularIds)->get(['id', 'nom', 'categorie_id'])
                    ->keyBy('id')->map(fn ($item) => ['nom' => $item->nom, 'categorie_id' => $item->categorie_id, 'reservations' => (int) $demand->firstWhere('equipement_id', $item->id)->reservations_count])->values()->all(),
                'equipements_sans_reservation' => Equipement::whereNotIn('id', Reservation::query()->select('equipement_id')->distinct())->count(),
            ];
            $task = 'Analyse les indicateurs agrégés du catalogue : explique la demande et repère les catégories ou équipements à examiner. Ne crée aucun chiffre et ne propose que des décisions à valider. Si on te demande d’améliorer une description, ajoute description_proposee fondée uniquement sur les caractéristiques fournies, sinon null. Réponds en JSON avec summary, insights, recommendations et description_proposee.';
        } elseif ($module === 'locations') {
            $context['indicateurs_reservations'] = [
                'total' => Reservation::count(),
                'par_statut' => Reservation::query()->selectRaw('statut, COUNT(*) AS total')->groupBy('statut')->pluck('total', 'statut'),
                'annulations' => Reservation::where('statut', 'annulee')->count(),
                'revenus_valides_dt' => (float) Paiement::where('statut', 'valide')->sum('montant'),
                'equipements_les_plus_reserves' => Reservation::query()->selectRaw('equipement_id, COUNT(*) AS reservations_count')->groupBy('equipement_id')->orderByDesc('reservations_count')->limit(10)->with('equipement:id,nom')->get()->map(fn (Reservation $reservation) => [
                    'equipement' => $reservation->equipement?->nom ?? 'Équipement indisponible',
                    'reservations' => (int) $reservation->reservations_count,
                ])->all(),
                'periode' => 'Données agrégées issues des réservations actuellement enregistrées.',
            ];
            $task = 'Analyse les indicateurs réels des réservations, annulations et revenus. Distingue constat et recommandation, ne décide jamais de confirmer ou refuser une réservation. Réponds en JSON avec summary, insights et recommendations.';
        } elseif ($module === 'reclamations') {
            $context['indicateurs_reclamations'] = [
                'total' => Reclamation::count(),
                'par_statut' => Reclamation::query()->selectRaw('statut, COUNT(*) AS total')->groupBy('statut')->pluck('total', 'statut'),
                'par_categorie' => Reclamation::query()->selectRaw('categorie, COUNT(*) AS total')->groupBy('categorie')->pluck('total', 'categorie'),
                'par_priorite' => Reclamation::query()->selectRaw('priorite, COUNT(*) AS total')->groupBy('priorite')->pluck('total', 'priorite'),
            ];
            $task = 'Analyse uniquement ces statistiques agrégées de réclamations. Ne révèle aucune identité, ne décide jamais d’une résolution. Réponds en JSON avec summary, insights et recommendations.';
        } elseif ($module === 'evenements') {
            $context['indicateurs_evenements'] = [
                'total' => Evenement::count(),
                'a_venir' => Evenement::aVenir()->count(),
                'places_inscrites' => Participation::where('statut', 'inscrit')->count(),
                'prochains_evenements' => Evenement::aVenir()->avecNbInscrits()->orderBy('date_debut')->limit(12)->get(['id', 'titre', 'capacite', 'date_debut'])
                    ->map(fn (Evenement $event) => ['titre' => $event->titre, 'capacite' => $event->capacite, 'inscrits' => $event->participations_actives_count, 'date_debut' => $event->date_debut?->toIso8601String()])->all(),
            ];
            $task = 'Analyse les statistiques réelles des événements et de leur capacité. Ne révèle aucune identité et ne modifie aucun événement. Si la question demande un nouveau brouillon, renseigne draft_title, draft_description et draft_program sans inventer de date, lieu, prix ou capacité; sinon mets-les à null. Réponds en JSON avec summary, insights, recommendations, draft_title, draft_description et draft_program.';
        } else {
            $context['indicateurs_blog'] = [
                'articles_total' => Article::count(),
                'par_statut' => Article::query()->selectRaw('statut, COUNT(*) AS total')->groupBy('statut')->pluck('total', 'statut'),
                'par_categorie' => Article::query()->selectRaw('categorie, COUNT(*) AS total')->groupBy('categorie')->pluck('total', 'categorie'),
                'commentaires_a_moderer' => Commentaire::where('approuve', false)->count(),
            ];
            $task = 'Analyse uniquement ces statistiques réelles de contenu. Propose des pistes éditoriales à valider; ne publie et ne modifie aucun article. Si la question demande un brouillon d’article, renseigne draft_title, draft_resume, draft_outline et draft_content, en distinguant les faits fournis des idées; sinon mets-les à null. Réponds en JSON avec summary, insights, recommendations et ces champs draft.';
        }

        $rules = [
            'summary' => 'required|string|min:10|max:1200',
            'insights' => 'required|array|min:1|max:6',
            'insights.*' => 'required|string|min:3|max:300',
            'recommendations' => 'required|array|min:1|max:6',
            'recommendations.*' => 'required|string|min:3|max:300',
            'description_proposee' => 'nullable|string|max:2000',
            'draft_title' => 'nullable|string|max:180',
            'draft_description' => 'nullable|string|max:1600',
            'draft_program' => 'nullable|array|max:8',
            'draft_program.*' => 'required|string|min:3|max:180',
            'draft_resume' => 'nullable|string|max:300',
            'draft_outline' => 'nullable|array|max:10',
            'draft_outline.*' => 'required|string|min:3|max:240',
            'draft_content' => 'nullable|string|max:10000',
        ];

        return ['task' => $task, 'context' => $context, 'rules' => $rules, 'records' => collect()];
    }

    private function frontTask(string $module, Collection $records): array
    {
        $ids = $records->pluck('id')->all();
        $idRule = ['integer', 'distinct', Rule::in($ids)];

        if (in_array($module, ['equipements', 'locations'], true)) {
            return [
                'Aide l’utilisateur à choisir parmi ces équipements candidats uniquement. Explique les raisons en restant dans les faits fournis, respecte budget/dates, propose des alternatives disponibles. Ne crée ni ne confirme de réservation. JSON exact : answer (texte court) et equipment_ids (0 à 3 identifiants candidats).',
                ['answer' => 'required|string|min:3|max:1200', 'equipment_ids' => 'present|array|max:3', 'equipment_ids.*' => $idRule],
            ];
        }
        if ($module === 'reclamations') {
            return [
                'Accompagne l’utilisateur pour clarifier ou suivre uniquement ses propres réclamations listées. Si la demande porte sur un nouveau brouillon, propose un texte sans le soumettre. Ne décide jamais de l’issue. JSON exact : answer, draft, category (technique, paiement, equipement, comportement, autre), priority (basse, moyenne, haute, urgente), sentiment (positif, neutre, negatif), urgency (basse, moyenne, haute, urgente) et missing_information (liste de champs manquants).',
                ['answer' => 'required|string|min:3|max:1200', 'draft' => 'required|string|max:2000', 'category' => ['required', Rule::in(array_keys(Reclamation::CATEGORIES))], 'priority' => ['required', Rule::in(array_keys(Reclamation::PRIORITES))], 'sentiment' => ['nullable', Rule::in(['positif', 'neutre', 'negatif'])], 'urgency' => ['nullable', Rule::in(array_keys(Reclamation::PRIORITES))], 'missing_information' => 'nullable|array|max:8', 'missing_information.*' => 'string|max:120'],
            ];
        }
        if ($module === 'evenements') {
            return [
                'Recommande et explique uniquement les événements présents dans le contexte public. N’invente aucun événement, lieu, date, prix ou disponibilité. JSON exact : answer et event_ids (0 à 5 identifiants fournis).',
                ['answer' => 'required|string|min:3|max:1200', 'event_ids' => 'present|array|max:5', 'event_ids.*' => $idRule],
            ];
        }

        return [
            'Aide à lire, résumer ou expliquer les articles publiés du contexte. Ne prétends pas qu’un fait absent figure dans l’article et n’invente pas de contenu. JSON exact : answer et article_ids (0 à 5 identifiants fournis).',
            ['answer' => 'required|string|min:3|max:1200', 'article_ids' => 'present|array|max:5', 'article_ids.*' => $idRule],
        ];
    }

    private function redactContactDetails(string $text): string
    {
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[contact masqué]', $text) ?? $text;

        return preg_replace('/\b(?:\+?216[\s.-]?)?[2-9](?:[\s.-]?\d){7}\b/', '[contact masqué]', $text) ?? $text;
    }
}
