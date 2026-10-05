<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            // Auteur de l'article
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('titre', 180)->unique();
            $table->string('slug', 200)->unique();
            $table->string('resume', 300);
            $table->longText('contenu');
            $table->enum('categorie', ['solaire', 'eolien', 'stockage', 'economies', 'actualites']);
            $table->string('image')->nullable();
            $table->enum('statut', ['brouillon', 'publie'])->default('brouillon');
            $table->dateTime('publie_le')->nullable();
            $table->unsignedInteger('vues')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
