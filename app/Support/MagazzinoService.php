<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        $movimento = [
            'articolo_id' => $articoloId,
            'tipo'        => 'scarico',
            'quantita'    => $q,
            'sorgente'    => $sorgente,
            'riferimento' => $riferimento,
            'utente_id'   => $utenteId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
        DB::table('movimenti_magazzino')->insert($movimento);

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

    /** Carico manuale (per quando arriva il fornitore o rettifica) */
    public static function caricaDiretto(int $articoloId, float $quantita, ?int $utenteId = null, ?string $nota = null): void
    {
        $q = round($quantita, 3);
        if ($q <= 0) return;

        DB::table('articoli_magazzino')->where('id', $articoloId)->increment('scorta_attuale', $q);
        
        $movimento = [
            'articolo_id' => $articoloId,
            'tipo'        => 'carico',
            'quantita'    => $q,
            'sorgente'    => 'manuale',
            'utente_id'   => $utenteId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
        if (Schema::hasColumn('movimenti_magazzino', 'nota')) {
            $movimento['nota'] = $nota;
        }
        DB::table('movimenti_magazzino')->insert($movimento);
    }

    /**
     * Articoli del magazzino centrale sotto soglia.
     */
    public static function sottoSoglia(): Collection
    {
        return DB::table('articoli_magazzino')
            ->where('attivo', true)
            ->whereNotNull('soglia_riordino')
            ->whereColumn('scorta_attuale', '<=', 'soglia_riordino')
            ->get();
    }

    /**
     * Articoli di un reparto sotto soglia.
     */
    public static function sottoSogliaReparto(int $repartoId): Collection
    {
        if (!Schema::hasTable('giacenze_reparto')) {
            return collect();
        }

        return DB::table('giacenze_reparto as g')
            ->join('articoli_magazzino as a', 'a.id', '=', 'g.articolo_id')
            ->where('g.reparto_id', $repartoId)
            ->where('a.attivo', true)
            ->whereNotNull('g.soglia')
            ->whereColumn('g.scorta', '<=', 'g.soglia')
            ->select('a.*', 'g.scorta as scorta_reparto', 'g.soglia as soglia_reparto', 'g.quantita_rifornimento')
            ->get();
    }

    /**
     * Contatori avvisi per la dashboard magazzino.
     */
    public static function avvisi(): array
    {
        $richieste = 0;
        if (Schema::hasTable('richieste_reparto')) {
            $richieste = DB::table('richieste_reparto')->where('stato', 'aperta')->count();
        }

        $negativi = DB::table('articoli_magazzino')
            ->where('attivo', true)
            ->where('scorta_attuale', '<', 0)
            ->count();

        return [
            'richieste' => $richieste,
            'negativi'  => $negativi,
        ];
    }

    /**
     * Movimento generico (carico / scarico) manuale o automatico.
     */
    public static function muovi(
        int $articoloId,
        string $tipo,
        float $quantita,
        string $sorgente,
        ?string $riferimento = null,
        ?int $utenteId = null,
        ?string $nota = null,
        ?int $repartoId = null
    ): void {
        $q = round($quantita, 3);
        if ($q <= 0) return;

        if ($tipo === 'carico') {
            DB::table('articoli_magazzino')->where('id', $articoloId)->increment('scorta_attuale', $q);
        } else {
            DB::table('articoli_magazzino')->where('id', $articoloId)->decrement('scorta_attuale', $q);
        }

        $mov = [
            'articolo_id' => $articoloId,
            'tipo'        => $tipo,
            'quantita'    => $q,
            'sorgente'    => $sorgente,
            'riferimento' => $riferimento,
            'utente_id'   => $utenteId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];

        if ($repartoId !== null && Schema::hasColumn('movimenti_magazzino', 'reparto_id')) {
            $mov['reparto_id'] = $repartoId;
        }
        if ($nota !== null && Schema::hasColumn('movimenti_magazzino', 'nota')) {
            $mov['nota'] = $nota;
        }

        DB::table('movimenti_magazzino')->insert($mov);
    }

    /**
     * Verifica soglia e aggiunge l'articolo alle bozze d'ordine fornitori.
     */
    public static function controllaSoglia(int $articoloId): bool
    {
        if (!Schema::hasTable('ordini_fornitore') || !Schema::hasTable('righe_ordine')) {
            return false;
        }

        $a = DB::table('articoli_magazzino')->where('id', $articoloId)->where('attivo', true)->first();
        if (!$a || $a->soglia_riordino === null || (float) $a->scorta_attuale > (float) $a->soglia_riordino) {
            return false;
        }
        if (!$a->fornitore_id || !(float) $a->quantita_standard_ordine) {
            return false;
        }

        $ordine = DB::table('ordini_fornitore')
            ->where('fornitore_id', $a->fornitore_id)
            ->where('stato', 'bozza')
            ->first();

        $ordineId = $ordine?->id;
        if (!$ordineId) {
            $ordineId = DB::table('ordini_fornitore')->insertGetId([
                'fornitore_id' => $a->fornitore_id,
                'stato'        => 'bozza',
                'creato_da'    => auth()->id(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        DB::table('righe_ordine')->updateOrInsert(
            ['ordine_id' => $ordineId, 'articolo_id' => $a->id],
            [
                'quantita_ordinata' => (float) $a->quantita_standard_ordine,
                'prezzo_acquisto'   => $a->prezzo_acquisto,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]
        );

        return true;
    }

    /**
     * Invia un trasferimento dal magazzino centrale a un reparto.
     */
    public static function inviaTrasferimento(
        int $articoloId,
        int $repartoId,
        float $quantita,
        ?int $utenteId = null,
        ?string $nota = null
    ): int {
        $q = round($quantita, 3);

        DB::table('articoli_magazzino')->where('id', $articoloId)->decrement('scorta_attuale', $q);

        $mov = [
            'articolo_id' => $articoloId,
            'tipo'        => 'scarico',
            'quantita'    => $q,
            'sorgente'    => 'trasferimento',
            'utente_id'   => $utenteId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
        if (Schema::hasColumn('movimenti_magazzino', 'reparto_id')) {
            $mov['reparto_id'] = $repartoId;
        }
        if (Schema::hasColumn('movimenti_magazzino', 'nota')) {
            $mov['nota'] = $nota ?: 'Trasferimento a reparto';
        }
        DB::table('movimenti_magazzino')->insert($mov);

        return (int) DB::table('trasferimenti_reparto')->insertGetId([
            'articolo_id'      => $articoloId,
            'reparto_id'       => $repartoId,
            'quantita_inviata' => $q,
            'stato'            => 'in_arrivo',
            'inviato_da'       => $utenteId,
            'inviato_il'       => now(),
            'nota'             => $nota,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    /**
     * Annulla un trasferimento in arrivo e ripristina la scorta nel centrale.
     */
    public static function annullaTrasferimento(int $trasferimentoId, ?int $utenteId = null): void
    {
        $trasf = DB::table('trasferimenti_reparto')->where('id', $trasferimentoId)->first();
        if (!$trasf || $trasf->stato !== 'in_arrivo') {
            return;
        }

        DB::table('articoli_magazzino')->where('id', $trasf->articolo_id)->increment('scorta_attuale', (float) $trasf->quantita_inviata);

        $mov = [
            'articolo_id' => $trasf->articolo_id,
            'tipo'        => 'carico',
            'quantita'    => (float) $trasf->quantita_inviata,
            'sorgente'    => 'trasferimento',
            'utente_id'   => $utenteId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
        if (Schema::hasColumn('movimenti_magazzino', 'reparto_id')) {
            $mov['reparto_id'] = $trasf->reparto_id;
        }
        if (Schema::hasColumn('movimenti_magazzino', 'nota')) {
            $mov['nota'] = 'Annullamento trasferimento #' . $trasferimentoId;
        }
        DB::table('movimenti_magazzino')->insert($mov);

        DB::table('trasferimenti_reparto')->where('id', $trasferimentoId)->update([
            'stato'      => 'annullato',
            'updated_at' => now(),
        ]);
    }

    /**
     * Scarico consumi Minibar per camera/controllo.
     */
    public static function scaricaMinibar(int $controlloId, array $righe, ?int $utenteId = null): void
    {
        if (empty($righe)) return;

        foreach ($righe as $minibarArticoloId => $quantita) {
            $q = (float) $quantita;
            if ($q <= 0) continue;

            $art = DB::table('minibar_articoli')->where('id', $minibarArticoloId)->first();
            if ($art && !empty($art->magazzino_articolo_id)) {
                self::scaricaDiretto(
                    (int) $art->magazzino_articolo_id,
                    $q,
                    'minibar',
                    'minibar:' . $controlloId,
                    $utenteId
                );
            }
        }
    }
}
