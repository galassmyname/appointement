<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypeRendezVousTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('type_rendez_vous', function (Blueprint $table) {
            $table->id();
            $table->string('nomService');
            $table->text('description');
            $table->integer('priorite');  // La priorité sera un entier (ou vous pouvez ajuster en fonction de vos besoins)
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
        Schema::dropIfExists('type_rendez_vous');
    }
}
