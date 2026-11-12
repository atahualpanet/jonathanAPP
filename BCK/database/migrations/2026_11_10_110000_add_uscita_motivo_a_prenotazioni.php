<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenotazioni', function (Blueprint $table) {
            // Perché l'ospite è partito prima del previsto (emergenza, problema in camera...)
            $table->string('uscita_motivo')->nullable()->after('uscita_il');
        });
    }

    public function down(): void
    {
        Schema::table('prenotazioni', function (Blueprint $table) {
            $table->dropColumn('uscita_motivo');
        });
    }
};
