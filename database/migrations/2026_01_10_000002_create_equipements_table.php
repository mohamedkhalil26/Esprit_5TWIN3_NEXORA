<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipements', function (Blueprint $table) {
            $table->id();
            // Relation 1-N : une catégorie possède plusieurs équipements
            $table->foreignId('categorie_id')->constrained('categories')->restrictOnDelete();
            // Propriétaire de l'équipement (particulier qui le met en location)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nom', 150);
            $table->string('marque', 80)->nullable();
            $table->text('description');
            $table->unsignedInteger('puissance_watts')->nullable();
            $table->unsignedInteger('capacite_wh')->nullable();
            $table->decimal('prix_jour', 8, 2);
            $table->decimal('caution', 8, 2)->default(0);
            $table->enum('etat', ['neuf', 'tres_bon', 'bon', 'usage'])->default('bon');
            $table->string('ville', 100);
            $table->boolean('disponible')->default(true);
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipements');
    }
};
