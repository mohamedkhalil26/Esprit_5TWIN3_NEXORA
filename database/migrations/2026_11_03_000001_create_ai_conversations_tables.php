<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module', 32);
            $table->string('audience', 12);
            $table->string('title', 120);
            $table->json('context_data')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'audience', 'module', 'updated_at'], 'ai_conversations_owner_history_idx');
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('role', 12);
            $table->longText('content');
            $table->timestamps();
            $table->index(['conversation_id', 'created_at'], 'ai_messages_conversation_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
