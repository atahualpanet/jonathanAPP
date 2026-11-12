<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Support\Pulizie;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Housekeeping mobile: la singola camera (inizia, checklist, nota, segna come pulita). */
class HousekeepingPulizieController extends Controller
{
    public function mostra(Camera $camera)
    {
        [$oggi, $sit, $pulizia] = $this->carica($camera);

        $aperta = $pulizia && ! $pulizia->finita_il;
        $voci = $pulizia ? (json_decode($pulizia->voci ?? '[]', true) ?: []) : [];

        return view('housekeeping.camera', [
            'camera'    => $camera,
            'sit'       => $sit,
            'pulizia'   => $pulizia,
            'aperta'    => $aperta,
            'voci'      => $voci,
            'checklist' => config('housekeeping.checklist'),
            'obbligatoria' => (bool) config('housekeeping.checklist_obbligatoria'),
            'fuoriServizio' => $camera->stato_pulizia === 'manutenzione',
        ]);
    }

    public function inizia(Camera $camera)
    {
        [$oggi, $sit, $pulizia] = $this->carica($camera);

        if ($camera->stato_pulizia === 'manutenzione') {
            return back()->withErrors(['camera' => 'La camera è fuori servizio.']);
        }
        if ($pulizia && ! $pulizia->finita_il) {
            return redirect()->route('hk.camera', $camera); // già iniziata
        }
        if (! $sit->da_pulire) {
            return back()->withErrors(['camera' => 'La camera risulta già pulita.']);
        }

        DB::transaction(function () use ($camera, $oggi) {
            DB::table('hk_pulizie')->insert([
                'camera_id'   => $camera->id,
                'utente_id'   => auth()->id(),
                'data'        => $oggi->toDateString(),
                'iniziata_il' => now(),
                'voci'        => json_encode([]),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $camera->cambiaStato('in_pulizia', auth()->id(), 'Pulizia iniziata');
        });

        return redirect()->route('hk.camera', $camera);
    }

    /** Spunta / toglie una voce della checklist (chiamata da JavaScript). */
    public function voce(Request $request, Camera $camera)
    {
        [, , $pulizia] = $this->carica($camera);

        $checklist = config('housekeeping.checklist');
        $dati = $request->validate([
            'voce'    => 'required|string|in:' . implode(',', array_keys($checklist)),
            'spuntata' => 'required|boolean',
        ]);

        if (! $pulizia || $pulizia->finita_il) {
            return response()->json(['ok' => false, 'errore' => 'Pulizia non iniziata.'], 409);
        }

        $voci = json_decode($pulizia->voci ?? '[]', true) ?: [];
        $voci = array_values(array_diff($voci, [$dati['voce']]));
        if ($dati['spuntata']) {
            $voci[] = $dati['voce'];
        }

        DB::table('hk_pulizie')->where('id', $pulizia->id)->update(['voci' => json_encode($voci), 'updated_at' => now()]);

        $fatte = count(array_intersect(array_keys($checklist), $voci));

        return response()->json(['ok' => true, 'fatte' => $fatte, 'totale' => count($checklist)]);
    }

    public function nota(Request $request, Camera $camera)
    {
        [, , $pulizia] = $this->carica($camera);

        $dati = $request->validate(['nota' => 'nullable|string|max:500']);

        if (! $pulizia || $pulizia->finita_il) {
            return response()->json(['ok' => false], 409);
        }

        DB::table('hk_pulizie')->where('id', $pulizia->id)->update([
            'nota' => trim((string) ($dati['nota'] ?? '')) ?: null,
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function completa(Camera $camera)
    {
        [, , $pulizia] = $this->carica($camera);

        if (! $pulizia || $pulizia->finita_il) {
            return redirect()->route('hk.camera', $camera)->withErrors(['camera' => 'Nessuna pulizia in corso per questa camera.']);
        }

        $checklist = array_keys(config('housekeeping.checklist'));
        $voci = json_decode($pulizia->voci ?? '[]', true) ?: [];
        if (config('housekeeping.checklist_obbligatoria') && count(array_intersect($checklist, $voci)) < count($checklist)) {
            return redirect()->route('hk.camera', $camera)->withErrors(['camera' => 'Spunta tutte le attività prima di segnare la camera come pulita.']);
        }

        DB::transaction(function () use ($camera, $pulizia) {
            DB::table('hk_pulizie')->where('id', $pulizia->id)->update(['finita_il' => now(), 'updated_at' => now()]);
            $camera->cambiaStato('pulita', auth()->id(), 'Pulizia completata');
        });

        return redirect()->route('hk.completata', $camera);
    }

    public function completata(Camera $camera)
    {
        [$oggi, , ] = $this->carica($camera);

        $pulizia = DB::table('hk_pulizie')->where('camera_id', $camera->id)->whereDate('data', $oggi)
            ->whereNotNull('finita_il')->orderByDesc('id')->first();

        if (! $pulizia) {
            return redirect()->route('hk.camera', $camera);
        }

        $durata = max(1, (int) round(abs(Carbon::parse($pulizia->iniziata_il)->diffInMinutes(Carbon::parse($pulizia->finita_il)))));

        return view('housekeeping.completata', [
            'camera'    => $camera,
            'durata'    => $durata,
            'attivita'  => count(json_decode($pulizia->voci ?? '[]', true) ?: []),
            'prossima'  => $this->prossimaCamera($camera, $oggi),
        ]);
    }

    /* ---------------- utilità ---------------- */

    /** Controlla che l'utente possa lavorare su questa camera e carica la situazione di oggi. */
    private function carica(Camera $camera): array
    {
        $u = auth()->user();
        abort_unless($u->isGestore() || $u->puo('stato_camere'), 403);

        $oggi = Carbon::today();

        if (! $u->isGestore()) {
            $assegnata = DB::table('hk_assegnazioni')->where('data', $oggi->toDateString())
                ->where('camera_id', $camera->id)->where('utente_id', $u->id)->exists();
            abort_unless($assegnata, 403);
        }

        $camera->loadMissing('tipologia');
        $sit = Pulizie::situazione($oggi, collect([$camera]))[$camera->id];

        $pulizia = DB::table('hk_pulizie')->where('camera_id', $camera->id)->whereDate('data', $oggi)
            ->orderByDesc('id')->first();

        return [$oggi, $sit, $pulizia];
    }

    /** La prossima camera da pulire tra quelle assegnate all'utente, dopo quella appena finita. */
    private function prossimaCamera(Camera $corrente, Carbon $oggi): ?Camera
    {
        $ids = DB::table('hk_assegnazioni')->where('data', $oggi->toDateString())
            ->where('utente_id', auth()->id())->pluck('camera_id');

        $lista = Camera::with('tipologia')->whereIn('id', $ids)->where('attiva', true)
            ->where('stato_pulizia', '!=', 'manutenzione')
            ->orderBy('piano')->orderBy('numero')->get()->values();

        if ($lista->isEmpty()) {
            return null;
        }

        $sit = Pulizie::situazione($oggi, $lista);
        $pos = $lista->search(fn ($c) => $c->id === $corrente->id);
        $ordine = $pos === false ? $lista : $lista->slice($pos + 1)->merge($lista->slice(0, $pos));

        return $ordine->first(fn ($c) => $c->id !== $corrente->id && $sit[$c->id]->da_pulire);
    }
}
