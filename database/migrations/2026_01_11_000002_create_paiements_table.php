<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            // Relation 1-N : une réservation peut avoir plusieurs paiements (acompte, solde...)
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->enum('methode', ['carte', 'especes', 'virement', 'mobile']);
            $table->enum('statut', ['en_attente', 'valide', 'echoue', 'rembourse'])->default('en_attente');
            $table->string('reference', 30)->unique();
            $table->dateTime('date_paiement');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
