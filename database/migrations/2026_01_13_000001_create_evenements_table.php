<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evenements', function (Blueprint $table) {
            $table->id();
            $table->string('titre', 150);
            $table->text('description');
            $table->enum('type', ['atelier', 'formation', 'conference', 'sortie']);
            $table->string('lieu', 150);
            $table->string('ville', 100);
            $table->dateTime('date_debut');
            $table->dateTime('date_fin');
            $table->unsignedSmallInteger('capacite');
            $table->decimal('prix', 8, 2)->default(0);
            $table->string('image')->nullable();
            $table->enum('statut', ['planifie', 'annule', 'termine'])->default('planifie');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements');
    }
};
