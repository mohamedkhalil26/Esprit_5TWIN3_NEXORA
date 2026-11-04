<?php

namespace App\Http\Controllers;

use App\Models\AiQuizAttempt;
use App\Models\AiQuizDefinition;
use App\Models\Article;
use App\Services\Ai\Blog\ArticleQuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ArticleQuizController extends Controller
{
    public function show(Request $request, Article $article): View
    {
        abort_unless($article->statut === 'publie' && $article->publie_le?->isPast(), 404);
        $quiz = AiQuizDefinition::where('article_id', $article->id)->where('locale', app()->getLocale())->where('status', 'published')->latest('version')->first();

        return view('ai.quiz', compact('article', 'quiz'));
    }

    public function generate(Request $request, Article $article, ArticleQuizService $service): RedirectResponse
    {
        try {
            $service->generate($article, $request->user(), app()->getLocale());
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('ai.quiz.show', $article)->with('success', 'Quiz généré à partir de l’article publié.');
    }

    public function submit(Request $request, AiQuizDefinition $quiz): View
    {
        abort_unless($quiz->status === 'published', 404);
        $answers = $request->validate(['answers' => 'nullable|array'])['answers'] ?? [];
        $key = $quiz->answer_key;
        $score = 0;
        foreach ($key as $questionId => $answer) {
            if (($answers[$questionId] ?? null) === $answer) {
                $score++;
            }
        }
        $attempt = AiQuizAttempt::create(['ai_quiz_definition_id' => $quiz->id, 'user_id' => $request->user()->id, 'score' => $score, 'total' => count($key), 'answers' => $answers, 'completed_at' => now()]);

        return view('ai.quiz-result', compact('quiz', 'attempt'));
    }
}
