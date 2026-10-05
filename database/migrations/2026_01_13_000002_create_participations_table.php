<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table pivot enrichie : relation N-N entre événements et utilisateurs
        Schema::create('participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id')->constrained('evenements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('statut', ['inscrit', 'present', 'absent', 'annule'])->default('inscrit');
            $table->string('commentaire', 500)->nullable();
            $table->timestamps();

            // Un membre ne peut s'inscrire qu'une seule fois au même événement
            $table->unique(['evenement_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participations');
    }
};
