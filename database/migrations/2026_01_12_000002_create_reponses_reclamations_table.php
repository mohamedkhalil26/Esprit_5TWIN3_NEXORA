<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reponses_reclamations', function (Blueprint $table) {
            $table->id();
            // Relation 1-N : une réclamation reçoit plusieurs réponses
            $table->foreignId('reclamation_id')->constrained('reclamations')->cascadeOnDelete();
            // Auteur de la réponse (administrateur ou membre)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reponses_reclamations');
    }
};
