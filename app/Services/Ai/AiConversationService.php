<?php

namespace App\Services\Ai;

use App\Exceptions\OpenRouterException;
use App\Models\AiConversation;
use App\Models\AiFeatureRun;
use App\Models\User;
use App\Services\OpenRouterService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiConversationService
{
    public function __construct(private readonly AiContextBuilder $contexts, private readonly OpenRouterService $openRouter) {}

    public function send(string $module, string $audience, User $user, array $input): AiConversation
    {
        if (empty($input['conversation_id'])) {
            throw new RuntimeException('Impossible d’ouvrir la conversation. Réessayez depuis l’assistant.');
        }
        $conversation = AiConversation::where('user_id', $user->id)->where('module', $module)->where('audience', $audience)->findOrFail($input['conversation_id']);
        $isRetry = filter_var($input['retry'] ?? false, FILTER_VALIDATE_BOOLEAN);
        unset($input['retry']);
        $selection = array_intersect_key($input, array_flip(['article_id', 'event_id', 'claim_id', 'budget', 'date_debut', 'date_fin']));
        $conversation->context_data = array_merge($conversation->context_data ?? [], $selection);
        $conversation->save();
        $input = array_merge($conversation->context_data ?? [], $input);

        $latestMessage = $conversation->messages()->latest('id')->first();
        $reuseQuestion = $isRetry && $latestMessage?->role === 'user' && $latestMessage->content === $input['question'];
        $historyQuery = $conversation->messages();
        if ($reuseQuestion) {
            $historyQuery->where('id', '!=', $latestMessage->id);
        }
        $history = $historyQuery->latest('id')->limit(7)->get()->reverse()->values()->map(fn ($message) => [
            'role' => $message->role,
            'content' => $message->content,
        ])->all();
        $built = $this->contexts->build($module, $audience, $user, $input['question'], $input, $history);
        $context = $built['context'];
        $context['limite_contexte'] = 'Réponds seulement à partir de ces éléments; dis clairement si l’information demandée manque.';
        try {
            $contextLength = strlen(json_encode($context, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        } catch (\JsonException) {
            throw new RuntimeException('Le contenu de la demande ne peut pas être transmis. Vérifiez les caractères saisis.');
        }
        if ($contextLength > 32000) {
            throw new RuntimeException('Le contexte de cette demande est trop volumineux. Reformulez avec une question plus ciblée.');
        }

        if (! $reuseQuestion) {
            $conversation->messages()->create(['role' => 'user', 'content' => $input['question']]);
        }
        $started = microtime(true);
        $run = AiFeatureRun::create([
            'user_id' => $user->id,
            'feature' => $module,
            'audience' => $audience,
            'module' => $module,
            'provider' => 'openrouter',
            'model' => config('ai.model'),
            'locale' => app()->getLocale(),
            'status' => 'context_built',
            'input_hash' => hash('sha256', $input['question']),
        ]);
        $run->update(['status' => 'sent']);
        try {
            $result = $this->openRouter->generate($built['task'], $context, $built['rules']);
            $result = $this->addRecommendationDetails($module, $result, $context);
        } catch (\Throwable $exception) {
            $run->update([
                'status' => $exception instanceof OpenRouterException && $exception->httpStatus === 429 ? 'rate_limited' : ($exception instanceof ConnectionException ? 'timeout' : 'provider_error'),
                'error_code' => $exception instanceof OpenRouterException ? 'http_'.$exception->httpStatus : 'provider_unavailable',
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);
            throw $exception;
        }
        $conversation->messages()->create(['role' => 'assistant', 'content' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
        $conversation->touch();
        $run->update(['status' => 'completed', 'duration_ms' => (int) round((microtime(true) - $started) * 1000)]);
        Log::info('nexora_ai.conversation_completed', [
            'user_id' => $user->id,
            'module' => $module,
            'audience' => $audience,
            'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
            'model' => config('ai.model'),
        ]);

        return $conversation;
    }

    private function addRecommendationDetails(string $module, array $result, array $context): array
    {
        $idsKey = match ($module) {
            'equipements', 'locations' => 'equipment_ids',
            'evenements' => 'event_ids',
            default => null,
        };
        if ($idsKey === null || empty($result[$idsKey])) {
            return $result;
        }
        $sourceKey = $module === 'evenements' ? 'evenements_a_venir' : 'candidats_reellement_disponibles';
        $sources = collect($context[$sourceKey] ?? [])->keyBy('id');
        $result['recommendation_details'] = collect($result[$idsKey])->map(function ($id) use ($sources) {
            $source = $sources->get((int) $id);

            return $source ? collect($source)->only(['id', 'nom', 'titre', 'score', 'confiance', 'ai_score', 'ai_confidence', 'ai_score_components', 'ai_score_missing', 'places_restantes', 'prix_jour', 'ville'])->all() : null;
        })->filter()->values()->all();

        return $result;
    }
}
