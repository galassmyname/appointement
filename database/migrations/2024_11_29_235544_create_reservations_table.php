<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationsTable extends Migration
{
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestataire_id')->constrained()->onDelete('cascade');
            $table->date('jour');
            $table->time('heureDebut');
            $table->time('heureFin');
            $table->foreignId('client_id')->nullable()->constrained('users')->onDelete('set null'); // Assurez-vous que 'clients' est le bon nom de la table
            $table->timestamps();
        });
        
    }

    public function down()
    {
        Schema::dropIfExists('reservations');
    }
}
