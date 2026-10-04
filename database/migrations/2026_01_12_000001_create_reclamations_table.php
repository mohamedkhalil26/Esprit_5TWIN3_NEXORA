<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamations', function (Blueprint $table) {
            $table->id();
            // Membre qui dépose la réclamation
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('sujet', 150);
            $table->text('description');
            $table->enum('categorie', ['technique', 'paiement', 'equipement', 'comportement', 'autre']);
            $table->enum('priorite', ['basse', 'moyenne', 'haute', 'urgente'])->default('moyenne');
            $table->enum('statut', ['ouverte', 'en_cours', 'resolue', 'fermee'])->default('ouverte');
            $table->string('piece_jointe')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamations');
    }
};
