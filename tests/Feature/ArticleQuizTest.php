<?php

namespace Tests\Feature;

use App\Models\AiQuizDefinition;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArticleQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_generation_stores_answer_key_server_side_only(): void
    {
        $article = Article::factory()->create([
            'statut' => 'publie',
            'publie_le' => now()->subMinute(),
            'contenu' => 'Contenu publié sur les batteries solaires.',
        ]);

        $user = User::factory()->create();

        // Configuration IA fictive pour le test
        config([
            'ai.key' => 'test-key',
            'ai.model' => 'test-model',
        ]);

        // Simulation de la réponse OpenRouter
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    [
                        'finish_reason' => 'stop',
                        'message' => [
                            'content' => json_encode([
                                'questions' => [
                                    [
                                        'id' => 'q1',
                                        'question' => 'Quelle énergie ?',
                                        'choices' => ['Solaire', 'Fossile'],
                                        'answer' => 'Solaire',
                                    ],
                                    [
                                        'id' => 'q2',
                                        'question' => 'Quel contenu ?',
                                        'choices' => ['Batteries', 'Avions'],
                                        'answer' => 'Batteries',
                                    ],
                                    [
                                        'id' => 'q3',
                                        'question' => 'Article publié ?',
                                        'choices' => ['Oui', 'Non'],
                                        'answer' => 'Oui',
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($user)
            ->post(route('ai.quiz.generate', $article));

        $response->assertRedirect();

        $quiz = AiQuizDefinition::sole();

        // Les bonnes réponses ne doivent pas être exposées dans les questions
        $this->assertArrayNotHasKey('answer', $quiz->questions[0]);

        // Vérification de l'affichage du quiz
        $this->get(route('ai.quiz.show', $article))
            ->assertOk()
            ->assertDontSee('answer_key', false)
            ->assertDontSee('name="answer"', false);

        // Vérification de la conservation de la bonne réponse côté serveur
        $this->assertSame('Solaire', $quiz->answer_key['q1']);
    }

    public function test_quiz_score_is_calculated_by_laravel(): void
    {
        $article = Article::factory()->create([
            'statut' => 'publie',
            'publie_le' => now()->subMinute(),
        ]);

        $user = User::factory()->create();

        $quiz = AiQuizDefinition::create([
            'article_id' => $article->id,
            'version' => 1,
            'locale' => 'fr',
            'questions' => [
                [
                    'id' => 'q1',
                    'question' => 'Question ?',
                    'choices' => ['Oui', 'Non'],
                ],
            ],
            'answer_key' => ['q1' => 'Oui'],
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('ai.quiz.submit', $quiz), [
                'answers' => ['q1' => 'Oui'],
            ])
            ->assertOk()
            ->assertSee('1 / 1');

        $this->assertDatabaseHas('ai_quiz_attempts', [
            'user_id' => $user->id,
            'score' => 1,
            'total' => 1,
        ]);
    }

    public function test_quiz_generation_shows_a_clear_error_when_no_free_model_is_validated(): void
    {
        $article = Article::factory()->create([
            'statut' => 'publie',
            'publie_le' => now()->subMinute(),
        ]);

        config([
            'ai.key' => 'test-key',
            'ai.model' => '',
        ]);

        Http::preventStrayRequests();

        $this->actingAs(User::factory()->create())
            ->post(route('ai.quiz.generate', $article))
            ->assertRedirect()
            ->assertSessionHas(
                'error',
                'Aucun modèle gratuit n’a été validé pour ce compte. Le service IA est momentanément indisponible.'
            );

        $this->assertDatabaseCount('ai_quiz_definitions', 0);

        Http::assertNothingSent();
    }
}
