<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRapportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rapports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prestataire_id'); // Référence à l'ID du prestataire
            $table->integer('totalRendezVous'); // Nombre total de rendez-vous
            $table->float('tauxAnnulation'); // Taux d'annulation
            $table->float('tauxOccupationPrestataire'); // Taux d'occupation du prestataire
            $table->date('dateRapport'); // Date du rapport
            $table->timestamps(); // Champs `created_at` et `updated_at`

            // Définir la clé étrangère pour le prestataire
            $table->foreign('prestataire_id')->references('id')->on('prestataires')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rapports');
    }
}

