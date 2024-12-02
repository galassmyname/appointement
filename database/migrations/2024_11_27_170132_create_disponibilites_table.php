<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisponibilitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disponibilites', function (Blueprint $table) {
            $table->id();
            $table->string('jour');
            $table->time('heureDebut'); // Heure de début
            $table->time('heureFin'); // Heure de fin
            $table->foreignId('prestataire_id')->constrained()->onDelete('cascade'); // Clé étrangère vers la table prestataires
            $table->boolean('estDisponible')->default(true); // Indicateur de disponibilité
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disponibilites');
    }
}
