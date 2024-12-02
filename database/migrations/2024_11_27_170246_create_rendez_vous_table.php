<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRendezVousTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rendez_vous', function (Blueprint $table) {
            $table->id(); // ID de la table
            $table->integer('duree'); // Durée
            $table->integer('delaiPreReservation'); // Délai de pré-réservation
            $table->integer('intervalPlanification'); // Intervalle de planification
            $table->integer('dureeAvantAnnulation'); // Durée avant annulation
            
            // Ajouter les colonnes nécessaires pour les clés étrangères
            $table->unsignedBigInteger('type_rendezvous_id'); // Ajouter la colonne type_rendezvous_id
            $table->unsignedBigInteger('client_id'); // Ajouter la colonne client_id
            $table->unsignedBigInteger('prestataire_id'); // Ajouter la colonne prestataire_id
            $table->unsignedBigInteger('disponibilite_id')->nullable(); // Ajouter la colonne disponibilite_id

            // Définir les clés étrangères
            $table->foreign('type_rendezvous_id')->references('id')->on('type_rendez_vous')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('prestataire_id')->references('id')->on('prestataires')->onDelete('cascade');
            $table->foreign('disponibilite_id')->references('id')->on('disponibilites')->onDelete('set null'); // Si la disponibilité est supprimée, on met à null
            
            $table->string('statut'); // Statut du rendez-vous (ex : 'pending', 'confirmed', 'canceled')

            $table->timestamps(); // Champs `created_at` et `updated_at`
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rendez_vous');
    }
}
