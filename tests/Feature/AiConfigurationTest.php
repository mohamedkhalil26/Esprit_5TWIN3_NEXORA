<?php

namespace Tests\Feature;

use App\Services\Ai\AiConversationService;
use App\Services\OpenRouterService;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class AiConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['ai.key' => 'test-key']);
    }

    public function test_empty_model_environment_uses_the_canonical_free_gemma_variant_and_json_mode(): void
    {
        $this->assertSame('google/gemma-4-31b-it:free', config('ai.model'));
        $this->assertSame('google/gemma-4-26b-a4b-it:free', config('ai.fallback_model'));

        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [[
            'finish_reason' => 'stop',
            'message' => ['content' => json_encode(['answer' => 'Réponse de test.'])],
        ]]])]);

        $result = app(OpenRouterService::class)->generate(
            'Réponds uniquement en JSON.',
            [],
            ['answer' => 'required|string|min:1'],
        );

        $this->assertSame('Réponse de test.', $result['answer']);
        Http::assertSent(fn ($request) => $request['model'] === 'google/gemma-4-31b-it:free'
            && ($request['response_format'] ?? null) === ['type' => 'json_object']);
        Http::assertSentCount(1);
    }

    public function test_explicit_openrouter_model_is_preserved(): void
    {
        $hadEnvValue = array_key_exists('OPENROUTER_MODEL', $_ENV);
        $hadServerValue = array_key_exists('OPENROUTER_MODEL', $_SERVER);
        $previousEnvValue = $_ENV['OPENROUTER_MODEL'] ?? null;
        $previousServerValue = $_SERVER['OPENROUTER_MODEL'] ?? null;
        $previousProcessValue = getenv('OPENROUTER_MODEL');

        try {
            $_ENV['OPENROUTER_MODEL'] = 'openrouter/explicit-test-model';
            $_SERVER['OPENROUTER_MODEL'] = 'openrouter/explicit-test-model';
            putenv('OPENROUTER_MODEL=openrouter/explicit-test-model');

            $settings = require config_path('ai.php');

            $this->assertSame('openrouter/explicit-test-model', $settings['model']);
        } finally {
            if ($hadEnvValue) {
                $_ENV['OPENROUTER_MODEL'] = $previousEnvValue;
            } else {
                unset($_ENV['OPENROUTER_MODEL']);
            }

            if ($hadServerValue) {
                $_SERVER['OPENROUTER_MODEL'] = $previousServerValue;
            } else {
                unset($_SERVER['OPENROUTER_MODEL']);
            }

            if ($previousProcessValue === false) {
                putenv('OPENROUTER_MODEL');
            } else {
                putenv('OPENROUTER_MODEL='.$previousProcessValue);
            }
        }
    }

    public function test_shared_conversation_assistants_depend_on_the_central_openrouter_service(): void
    {
        $constructor = new ReflectionMethod(AiConversationService::class, '__construct');
        $openRouterParameter = collect($constructor->getParameters())->firstWhere('name', 'openRouter');

        $this->assertNotNull($openRouterParameter);
        $this->assertSame(OpenRouterService::class, $openRouterParameter->getType()->getName());
    }
}
