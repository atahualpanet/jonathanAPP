<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Camera;
use App\Models\Ospite;
use App\Models\Prenotazione;
use Carbon\Carbon;

/**
 * Dati demo per vedere il tableau come nel mockup.
 * Date relative a oggi. Lanciare DOPO HotelSeeder:
 *   php artisan db:seed --class=TableauDemoSeeder
 */
class TableauDemoSeeder extends Seeder
{
    public function run(): void
    {
        $oggi = Carbon::today();

        // Il mockup usa le camere 101-106 al primo piano: creiamo quelle mancanti
        $tipologia = \App\Models\TipologiaCamera::first();
        if ($tipologia) {
            foreach (['103', '104', '105', '106'] as $numero) {
                Camera::firstOrCreate(
                    ['numero' => $numero],
                    ['tipologia_camera_id' => $tipologia->id, 'piano' => 1, 'stato_pulizia' => 'pulita', 'attiva' => true]
                );
            }
            // 106 in lavori (barra grigia nel tableau)
            Camera::where('numero', '106')->update(['stato_pulizia' => 'manutenzione']);
        }

        // [cognome, camera, giorno inizio (offset), giorno fine (offset, partenza), stato]
        $demo = [
            ['Rossi',   '101', 0, 3, 'in_casa'],
            ['Bianchi', '102', 1, 3, 'confermata'],
            ['Verdi',   '102', 4, 7, 'confermata'],
            ['Conti',   '103', 0, 1, 'in_casa'],
            ['Greco',   '103', 2, 6, 'confermata'],
            ['Ferri',   '104', 0, 5, 'in_casa'],
            ['Marino',  '105', 3, 6, 'in_attesa'],
            ['Neri',    '106', 3, 5, 'confermata'],
        ];

        foreach ($demo as $i => [$cognome, $numero, $da, $a, $stato]) {
            $camera = Camera::where('numero', $numero)->first();
            if (! $camera) {
                continue;
            }

            $codice = sprintf('DEMO-%04d', $i + 1);
            if (Prenotazione::withTrashed()->where('codice', $codice)->exists()) {
                continue; // già creata
            }

            $ospite = Ospite::create(['nome' => 'Demo', 'cognome' => $cognome]);

            $arrivo = $oggi->copy()->addDays($da);
            $partenza = $oggi->copy()->addDays($a);

            $prenotazione = Prenotazione::create([
                'codice'        => $codice,
                'ospite_id'     => $ospite->id,
                'data_arrivo'   => $arrivo,
                'data_partenza' => $partenza,
                'adulti'        => 2,
                'stato'         => $stato,
                'canale'        => 'diretto',
            ]);

            $prenotazione->camere()->attach($camera->id, [
                'data_arrivo'   => $arrivo->toDateString(),
                'data_partenza' => $partenza->toDateString(),
                'tariffa'       => 142,
            ]);
        }
    }
}
