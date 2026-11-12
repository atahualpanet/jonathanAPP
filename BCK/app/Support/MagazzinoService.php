<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class MagazzinoService
{
    /**
     * Scarico diretto dal magazzino centrale.
     * Ritorna un array con eventuali avvisi (es. scorta sotto soglia).
     */
    public static function scaricaDiretto(int $articoloId, float $quantita, string $sorgente, ?string $riferimento = null, ?int $utenteId = null): array
    {
        $q = round($quantita, 3);
        if ($q <= 0) return [];

        $avvisi = [];
        $articolo = DB::table('articoli_magazzino')->where('id', $articoloId)->first();

        if (!$articolo) {
            return ['errore' => 'Articolo di magazzino non trovato.'];
        }

        // 1. Aggiorna la scorta
        DB::table('articoli_magazzino')->where('id', $articoloId)->decrement('scorta_attuale', $q);

        // 2. Registra il movimento
        DB::table('movimenti_magazzino')->insert([
            'articolo_id' => $articoloId,
            'tipo' => 'scarico',
            'quantita' => $q,
            'sorgente' => $sorgente,
            'riferimento' => $riferimento,
            'utente_id' => $utenteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Controllo Soglia e Alert
        $nuovaScorta = (float) $articolo->scorta_attuale - $q;
        if ($articolo->soglia_riordino !== null && $nuovaScorta <= (float) $articolo->soglia_riordino) {
            $avvisi[] = "⚠️ ALERT MAGAZZINO: '{$articolo->nome}' è sceso a {$nuovaScorta} {$articolo->unita_misura} (Soglia: {$articolo->soglia_riordino}). Controllare gli ordini!";
        }

        if ($nuovaScorta < 0) {
            $avvisi[] = "🚨 ATTENZIONE: '{$articolo->nome}' è andato in negativo ({$nuovaScorta}). Verificare le giacenze fisiche.";
        }

        return $avvisi;
    }

    /** Carico manuale (per quando arriva il fornitore) */
    public static function caricaDiretto(int $articoloId, float $quantita, ?int $utenteId = null, ?string $nota = null): void
    {
        $q = round($quantita, 3);
        if ($q <= 0) return;

        DB::table('articoli_magazzino')->where('id', $articoloId)->increment('scorta_attuale', $q);
        DB::table('movimenti_magazzino')->insert([
            'articolo_id' => $articoloId, 'tipo' => 'carico', 'quantita' => $q,
            'sorgente' => 'manuale', 'nota' => $nota, 'utente_id' => $utenteId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}