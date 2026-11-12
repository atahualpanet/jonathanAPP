<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonne minime per far funzionare davvero (non solo visivamente) il check-in
 * e gli adempimenti nella scheda prenotazione:
 * - ospiti: se la privacy è stata accettata e quando
 * - prenotazioni: quando sono stati inviati Alloggiati Web e ISTAT
 * (l'invio vero e proprio alle piattaforme esterne non è collegato: qui si
 * registra solo la data dell'invio, per riflettere lo stato nella scheda)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ospiti', function (Blueprint $table) {
            $table->boolean('privacy_accettata')->default(false)->after('note');
            $table->timestamp('privacy_accettata_il')->nullable()->after('privacy_accettata');
        });

        Schema::table('prenotazioni', function (Blueprint $table) {
            $table->timestamp('alloggiati_inviato_il')->nullable()->after('uscita_il');
            $table->timestamp('istat_inviato_il')->nullable()->after('alloggiati_inviato_il');
        });
    }

    public function down(): void
    {
        Schema::table('ospiti', function (Blueprint $table) {
            $table->dropColumn(['privacy_accettata', 'privacy_accettata_il']);
        });

        Schema::table('prenotazioni', function (Blueprint $table) {
            $table->dropColumn(['alloggiati_inviato_il', 'istat_inviato_il']);
        });
    }
};
