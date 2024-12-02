<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrestatairesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prestataires', function (Blueprint $table) {
            $table->id();  // La clé primaire 'id'
            $table->string('name');  // Nom du prestataire
            $table->string('email')->unique();  // Email unique
            $table->string('telephone')->nullable();  // Téléphone (nullable)
            $table->string('specialite')->nullable();  // Spécialité (nullable)
            $table->boolean('is_admin')->default(false);  // Indicateur si admin
            $table->unsignedBigInteger('role_id');  // Clé étrangère vers la table 'roles'
            $table->string('password');  // Mot de passe
            $table->timestamps();  // Colonnes created_at et updated_at
            $table->softDeletes();  // Pour la suppression logique (si vous souhaitez l'activer)

            // Définir la clé étrangère vers la table 'roles'
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prestataires');
    }
}
