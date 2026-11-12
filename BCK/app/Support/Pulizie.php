<?php

namespace App\Support;

use App\Models\Camera;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cosa c'è da fare in una camera in un dato giorno, ricavato dalle prenotazioni.
 *
 * Tipo di camera:
 *  - partenza: l'ospite parte oggi (o è già partito oggi, o doveva partire e risulta ancora in casa)
 *  - fermata:  l'ospite resta anche stanotte (pulizia giornaliera)
 *  - arrivo:   camera vuota con un arrivo confermato oggi
 *  - libera:   nessun movimento
 * Contano solo prenotazioni confermate / in casa / partite: quelle "in attesa", annullate e no-show no.
 */
class Pulizie
{
    public const TIPI = [
        'partenza' => 'Partenza',
        'fermata'  => 'Fermata',
        'arrivo'   => 'Arrivo oggi',
        'libera'   => 'Libera',
    ];

    /**
     * @param  Collection  $camere  collezione di Camera
     * @return Collection  camera_id => oggetto con tipo, tipo_etichetta, arrivo_oggi, stato, stato_etichetta, da_pulire
     */
    public static function situazione(Carbon $giorno, Collection $camere): Collection
    {
        $g = $giorno->toDateString();
        $ids = $camere->pluck('id')->all();

        $righe = DB::table('prenotazione_camera as pc')
            ->join('prenotazioni as p', 'p.id', '=', 'pc.prenotazione_id')
            ->whereNull('p.deleted_at')
            ->whereIn('pc.camera_id', $ids)
            ->where(function ($q) use ($g) {
                $q->where(fn ($q) => $q->where('p.stato', 'in_casa')->where('pc.data_arrivo', '<=', $g))
                  ->orWhere(fn ($q) => $q->where('p.stato', 'partita')->whereDate('p.uscita_il', $g))
                  ->orWhere(fn ($q) => $q->where('p.stato', 'confermata')->where('pc.data_arrivo', $g));
            })
            ->get(['pc.camera_id', 'pc.data_partenza', 'p.stato'])
            ->groupBy('camera_id');

        // camere con una pulizia completata in quel giorno (serve per le "fermate": restano "pulita" nel db da ieri)
        $pulitaOggi = DB::table('registri_pulizia')
            ->where('stato_nuovo', 'pulita')
            ->whereDate('created_at', $g)
            ->whereIn('camera_id', $ids)
            ->pluck('camera_id')->unique()->flip();

        return $camere->mapWithKeys(function ($camera) use ($righe, $pulitaOggi, $g) {
            $partenza = $fermata = $arrivo = false;
            foreach ($righe->get($camera->id, collect()) as $r) {
                if ($r->stato === 'partita') {
                    $partenza = true;
                } elseif ($r->stato === 'in_casa') {
                    if ((string) $r->data_partenza <= $g) {
                        $partenza = true;
                    } else {
                        $fermata = true;
                    }
                } elseif ($r->stato === 'confermata') {
                    $arrivo = true;
                }
            }

            $tipo = $partenza ? 'partenza' : ($fermata ? 'fermata' : ($arrivo ? 'arrivo' : 'libera'));

            $stato = $camera->stato_pulizia ?: 'pulita';
            if ($stato === 'ispezionata') {
                $stato = 'pulita';
            }
            // una camera con ospite che resta va ripulita ogni giorno
            if ($stato === 'pulita' && $tipo === 'fermata' && ! $pulitaOggi->has($camera->id)) {
                $stato = 'sporca';
            }

            return [$camera->id => (object) [
                'tipo'            => $tipo,
                'tipo_etichetta'  => self::TIPI[$tipo],
                'arrivo_oggi'     => $arrivo,
                'stato'           => $stato,
                'stato_etichetta' => Camera::STATI[$stato] ?? ucfirst($stato),
                'da_pulire'       => in_array($stato, ['sporca', 'in_pulizia'], true),
            ]];
        });
    }
}
