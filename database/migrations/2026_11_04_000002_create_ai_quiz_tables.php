<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_quiz_definitions')) {
            Schema::create('ai_quiz_definitions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->string('locale', 5)->default('fr');
                $table->json('questions');
                $table->text('answer_key');
                $table->string('status', 16)->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->unique(['article_id', 'version', 'locale']);
                $table->index(['article_id', 'status', 'locale']);
            });
        }
        if (! Schema::hasTable('ai_quiz_attempts')) {
            Schema::create('ai_quiz_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_quiz_definition_id')->constrained('ai_quiz_definitions')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('score');
                $table->unsignedInteger('total');
                $table->json('answers');
                $table->timestamp('completed_at');
                $table->timestamps();
                $table->index(['user_id', 'ai_quiz_definition_id', 'completed_at'], 'ai_quiz_attempts_lookup_idx');
            });
        } else {
            Schema::table('ai_quiz_attempts', function (Blueprint $table) {
                $table->index(['user_id', 'ai_quiz_definition_id', 'completed_at'], 'ai_quiz_attempts_lookup_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_quiz_attempts');
        Schema::dropIfExists('ai_quiz_definitions');
    }
};
