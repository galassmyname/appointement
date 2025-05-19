<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Renommer la colonne
            $table->renameColumn('jour', 'date');
        });

        // Séparer le changement de type pour éviter les conflits
        Schema::table('reservations', function (Blueprint $table) {
            // Changer le type en 'date'
            $table->date('date')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revenir au type précédent (string ici à adapter selon le type original)
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('date')->change();
        });

        // Renommer à nouveau la colonne
        Schema::table('reservations', function (Blueprint $table) {
            $table->renameColumn('date', 'jour');
        });
    }
};
