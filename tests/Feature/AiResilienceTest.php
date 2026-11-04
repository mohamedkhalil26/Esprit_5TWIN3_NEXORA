<?php

namespace Tests\Feature;

use App\Exceptions\OpenRouterException;
use App\Services\OpenRouterService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiResilienceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config([
            'ai.key' => 'test-key',
            'ai.model' => 'google/gemma-4-31b-it:free',
            'ai.fallback_model' => 'google/gemma-4-26b-a4b-it:free',
        ]);
    }

    public function test_timeouts_leave_room_for_slow_free_models(): void
    {
        $this->assertGreaterThanOrEqual(45, (int) config('ai.timeout'));
        $this->assertGreaterThan((int) config('ai.timeout'), (int) config('ai.total_budget'));
        $this->assertGreaterThanOrEqual(5, (int) config('ai.connect_timeout'));
    }

    public function test_json_mode_is_retried_without_response_format_when_the_provider_rejects_it(): void
    {
        Http::fakeSequence('openrouter.ai/*')
            ->push(['error' => ['message' => 'response_format is not supported for this model']], 400)
            ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{"ok":true}']]]]);

        $this->assertSame(['ok' => true], app(OpenRouterService::class)->generate(
            'Répondre en JSON.', ['synthetique' => true], ['ok' => 'required|boolean'],
        ));

        $requests = Http::recorded()->map(fn ($pair) => $pair[0])->values();
        $this->assertSame(['type' => 'json_object'], $requests[0]['response_format']);
        $this->assertArrayNotHasKey('response_format', $requests[1]);
    }

    public function test_router_models_never_request_json_object_mode(): void
    {
        config(['ai.model' => 'openrouter/free', 'ai.fallback_model' => null]);
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [[
            'finish_reason' => 'stop',
            'message' => ['content' => '{"ok":true}'],
        ]]])]);

        app(OpenRouterService::class)->generate('Tâche factice.', ['synthetique' => true], ['ok' => 'required|boolean']);

        Http::assertSent(fn ($request) => $request['model'] === 'openrouter/free'
            && ! array_key_exists('response_format', $request->data()));
    }

    public function test_structured_content_parts_are_joined_before_validation(): void
    {
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [[
            'finish_reason' => 'stop',
            'message' => ['content' => [['type' => 'text', 'text' => '{"ok":true}']]],
        ]]])]);

        $this->assertSame(['ok' => true], app(OpenRouterService::class)->generate(
            'Retourner un objet.', ['synthetique' => true], ['ok' => 'required|boolean'],
        ));
    }

    public function test_rate_limit_cooldown_is_capped_and_typed(): void
    {
        config(['ai.max_rate_limit_cooldown' => 300]);
        Http::fakeSequence('openrouter.ai/*')->push([], 429, ['Retry-After' => '999999']);

        try {
            app(OpenRouterService::class)->generate('Demande factice.', ['synthetique' => true], ['ok' => 'required|boolean']);
            $this->fail('Le 429 doit interrompre la génération.');
        } catch (OpenRouterException $exception) {
            $this->assertSame(300, $exception->retryAfterSeconds);
            $this->assertTrue($exception->isRateLimited());
            $this->assertSame('rate_limited', $exception->runStatus());
            $this->assertSame('http_429', $exception->runErrorCode());
        }

        Http::assertSentCount(1);
    }

    public function test_timeout_and_configuration_errors_are_distinguishable(): void
    {
        config(['ai.key' => null]);

        try {
            app(OpenRouterService::class)->generate('Demande factice.', [], ['ok' => 'required|boolean']);
            $this->fail('Une clé absente doit être signalée.');
        } catch (OpenRouterException $exception) {
            $this->assertTrue($exception->isConfigurationError());
            $this->assertSame('configuration', $exception->runErrorCode());
        }

        config(['ai.key' => 'test-key']);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(static function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        try {
            app(OpenRouterService::class)->generate('Demande factice.', ['synthetique' => true], ['ok' => 'required|boolean']);
            $this->fail('Un timeout doit être signalé.');
        } catch (OpenRouterException $exception) {
            $this->assertTrue($exception->isTimeout());
            $this->assertSame('timeout', $exception->runStatus());
            $this->assertStringContainsString('expiré', $exception->getMessage());
        }
    }

    public function test_openrouter_headers_are_sent_for_free_tier_tracking(): void
    {
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [[
            'finish_reason' => 'stop',
            'message' => ['content' => '{"ok":true}'],
        ]]])]);

        app(OpenRouterService::class)->generate('Tâche factice.', ['synthetique' => true], ['ok' => 'required|boolean']);

        Http::assertSent(fn ($request) => $request->hasHeader('HTTP-Referer') && $request->hasHeader('X-Title'));
    }
}
