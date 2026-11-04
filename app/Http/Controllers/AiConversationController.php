<?php

namespace App\Http\Controllers;

use App\Exceptions\OpenRouterException;
use App\Models\AiConversation;
use App\Models\AiFeedback;
use App\Models\AiMessage;
use App\Models\Article;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\Reclamation;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Ai\AiContextBuilder;
use App\Services\Ai\AiConversationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AiConversationController extends Controller
{
    private const MODULES = [
        'equipements' => 'Smart Equipment Advisor',
        'locations' => 'Booking Copilot',
        'reclamations' => 'Claim Companion',
        'evenements' => 'Event Companion',
        'blog' => 'Reading Assistant',
    ];

    public function showFront(Request $request, string $module, AiContextBuilder $contexts)
    {
        return $this->showExperience($request, 'front', $module, $contexts);
    }

    public function showAdmin(Request $request, string $module, AiContextBuilder $contexts)
    {
        return $this->showExperience($request, 'admin', $module, $contexts);
    }

    private function showExperience(Request $request, string $audience, string $module, AiContextBuilder $contexts)
    {
        $this->authorizeExperience($request->user(), $audience, $module);
        $conversations = AiConversation::where('user_id', $request->user()->id)
            ->where('module', $module)->where('audience', $audience)->latest('updated_at')->limit(20)->get();
        $conversation = null;
        $messages = collect();
        $references = collect();
        $referenceIds = [];
        if ($request->filled('conversation_id')) {
            $conversation = AiConversation::where('user_id', $request->user()->id)
                ->where('module', $module)->where('audience', $audience)->findOrFail($request->integer('conversation_id'));
            $messages = $conversation->messages()->latest('id')->limit(40)->get()->reverse()->values();
            $referenceKey = match ($module) {
                'equipements', 'locations' => 'equipment_ids',
                'evenements' => 'event_ids',
                'blog' => 'article_ids',
                default => null,
            };
            if ($referenceKey) {
                $referenceIds = $messages->where('role', 'assistant')->flatMap(function (AiMessage $message) use ($referenceKey) {
                    $data = json_decode($message->content, true);

                    return is_array($data) && is_array($data[$referenceKey] ?? null) ? $data[$referenceKey] : [];
                })->map(fn ($id) => (int) $id)->unique()->all();
            }
        }
        $selectedContext = $conversation?->context_data ?? array_filter([
            'article_id' => $request->integer('article_id') ?: null,
            'event_id' => $request->integer('event_id') ?: null,
            'claim_id' => $request->integer('claim_id') ?: null,
            'budget' => $request->query('budget'),
            'date_debut' => $request->query('date_debut'),
            'date_fin' => $request->query('date_fin'),
        ]);
        $references = $contexts->references($module, $referenceIds, $request->user(), $selectedContext);
        $selectedRecord = match ($module) {
            'blog' => ! empty($selectedContext['article_id']) ? Article::publies()->find($selectedContext['article_id']) : null,
            'evenements' => ! empty($selectedContext['event_id']) ? Evenement::aVenir()->find($selectedContext['event_id']) : null,
            'reclamations' => ! empty($selectedContext['claim_id']) ? Reclamation::where('user_id', $request->user()->id)->find($selectedContext['claim_id']) : null,
            default => null,
        };
        $userFeedback = AiFeedback::where('user_id', $request->user()->id)->where('feature', $module)->latest()->limit(5)->get();

        return view('ai.conversation', [
            'audience' => $audience,
            'module' => $module,
            'title' => __('ai_hub.modules.'.$module.($audience === 'admin' ? '.back_title' : '.front_title')),
            'description' => $audience === 'admin' ? __('ai_conversation.admin_description') : __('ai_hub.modules.'.$module.'.front_description'),
            'suggestions' => __('ai_conversation.suggestions.'.$audience.'.'.$module),
            'conversations' => $conversations,
            'conversation' => $conversation,
            'messages' => $messages,
            'references' => $references,
            'selectedRecord' => $selectedRecord,
            'selectedContext' => $selectedContext,
            'userFeedback' => $userFeedback,
        ]);
    }

    public function sendFront(Request $request, string $module, AiConversationService $service)
    {
        return $this->sendExperience($request, 'front', $module, $service);
    }

    public function sendAdmin(Request $request, string $module, AiConversationService $service)
    {
        return $this->sendExperience($request, 'admin', $module, $service);
    }

    private function sendExperience(Request $request, string $audience, string $module, AiConversationService $service)
    {
        $this->authorizeExperience($request->user(), $audience, $module);
        $data = $request->validate([
            'question' => 'required|string|min:3|max:2000',
            'conversation_id' => 'nullable|integer',
            'retry' => 'sometimes|boolean',
            'budget' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'date_debut' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today', 'required_with:date_fin'],
            'date_fin' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_debut', 'required_with:date_debut'],
            'article_id' => ['nullable', 'integer', Rule::exists('articles', 'id')->where('statut', 'publie')],
            'event_id' => ['nullable', 'integer', Rule::exists('evenements', 'id')->where('statut', 'planifie')],
            'claim_id' => ['nullable', 'integer', Rule::exists('reclamations', 'id')->where('user_id', $request->user()->id)],
            'ville' => 'nullable|string|max:120',
            'type' => ['nullable', Rule::in(array_keys(Evenement::TYPES))],
            'puissance_min' => 'nullable|numeric|min:0|max:1000000',
            'capacite_min' => 'nullable|numeric|min:0|max:1000000',
            'etat' => ['nullable', Rule::in(array_keys(Equipement::ETATS))],
        ]);
        if ($audience === 'front' && $module === 'locations' && ! empty($data['date_debut']) && ! empty($data['date_fin']) && Reservation::calculerJours($data['date_debut'], $data['date_fin']) > 30) {
            throw ValidationException::withMessages(['date_fin' => 'La période de réservation ne peut pas dépasser 30 jours.']);
        }

        if (empty($data['conversation_id'])) {
            $data['conversation_id'] = AiConversation::create([
                'user_id' => $request->user()->id,
                'module' => $module,
                'audience' => $audience,
                'title' => __('ai_conversation.conversation_title', [
                    'module' => __('ai_conversation.module_labels.'.$module),
                    'date' => now()->locale(app()->getLocale())->translatedFormat('d M H:i'),
                ]),
                'context_data' => array_intersect_key($data, array_flip(['article_id', 'event_id', 'claim_id', 'budget', 'date_debut', 'date_fin', 'ville', 'type', 'puissance_min', 'capacite_min', 'etat'])),
            ])->id;
        }

        try {
            $conversation = $service->send($module, $audience, $request->user(), $data);
        } catch (RuntimeException $exception) {
            $errorKey = $this->conversationErrorKey($exception);

            return redirect()->route($audience === 'admin' ? 'ai.admin.show' : 'ai.front.show', ['module' => $module, 'conversation_id' => $data['conversation_id']])
                ->withInput()->with('ai_response_error_message', __('ai_conversation.errors.'.$errorKey))->with('ai_response_error', true);
        }

        return redirect()->route($audience === 'admin' ? 'ai.admin.show' : 'ai.front.show', ['module' => $module, 'conversation_id' => $conversation->id]);
    }

    public function destroyFront(Request $request, string $module, int $conversation)
    {
        return $this->destroyExperience($request, 'front', $module, $conversation);
    }

    public function destroyAdmin(Request $request, string $module, int $conversation)
    {
        return $this->destroyExperience($request, 'admin', $module, $conversation);
    }

    private function destroyExperience(Request $request, string $audience, string $module, int $conversation)
    {
        $this->authorizeExperience($request->user(), $audience, $module);
        AiConversation::where('user_id', $request->user()->id)->where('module', $module)->where('audience', $audience)->findOrFail($conversation)->delete();

        return redirect()->route($audience === 'admin' ? 'ai.admin.show' : 'ai.front.show', $module)->with('success', 'Conversation supprimée.');
    }

    private function authorizeExperience(User $user, string $audience, string $module): void
    {
        abort_unless(array_key_exists($module, self::MODULES), 404);
        abort_unless(in_array($audience, ['front', 'admin'], true), 404);
        abort_if($audience === 'admin' && ! $user->isAdmin(), 403);
    }

    private function conversationErrorKey(RuntimeException $exception): string
    {
        if (str_contains($exception->getMessage(), 'Aucun modèle gratuit') || str_contains($exception->getMessage(), 'Aucun modèle IA gratuit')) {
            return 'model_not_ready';
        }

        if ($exception instanceof OpenRouterException) {
            return match ($exception->httpStatus) {
                429 => 'rate_limited',
                401 => 'invalid_configuration',
                402 => 'credits_exhausted',
                403 => 'model_forbidden',
                400 => 'request_rejected',
                default => 'provider_unavailable',
            };
        }

        if (str_contains($exception->getMessage(), 'configuration') || str_contains($exception->getMessage(), 'configuré')) {
            return 'invalid_configuration';
        }

        return 'generation_failed';
    }
}
