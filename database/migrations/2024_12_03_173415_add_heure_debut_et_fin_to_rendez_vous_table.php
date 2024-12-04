<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('rendez_vous', function (Blueprint $table) {
            $table->time('heureDebut')->nullable(); // Colonne pour l'heure de début
            $table->time('heureFin')->nullable();   // Colonne pour l'heure de fin
        });
    }
    
    public function down()
    {
        Schema::table('rendez_vous', function (Blueprint $table) {
            $table->dropColumn(['heureDebut', 'heureFin']);
        });
    }
    
};
