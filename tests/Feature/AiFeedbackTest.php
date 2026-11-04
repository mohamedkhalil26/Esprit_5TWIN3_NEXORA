<?php

namespace Tests\Feature;

use App\Models\AiFeatureRun;
use App\Models\AiConversation;
use App\Models\AiFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_feedback_for_a_feature(): void
    {
        $user = User::factory()->create();
        $conversation = AiConversation::create(['user_id' => $user->id, 'module' => 'equipements', 'audience' => 'front', 'title' => 'Equipment assistant']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => json_encode(['answer' => 'Réponse synthétique.'])]);

        $this->actingAs($user)->post(route('ai.feedback.store'), [
            'feature' => 'equipements',
            'conversation_id' => $conversation->id,
            'feedback_type' => 'helpful',
            'rating' => 5,
            'comment' => 'Retour synthétique.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('ai_feedback', ['user_id' => $user->id, 'feature' => 'equipements', 'rating' => 5]);
    }

    public function test_user_cannot_rate_a_latest_question_without_an_assistant_response(): void
    {
        $user = User::factory()->create();
        $conversation = AiConversation::create(['user_id' => $user->id, 'module' => 'equipements', 'audience' => 'front', 'title' => 'Equipment assistant']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => json_encode(['answer' => 'Réponse précédente.'])]);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Question sans réponse.']);

        $this->actingAs($user)->from(route('ai.front.show', ['module' => 'equipements', 'conversation_id' => $conversation->id]))
            ->post(route('ai.feedback.store'), [
                'feature' => 'equipements',
                'conversation_id' => $conversation->id,
                'feedback_type' => 'helpful',
            ])->assertRedirect()->assertSessionHasErrors('conversation_id');

        $this->assertDatabaseCount('ai_feedback', 0);
    }

    public function test_user_cannot_reference_another_users_ai_run(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $run = AiFeatureRun::create(['user_id' => $owner->id, 'feature' => 'equipements', 'audience' => 'front', 'status' => 'completed']);
        $conversation = AiConversation::create(['user_id' => $owner->id, 'module' => 'equipements', 'audience' => 'front', 'title' => 'Equipment assistant']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => json_encode(['answer' => 'Réponse synthétique.'])]);

        $this->actingAs($other)->post(route('ai.feedback.store'), [
            'feature' => 'equipements',
            'conversation_id' => $conversation->id,
            'feedback_type' => 'incorrect',
            'ai_feature_run_id' => $run->id,
        ])->assertNotFound();
    }

    public function test_only_admin_can_consult_feedback(): void
    {
        $this->actingAs(User::factory()->create())->get(route('ai.admin.feedback.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('ai.admin.feedback.index'))->assertOk();
    }

    public function test_user_can_delete_only_own_feedback(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $feedback = AiFeedback::create(['user_id' => $owner->id, 'feature' => 'blog', 'feedback_type' => 'helpful']);

        $this->actingAs($other)->delete(route('ai.feedback.destroy', $feedback))->assertNotFound();
        $this->actingAs($owner)->delete(route('ai.feedback.destroy', $feedback))->assertRedirect();
        $this->assertDatabaseMissing('ai_feedback', ['id' => $feedback->id]);
    }
}
