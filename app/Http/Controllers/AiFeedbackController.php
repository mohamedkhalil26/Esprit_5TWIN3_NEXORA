<?php

namespace App\Http\Controllers;

use App\Models\AiFeatureRun;
use App\Models\AiConversation;
use App\Models\AiFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiFeedbackController extends Controller
{
    private const FEATURES = ['equipements', 'locations', 'reclamations', 'evenements', 'blog'];

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'feature' => ['required', Rule::in(self::FEATURES)],
            'conversation_id' => ['required', 'integer'],
            'feedback_type' => ['required', Rule::in(['helpful', 'not_helpful', 'incorrect', 'irrelevant'])],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'ai_feature_run_id' => ['nullable', 'integer', 'exists:ai_feature_runs,id'],
        ]);
        $conversation = AiConversation::whereKey($data['conversation_id'])
            ->where('user_id', $request->user()->id)
            ->where('module', $data['feature'])
            ->firstOrFail();
        if ($conversation->messages()->latest('id')->value('role') !== 'assistant') {
            throw ValidationException::withMessages([
                'conversation_id' => __('ai_conversation.feedback_requires_answer'),
            ]);
        }
        if (! empty($data['ai_feature_run_id'])) {
            abort_unless(AiFeatureRun::whereKey($data['ai_feature_run_id'])->where('user_id', $request->user()->id)->exists(), 404);
        }
        $data['user_id'] = $request->user()->id;
        AiFeedback::create($data);

        return back()->with('success', 'Merci pour votre retour sur cette fonctionnalité IA.');
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $feedback = AiFeedback::latest()->with('user:id,name')->limit(100)->get();
        $summary = AiFeedback::query()->selectRaw('feature, feedback_type, COUNT(*) AS total')->groupBy('feature', 'feedback_type')->get();

        return view('admin.ai-feedback.index', compact('feedback', 'summary'));
    }

    public function destroy(Request $request, AiFeedback $feedback): RedirectResponse
    {
        abort_unless($feedback->user_id === $request->user()->id, 404);
        $feedback->delete();

        return back()->with('success', 'Votre feedback a été supprimé.');
    }
}
