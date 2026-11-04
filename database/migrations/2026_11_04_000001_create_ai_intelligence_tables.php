<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_feature_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature', 80);
            $table->string('audience', 12);
            $table->string('module', 32)->nullable();
            $table->string('provider', 32)->default('openrouter');
            $table->string('model', 120)->nullable();
            $table->string('prompt_version', 40)->nullable();
            $table->string('locale', 5)->default('fr');
            $table->string('status', 24);
            $table->string('error_code', 48)->nullable();
            $table->string('input_hash', 64)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();
            $table->index(['feature', 'status', 'created_at']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('ai_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_feature_run_id')->nullable()->constrained('ai_feature_runs')->nullOnDelete();
            $table->string('feature', 80);
            $table->string('feedback_type', 32);
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('comment')->nullable();
            $table->string('resource_type', 120)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->timestamps();
            $table->index(['feature', 'feedback_type', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->default('fr');
            $table->json('categories')->nullable();
            $table->json('channels')->nullable();
            $table->string('frequency', 24)->default('instant');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('smart_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 32);
            $table->string('priority', 16)->default('normal');
            $table->string('status', 16)->default('pending');
            $table->string('title', 180);
            $table->text('content');
            $table->string('resource_type', 120)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('dedupe_key', 190)->unique();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_notifications');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('ai_feedback');
        Schema::dropIfExists('ai_feature_runs');
    }
};
