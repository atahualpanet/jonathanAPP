<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Ruolo;
use App\Support\Pulizie;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HousekeepingController extends Controller
{
    /* ====================== MOBILE: il mio piano ====================== */

    public function piano(Request $request)
    {
        $this->soloPersonale();

        $utente = auth()->user();
        $oggi = Carbon::today();

        $assegnate = DB::table('hk_assegnazioni')->where('data', $oggi->toDateString())
            ->where('utente_id', $utente->id)->pluck('camera_id');

        $camere = Camera::with('tipologia')->whereIn('id', $assegnate)->where('attiva', true)
            ->orderBy('piano')->orderBy('numero')->get();

        $sit = Pulizie::situazione($oggi, $camere);

        // fuori servizio: si vedono ma non contano nell'avanzamento
        $fuoriServizio = fn ($c) => $c->stato_pulizia === 'manutenzione';
        $lavorabili = $camere->reject($fuoriServizio);
        $pulite = $lavorabili->filter(fn ($c) => $sit[$c->id]->stato === 'pulita')->count();

        // la prossima: prima quella già "in corso", poi la prima da pulire
        $prossima = $lavorabili->first(fn ($c) => $sit[$c->id]->stato === 'in_pulizia')
            ?? $lavorabili->first(fn ($c) => $sit[$c->id]->da_pulire);

        $filtro = in_array($request->query('filtro'), ['da_pulire', 'pronte'], true) ? $request->query('filtro') : 'tutte';
        $elenco = $camere->filter(function ($c) use ($filtro, $sit, $fuoriServizio) {
            if ($filtro === 'da_pulire') {
                return ! $fuoriServizio($c) && $sit[$c->id]->da_pulire;
            }
            if ($filtro === 'pronte') {
                return ! $fuoriServizio($c) && $sit[$c->id]->stato === 'pulita';
            }
            return true;
        })->values();

        $piani = $camere->pluck('piano')->unique();
        $titolo = $piani->count() === 1 ? 'Piano ' . $piani->first() : 'Le mie camere';

        return view('housekeeping.piano', [
            'utente'     => $utente,
            'titolo'     => $titolo,
            'oggi'       => $oggi,
            'elenco'     => $elenco,
            'sit'        => $sit,
            'totale'     => $lavorabili->count(),
            'pulite'     => $pulite,
            'prossima'   => $prossima,
            'filtro'     => $filtro,
            'haCamere'   => $camere->isNotEmpty(),
            'puoAssegnare' => $this->puoAssegnare(),
        ]);
    }

    /* ====================== DESKTOP: assegnazioni ====================== */

    public function index(Request $request)
    {
        $this->soloPersonale();

        $data = $this->dataRichiesta($request);

        $camere = Camera::with('tipologia')->where('attiva', true)
            ->orderBy('piano')->orderBy('numero')->get();

        $sit = Pulizie::situazione($data, $camere);

        $assegnazioni = DB::table('hk_assegnazioni')->where('data', $data->toDateString())
            ->pluck('utente_id', 'camera_id');

        $personale = $this->personale();

        $riepilogo = [
            'da_pulire'    => $camere->filter(fn ($c) => $c->stato_pulizia !== 'manutenzione' && $sit[$c->id]->stato === 'sporca')->count(),
            'in_corso'     => $camere->filter(fn ($c) => $sit[$c->id]->stato === 'in_pulizia')->count(),
            'pulite'       => $camere->filter(fn ($c) => $sit[$c->id]->stato === 'pulita')->count(),
            'non_assegnate' => $camere->filter(fn ($c) => $c->stato_pulizia !== 'manutenzione' && ! $assegnazioni->has($c->id))->count(),
        ];

        return view('housekeeping.index', [
            'data'         => $data,
            'camere'       => $camere->groupBy('piano'),
            'sit'          => $sit,
            'assegnazioni' => $assegnazioni,
            'personale'    => $personale,
            'riepilogo'    => $riepilogo,
            'puoAssegnare' => $this->puoAssegnare(),
        ]);
    }

    /** Assegna (o toglie) una singola camera. */
    public function assegna(Request $request)
    {
        $this->soloAssegnatori();

        $dati = $request->validate([
            'data'      => 'required|date_format:Y-m-d',
            'camera_id' => 'required|integer|exists:camere,id',
            'utente_id' => 'nullable|integer',
        ]);

        $utenteId = $this->utenteValido($dati['utente_id'] ?? null);

        if ($utenteId === null) {
            DB::table('hk_assegnazioni')->where('data', $dati['data'])->where('camera_id', $dati['camera_id'])->delete();
        } else {
            $this->scrivi($dati['data'], (int) $dati['camera_id'], $utenteId);
        }

        return redirect()->route('housekeeping.index', ['data' => $dati['data']]);
    }

    /** Assegna (o toglie) tutte le camere di un piano. */
    public function assegnaPiano(Request $request)
    {
        $this->soloAssegnatori();

        $dati = $request->validate([
            'data'      => 'required|date_format:Y-m-d',
            'piano'     => 'required|integer|min:0',
            'utente_id' => 'nullable|integer',
        ]);

        $utenteId = $this->utenteValido($dati['utente_id'] ?? null);
        $ids = Camera::where('attiva', true)->where('piano', $dati['piano'])->pluck('id');

        if ($utenteId === null) {
            DB::table('hk_assegnazioni')->where('data', $dati['data'])->whereIn('camera_id', $ids)->delete();
        } else {
            foreach ($ids as $id) {
                $this->scrivi($dati['data'], (int) $id, $utenteId);
            }
        }

        return redirect()->route('housekeeping.index', ['data' => $dati['data']])
            ->with('ok', $utenteId === null ? 'Assegnazioni del piano tolte.' : 'Piano assegnato.');
    }

    /** Copia le assegnazioni del giorno prima (solo per le camere ancora senza nessuno). */
    public function copiaDaIeri(Request $request)
    {
        $this->soloAssegnatori();

        $dati = $request->validate(['data' => 'required|date_format:Y-m-d']);
        $giorno = Carbon::createFromFormat('Y-m-d', $dati['data'])->startOfDay();

        $validi = $this->personale()->pluck('id')->all();
        $gia = DB::table('hk_assegnazioni')->where('data', $giorno->toDateString())->pluck('camera_id')->all();

        $copiate = 0;
        $righe = DB::table('hk_assegnazioni')->where('data', $giorno->copy()->subDay()->toDateString())->get();
        foreach ($righe as $r) {
            if (in_array($r->camera_id, $gia, true) || ! in_array($r->utente_id, $validi, true)) {
                continue;
            }
            $this->scrivi($giorno->toDateString(), (int) $r->camera_id, (int) $r->utente_id);
            $copiate++;
        }

        return redirect()->route('housekeeping.index', ['data' => $dati['data']])
            ->with('ok', $copiate > 0 ? "Copiate {$copiate} assegnazioni dal giorno prima." : 'Niente da copiare dal giorno prima.');
    }

    /* ====================== utilità ====================== */

    private function dataRichiesta(Request $request): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', (string) $request->query('data'))->startOfDay();
        } catch (\Throwable $e) {
            return Carbon::today();
        }
    }

    /** Chi può essere incaricato delle pulizie: profili con modulo Housekeeping e funzione "Stato camere", non gestori. */
    private function personale(): Collection
    {
        $chiavi = Ruolo::tutti()->filter(fn ($r) => in_array('housekeeping', $r->moduli ?? [], true)
                && in_array('stato_camere', $r->funzioni ?? [], true)
                && ! $r->gestore)
            ->keys()->all();

        $modello = get_class(auth()->user());

        return $modello::where('attivo', true)->whereIn('ruolo', $chiavi)
            ->orderBy('cognome')->orderBy('nome')->get();
    }

    private function utenteValido($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }
        abort_unless($this->personale()->contains('id', (int) $id), 422, 'Operatore non valido.');

        return (int) $id;
    }

    private function scrivi(string $data, int $cameraId, int $utenteId): void
    {
        DB::table('hk_assegnazioni')->updateOrInsert(
            ['data' => $data, 'camera_id' => $cameraId],
            ['utente_id' => $utenteId, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function puoAssegnare(): bool
    {
        $u = auth()->user();

        return $u->isGestore() || $u->puo('assegna_pulizie');
    }

    private function soloAssegnatori(): void
    {
        abort_unless($this->puoAssegnare(), 403);
    }

    private function soloPersonale(): void
    {
        $u = auth()->user();
        abort_unless($u->isGestore() || $u->puo('stato_camere') || $u->puo('assegna_pulizie'), 403);
    }
}
