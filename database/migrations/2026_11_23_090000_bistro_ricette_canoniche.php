<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bistro: rende bistro_ricette l'unica fonte del collegamento al magazzino.
 * Recupera anche eventuali associazioni create dalla vecchia colonna diretta,
 * senza sovrascrivere ricette già presenti e senza modificare le giacenze.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bistro_articoli')
            || !Schema::hasTable('articoli_magazzino')
            || !Schema::hasTable('bistro_ricette')
            || !Schema::hasColumn('bistro_articoli', 'magazzino_articolo_id')) {
            return;
        }

        $legacy = DB::table('bistro_articoli')
            ->whereNotNull('magazzino_articolo_id')
            ->orderBy('id')
            ->get(['id', 'magazzino_articolo_id']);

        foreach ($legacy as $articolo) {
            $giaCollegato = DB::table('bistro_ricette')
                ->where('bistro_articolo_id', $articolo->id)
                ->exists();

            $magazzinoEsiste = DB::table('articoli_magazzino')
                ->where('id', $articolo->magazzino_articolo_id)
                ->exists();

            if (!$giaCollegato && $magazzinoEsiste) {
                DB::table('bistro_ricette')->insert([
                    'bistro_articolo_id' => $articolo->id,
                    'magazzino_articolo_id' => $articolo->magazzino_articolo_id,
                    'quantita' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Migrazione dati conservativa: non cancellare collegamenti o giacenze.
    }
};
