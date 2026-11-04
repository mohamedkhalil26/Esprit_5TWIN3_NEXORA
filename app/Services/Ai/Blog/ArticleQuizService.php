<?php

namespace App\Services\Ai\Blog;

use App\Models\AiQuizDefinition;
use App\Models\Article;
use App\Models\User;
use App\Services\OpenRouterService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ArticleQuizService
{
    public function __construct(private readonly OpenRouterService $openRouter) {}

    public function generate(Article $article, User $user, string $locale = 'fr'): AiQuizDefinition
    {
        abort_unless($article->statut === 'publie' && $article->publie_le?->isPast(), 404);
        $result = $this->openRouter->generate(
            'Génère un quiz de compréhension uniquement à partir de cet article. Chaque question possède id, question, choices et answer. Ne crée aucun fait absent.',
            ['article' => ['titre' => $article->titre, 'resume' => $article->resume, 'contenu' => strip_tags($article->contenu)], 'locale' => $locale],
            ['questions' => 'required|array|min:3|max:8', 'questions.*.id' => 'required|string|distinct|max:32', 'questions.*.question' => 'required|string|min:5|max:300', 'questions.*.choices' => 'required|array|min:2|max:5', 'questions.*.choices.*' => 'required|string|max:160', 'questions.*.answer' => 'required|string'],
        );
        $latest = AiQuizDefinition::where('article_id', $article->id)->where('locale', $locale)->max('version') ?? 0;
        $questions = [];
        $answerKey = [];
        foreach ($result['questions'] as $question) {
            if (! in_array($question['answer'], $question['choices'], true)) {
                throw new RuntimeException('Le quiz généré contient un corrigé incohérent.');
            }
            $questions[] = ['id' => $question['id'], 'question' => $question['question'], 'choices' => array_values($question['choices'])];
            $answerKey[$question['id']] = $question['answer'];
        }

        return DB::transaction(fn () => AiQuizDefinition::create([
            'article_id' => $article->id,
            'version' => $latest + 1,
            'locale' => $locale,
            'questions' => $questions,
            'answer_key' => $answerKey,
            'status' => 'published',
            'published_at' => now(),
        ]));
    }
}
