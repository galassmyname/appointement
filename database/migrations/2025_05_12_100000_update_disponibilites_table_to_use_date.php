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
        Schema::table('disponibilites', function (Blueprint $table) {
            $table->dropColumn('jour');
            $table->date('date')->after('prestataire_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disponibilites', function (Blueprint $table) {
            $table->dropColumn('date');
            $table->string('jour')->after('prestataire_id')->nullable();
        });
    }
};
