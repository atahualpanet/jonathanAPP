<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Prenotazione;
use App\Models\TipologiaCamera;
use App\Support\MagazzinoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MinibarController extends Controller
{
    private const CATEGORIE = ['bevande', 'alcolici', 'snack', 'altro'];

    /** Listino di partenza: [nome, categoria, prezzo, aliquota IVA] */
    private const LISTINO_BASE = [
        ['Acqua naturale 50 cl',   'bevande',  2.00, 22],
        ['Acqua frizzante 50 cl',  'bevande',  2.00, 22],
        ['Coca-Cola 33 cl',        'bevande',  3.50, 22],
        ['Aranciata 33 cl',        'bevande',  3.50, 22],
        ['Succo di frutta',        'bevande',  3.50, 22],
        ['Birra 33 cl',            'alcolici', 4.50, 22],
        ['Vino bianco 20 cl',      'alcolici', 8.00, 22],
        ['Vino rosso 20 cl',       'alcolici', 8.00, 22],
        ['Prosecco 20 cl',         'alcolici', 9.00, 22],
        ['Patatine',               'snack',    3.00, 22],
        ['Arachidi',               'snack',    3.00, 22],
        ['Barretta al cioccolato', 'snack',    2.50, 22],
        ['Biscotti',               'snack',    2.50, 22],
    ];

    // ======================================================================
    // LISTINO E DOTAZIONI
    // ======================================================================

    public function listino()
    {
        $articoli = DB::table('minibar_articoli')->orderByDesc('attivo')->orderBy('categoria')->orderBy('nome')->get();
        $tipologie = TipologiaCamera::where('attiva', true)->orderBy('nome')->get();

        // dotazione[tipologia_id][articolo_id] = quantità
        $dotazione = [];
        foreach (DB::table('minibar_dotazioni')->get() as $d) {
            $dotazione[$d->tipologia_camera_id][$d->articolo_id] = $d->quantita;
        }

        return view('minibar.listino', ['articoli' => $articoli, 'tipologie' => $tipologie, 'dotazione' => $dotazione, 'categorie' => self::CATEGORIE,
            'magazzino' => DB::table('articoli_magazzino')->where('attivo', true)->orderBy('nome')->get()]);
    }

    /** Carica gli articoli tipici di un minibar (solo se il listino è ancora vuoto). */
    public function caricaListinoBase()
    {
        if (DB::table('minibar_articoli')->exists()) {
            return back()->withErrors(['minibar' => 'Il listino non è vuoto: il listino base si carica solo su un listino nuovo.']);
        }

        foreach (self::LISTINO_BASE as [$nome, $categoria, $prezzo, $iva]) {
            DB::table('minibar_articoli')->insert([
                'nome' => $nome, 'categoria' => $categoria, 'prezzo' => $prezzo, 'aliquota_iva' => $iva,
                'attivo' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return back()->with('ok', 'Listino base caricato: controlla prezzi e aliquote.');
    }

    private function validaArticolo(Request $request): array
    {
        return $request->validate([
            'nome'         => 'required|string|max:100',
            'categoria'    => 'required|in:' . implode(',', self::CATEGORIE),
            'prezzo'       => 'required|numeric|min:0|max:9999',
            'aliquota_iva' => 'required|numeric|min:0|max:100',
            'magazzino_articolo_id' => 'nullable|integer|exists:articoli_magazzino,id',
        ]);
    }

    public function articoloCrea(Request $request)
    {
        $dati = $this->validaArticolo($request);

        DB::table('minibar_articoli')->insert($dati + ['attivo' => true, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('ok', 'Articolo aggiunto al listino.');
    }

    public function articoloAggiorna(Request $request, int $articolo)
    {
        $dati = $this->validaArticolo($request);
        $dati['attivo'] = $request->boolean('attivo');

        $n = DB::table('minibar_articoli')->where('id', $articolo)->update($dati + ['updated_at' => now()]);
        abort_unless($n || DB::table('minibar_articoli')->where('id', $articolo)->exists(), 404);

        return back()->with('ok', 'Articolo aggiornato. I conti già addebitati non cambiano.');
    }

    /** Dotazione standard di una tipologia: quanti pezzi di ogni articolo ci devono essere in camera. */
    public function dotazioneSalva(Request $request, int $tipologia)
    {
        TipologiaCamera::findOrFail($tipologia);

        $dati = $request->validate([
            'quantita'   => 'nullable|array',
            'quantita.*' => 'nullable|integer|min:0|max:20',
        ]);
        $attivi = DB::table('minibar_articoli')->where('attivo', true)->pluck('id')->all();

        DB::transaction(function () use ($tipologia, $dati, $attivi) {
            foreach ($attivi as $articoloId) {
                $q = (int) ($dati['quantita'][$articoloId] ?? 0);
                if ($q > 0) {
                    DB::table('minibar_dotazioni')->updateOrInsert(
                        ['tipologia_camera_id' => $tipologia, 'articolo_id' => $articoloId],
                        ['quantita' => $q, 'updated_at' => now(), 'created_at' => now()]
                    );
                } else {
                    DB::table('minibar_dotazioni')->where('tipologia_camera_id', $tipologia)->where('articolo_id', $articoloId)->delete();
                }
            }
        });

        return back()->with('ok', 'Dotazione salvata.');
    }

    // ======================================================================
    // CONTROLLO DELLA GOVERNANTE
    // ======================================================================

    /**
     * Prenotazione a cui addebitare il minibar di una camera: l'ospite in casa; se la camera
     * è appena stata lasciata, l'ultima prenotazione partita (così si può controllare dopo il check-out).
     */
    private function prenotazioneDellaCamera(Camera $camera): ?Prenotazione
    {
        $base = fn () => Prenotazione::whereHas('camere', fn ($q) => $q->where('camere.id', $camera->id));

        return $base()->where('stato', 'in_casa')->orderByDesc('data_arrivo')->first()
            ?? $base()->where('stato', 'partita')
                ->where('data_partenza', '>=', now()->subDays(2)->toDateString())
                ->orderByDesc('uscita_il')->orderByDesc('data_partenza')->first();
    }

    public function controllo(Camera $camera)
    {
        $camera->load('tipologia');
        $prenotazione = $this->prenotazioneDellaCamera($camera);
        $prenotazione?->load('ospite');

        $conto = $prenotazione ? app(FrontDeskController::class)->contoDi($prenotazione) : null;

        $dotazione = DB::table('minibar_dotazioni')->where('tipologia_camera_id', $camera->tipologia_camera_id)->pluck('quantita', 'articolo_id');
        $articoli = DB::table('minibar_articoli')->where('attivo', true)->orderBy('categoria')->orderBy('nome')->get();

        // Prima gli articoli della dotazione standard, poi gli altri (ospite che ha portato roba o dotazione non impostata)
        $inDotazione = $articoli->filter(fn ($a) => $dotazione->has($a->id))->values();
        $altri = $articoli->reject(fn ($a) => $dotazione->has($a->id))->values();

        $ultimi = DB::table('minibar_controlli')->where('camera_id', $camera->id)
            ->when($prenotazione, fn ($q) => $q->where('prenotazione_id', $prenotazione->id))
            ->orderByDesc('id')->limit(5)->get();

        return view('minibar.controllo', [
            'camera' => $camera, 'prenotazione' => $prenotazione, 'conto' => $conto,
            'dotazione' => $dotazione, 'inDotazione' => $inDotazione, 'altri' => $altri,
            'ultimi' => $ultimi, 'token' => bin2hex(random_bytes(16)),
        ]);
    }

    public function controlloSalva(Request $request, Camera $camera)
    {
        $dati = $request->validate([
            'token'          => 'required|alpha_num|size:32',
            'controllato_da' => 'nullable|string|max:100',
            'mancanti'       => 'nullable|array',
            'mancanti.*'     => 'nullable|integer|min:0|max:20',
        ]);

        $prenotazione = $this->prenotazioneDellaCamera($camera);
        if (! $prenotazione) {
            return back()->withErrors(['minibar' => 'Nessun ospite in casa (o partito da poco) in questa camera: non c\'è un conto a cui addebitare.']);
        }

        if (DB::table('minibar_controlli')->where('token', $dati['token'])->exists()) {
            return redirect()->route('camere.minibar', $camera)->with('ok', 'Controllo già registrato: nessun doppio addebito.');
        }

        // Solo gli articoli con quantità > 0, con prezzo e IVA presi dal listino di adesso
        $richieste = collect($dati['mancanti'] ?? [])->map(fn ($q) => (int) $q)->filter(fn ($q) => $q > 0);
        $articoli = DB::table('minibar_articoli')->whereIn('id', $richieste->keys())->where('attivo', true)->get()->keyBy('id');
        $righe = $richieste->filter(fn ($q, $id) => $articoli->has($id));

        $fd = app(FrontDeskController::class);
        $conto = $fd->contoDi($prenotazione);

        if ($conto && $conto->stato !== 'aperto' && $righe->isNotEmpty()) {
            return back()->withInput()->withErrors(['minibar' => 'Il conto di questa prenotazione è chiuso: avvisa la reception, che addebiterà il minibar a mano.']);
        }
        if ($conto && $righe->isNotEmpty() && $fd->folioBloccato($conto->id, 'B')) {
            return back()->withInput()->withErrors(['minibar' => 'Il folio B (Extra) ha già una fattura emessa: avvisa la reception.']);
        }

        $addebitato = 0.0;
        DB::transaction(function () use ($dati, $camera, $prenotazione, $righe, $articoli, $fd, &$conto, &$addebitato) {
            $controlloId = DB::table('minibar_controlli')->insertGetId([
                'token' => $dati['token'], 'camera_id' => $camera->id, 'prenotazione_id' => $prenotazione->id,
                'controllato_da' => $dati['controllato_da'] ?? null, 'totale' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Magazzino: i pezzi mancanti scaricano gli articoli collegati nel listino (nessun effetto se non collegati)
            MagazzinoService::scaricaMinibar($controlloId, $righe->all(), auth()->id());

            $totale = 0.0;
            if ($righe->isNotEmpty()) {
                $contoId = $conto->id ?? $fd->apriContoPer($prenotazione);

                foreach ($righe as $articoloId => $q) {
                    $a = $articoli[$articoloId];
                    $importo = round($q * (float) $a->prezzo, 2);
                    $totale += $importo;

                    $voceId = DB::table('voci_conto')->insertGetId([
                        'conto_id'      => $contoId,
                        'folio'         => 'B',
                        'tipo'          => 'servizio',
                        'descrizione'   => 'Minibar · ' . $q . ' × ' . $a->nome . ' · camera ' . $camera->numero,
                        'importo'       => $importo,
                        'riferimento'   => 'minibar:' . $controlloId . ':' . (float) $a->aliquota_iva,
                        'registrata_il' => now(),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    DB::table('minibar_controllo_righe')->insert([
                        'controllo_id' => $controlloId, 'articolo_id' => $a->id, 'nome' => $a->nome,
                        'prezzo' => $a->prezzo, 'quantita' => $q, 'voce_conto_id' => $voceId,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }

                $fd->aggiornaSaldoConto($contoId);
            }

            DB::table('minibar_controlli')->where('id', $controlloId)->update(['totale' => round($totale, 2)]);
            $addebitato = round($totale, 2);
        });

        $msg = $righe->isEmpty()
            ? 'Controllo registrato: minibar completo, niente da addebitare.'
            : 'Controllo registrato: addebitati € ' . number_format($addebitato, 2, ',', '.') . ' al conto della camera ' . $camera->numero . '.';

        return redirect()->route('camere.minibar', $camera)->with('ok', $msg);
    }
}
