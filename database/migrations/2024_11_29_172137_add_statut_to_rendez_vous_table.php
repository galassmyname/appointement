<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatutToRendezVousTable extends Migration
{
    /**
     * Applique les modifications à la base de données.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rendez_vous', function (Blueprint $table) {
            $table->string('statut')->default('en attente')->change(); // Ajoute la valeur par défaut 'pending' pour statut
        });
    }

    /**
     * Annule les modifications effectuées par la méthode up().
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rendez_vous', function (Blueprint $table) {
            $table->string('statut')->default(null)->change(); // Annule la valeur par défaut
        });
    }
}
