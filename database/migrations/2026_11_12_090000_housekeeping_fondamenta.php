<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Housekeeping, fondamenta:
 *  - camere.stato_pulizia non è più un ENUM fisso (serve il nuovo stato "in_pulizia");
 *  - tabella hk_assegnazioni: quale persona pulisce quale camera in quale giorno;
 *  - nuova funzione "assegna_pulizie" ai profili amministratore, direttore e reception.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camere', function (Blueprint $table) {
            $table->string('stato_pulizia', 20)->default('pulita')->change();
        });

        Schema::create('hk_assegnazioni', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->foreignId('camera_id')->constrained('camere')->cascadeOnDelete();
            $table->foreignId('utente_id')->constrained('utenti')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['data', 'camera_id']);   // una camera ha una sola persona per giorno
            $table->index(['utente_id', 'data']);
        });

        if (Schema::hasTable('ruoli')) {
            foreach (['amministratore', 'direttore', 'reception'] as $chiave) {
                $riga = DB::table('ruoli')->where('chiave', $chiave)->first();
                if (! $riga) {
                    continue;
                }
                $funzioni = json_decode($riga->funzioni ?? '[]', true) ?: [];
                if (! in_array('assegna_pulizie', $funzioni, true)) {
                    $funzioni[] = 'assegna_pulizie';
                    DB::table('ruoli')->where('id', $riga->id)->update(['funzioni' => json_encode($funzioni), 'updated_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hk_assegnazioni');
        // lo stato resta stringa: tornare all'ENUM farebbe fallire le camere "in_pulizia"
    }
};
