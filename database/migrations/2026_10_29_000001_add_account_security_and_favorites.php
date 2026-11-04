<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo_path')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->unsignedBigInteger('two_factor_last_step')->nullable();
        });
        Schema::create('article_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'article_id']);
        });
        Schema::create('reclamation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reclamation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('changes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamation_histories');
        Schema::dropIfExists('article_user');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn([
            'photo_path', 'two_factor_secret', 'two_factor_recovery_codes',
            'two_factor_confirmed_at', 'two_factor_last_step',
        ]));
    }
};
