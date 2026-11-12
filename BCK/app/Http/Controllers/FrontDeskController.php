<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Camera;
use App\Models\InvioAlloggiati;
use App\Support\SchedinePdf;
use App\Support\SchedineTxt;
use App\Models\Prenotazione;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Ospite;
use App\Models\TipologiaCamera;
use Illuminate\Support\Facades\Storage;

class FrontDeskController extends Controller
{
    /** Stati che non occupano la camera e non vanno mostrati nel tableau */
    private const STATI_ESCLUSI = ["annullata", "no_show"];

    /** Giorni per settimana mostrati nel tableau */
    private const GIORNI_PER_SETTIMANA = 7;

    public function prenotazioni(Request $request)
    {
        // Data di inizio: oggi oppure ?data=YYYY-MM-DD (se non valida, oggi)
        try {
            $dataInizio = $request->filled("data")
                ? Carbon::parse($request->query("data"))->startOfDay()
                : Carbon::today();
        } catch (\Throwable $e) {
            $dataInizio = Carbon::today();
        }

        // Numero di settimane visibili: 1, 2 o 3 (default 1)
        $settimane = (int) $request->query('settimane', 1);
        $settimane = in_array($settimane, [1, 2, 3], true) ? $settimane : 1;
        $numGiorni = $settimane * self::GIORNI_PER_SETTIMANA;

        $periodoGiorni = [];
        for ($i = 0; $i < $numGiorni; $i++) {
            $periodoGiorni[] = $dataInizio->copy()->addDays($i);
        }
        $fineVista = $dataInizio->copy()->addDays($numGiorni);

        // Camere attive raggruppate per piano
        $camerePerPiano = Camera::with("tipologia")
            ->where("attiva", true)
            ->orderBy("piano")
            ->orderBy("numero")
            ->get()
            ->groupBy("piano");

        // Prenotazioni con almeno una assegnazione camera che tocca il periodo
        // (le date per camera stanno nella pivot prenotazione_camera)
        $prenotazioni = Prenotazione::with(["ospite", "camere"])
            ->whereNotIn("stato", self::STATI_ESCLUSI)
            ->whereHas("camere", function ($q) use ($dataInizio, $fineVista) {
                $q->where("prenotazione_camera.data_arrivo", "<", $fineVista->toDateString())
                  ->where("prenotazione_camera.data_partenza", ">=", $dataInizio->toDateString());
            })
            ->get();

        // Indice per camera: [camera_id => [ [p, dal, al], ... ]]
        $assegnazioni = [];
        foreach ($prenotazioni as $p) {
            foreach ($p->camere as $cam) {
                $assegnazioni[$cam->id][] = [
                    "p"   => $p,
                    "dal" => Carbon::parse($cam->pivot->data_arrivo)->startOfDay(),
                    "al"  => Carbon::parse($cam->pivot->data_partenza)->startOfDay(),
                ];
            }
        }

        // Segmenti per riga (vuota / prenotazione / lavori) con colspan già calcolato
        $righe = [];
        foreach ($camerePerPiano as $camere) {
            foreach ($camere as $camera) {
                $righe[$camera->id] = $this->costruisciRiga($camera, $periodoGiorni, $assegnazioni[$camera->id] ?? []);
            }
        }

        // KPI di oggi
        $oggi = Carbon::today()->toDateString();
        $arriviOggi = Prenotazione::whereDate("data_arrivo", $oggi)
            ->whereNotIn("stato", self::STATI_ESCLUSI)->count();
        $partenzeOggi = Prenotazione::whereDate("data_partenza", $oggi)
            ->whereNotIn("stato", self::STATI_ESCLUSI)->count();
        $inCasaCount = Prenotazione::where("stato", "in_casa")
            ->withCount("camere")->get()->sum("camere_count");

        return view("frontdesk.prenotazioni", compact(
            "camerePerPiano",
            "periodoGiorni",
            "righe",
            "dataInizio",
            "arriviOggi",
            "partenzeOggi",
            "inCasaCount",
            "settimane"
        ));
    }

    /**
     * Trasforma i giorni di una camera in segmenti con colspan:
     * una prenotazione su più giorni diventa UNA barra continua.
     */
    private function costruisciRiga(Camera $camera, array $giorni, array $assegnazioni): array
    {
        $totale = count($giorni);
        $metaTotali = $totale * 2;
        $primo = $giorni[0];
        $inManutenzione = $camera->stato_pulizia === "manutenzione";

        // Ogni giorno è diviso in due metà: indice 2g = mattina (si libera chi parte),
        // indice 2g+1 = pomeriggio (entra chi arriva). Una prenotazione occupa dal
        // pomeriggio dell'arrivo alla mattina della partenza: così partenza e arrivo
        // dello stesso giorno stanno sulla stessa riga, uno dopo l'altro.
        $intervalli = [];
        foreach ($assegnazioni as $a) {
            $intervalli[] = [
                "a"    => $a,
                "da"   => 2 * (int) $primo->diffInDays($a["dal"], false) + 1,
                "fine" => 2 * (int) $primo->diffInDays($a["al"], false) + 1,
            ];
        }

        $trova = function (int $h) use ($intervalli) {
            foreach ($intervalli as $iv) {
                if ($iv["da"] <= $h && $iv["fine"] > $h) {
                    return $iv;
                }
            }
            return null;
        };

        $segmenti = [];
        $h = 0;
        while ($h < $metaTotali) {
            $iv = $trova($h);

            if ($iv) {
                $span = min($iv["fine"], $metaTotali) - $h;
                $segmenti[] = [
                    "tipo"        => "prenotazione",
                    "span"        => $span,
                    "p"           => $iv["a"]["p"],
                    "camera"      => $camera->id,
                    "dal"         => $iv["a"]["dal"]->toDateString(), // date REALI dell'assegnazione (anche se fuori vista)
                    "al"          => $iv["a"]["al"]->toDateString(),
                    "da_sinistra" => $iv["a"]["dal"]->lt($primo),     // inizia prima della vista
                    "a_destra"    => $iv["fine"] > $metaTotali,       // prosegue dopo la vista
                ];
                $h += $span;
                continue;
            }

            if ($inManutenzione) {
                // Provvisorio: finché non c è il modulo Manutenzione, camera in "manutenzione" = Lavori sulle metà libere
                $span = 1;
                while ($h + $span < $metaTotali && !$trova($h + $span)) {
                    $span++;
                }
                $segmenti[] = ["tipo" => "lavori", "span" => $span];
                $h += $span;
                continue;
            }

            $giorno = $giorni[intdiv($h, 2)];
            if ($h % 2 === 0 && $h + 1 < $metaTotali && !$trova($h + 1)) {
                $segmenti[] = ["tipo" => "vuota", "span" => 2, "giorno" => $giorno, "meta" => "giorno"];
                $h += 2;
            } else {
                $segmenti[] = ["tipo" => "vuota", "span" => 1, "giorno" => $giorno, "meta" => $h % 2 === 0 ? "am" : "pm"];
                $h++;
            }
        }

        return $segmenti;
    }


    /**
     * Regole e scrittura dello spostamento di UNA assegnazione camera (camera e/o date).
     * Le usano il drag & drop del tableau e il modulo "Modifica prenotazione": le regole sono le stesse.
     * Ritorna il messaggio d'errore, oppure null se lo spostamento è stato scritto.
     *
     * $dati: camera_origine, camera_id, data_arrivo (Y-m-d), data_partenza (Y-m-d)
     */
    private function applicaSpostamento(Prenotazione $prenotazione, array $dati): ?string
    {
        if (! $prenotazione->modificabile()) {
            return 'Questa prenotazione non è più modificabile.';
        }

        // Assegnazione camera che si sta modificando
        $pivot = DB::table('prenotazione_camera')
            ->where('prenotazione_id', $prenotazione->id)
            ->where('camera_id', $dati['camera_origine'])
            ->first();
        if (! $pivot) {
            return 'Assegnazione camera non trovata: ricarica la pagina.';
        }

        $arrivoCambiato = $dati['data_arrivo'] !== substr((string) $pivot->data_arrivo, 0, 10);
        $cameraCambiata = (int) $dati['camera_id'] !== (int) $dati['camera_origine'];

        // Ospite già in casa: non si può retrodatare l'arrivo (è già arrivato),
        // ma camera e partenza si possono cambiare (cambio camera durante il soggiorno).
        if ($prenotazione->stato === 'in_casa' && $arrivoCambiato) {
            return 'Ospite già in casa: non puoi modificare la data di arrivo.';
        }

        if ($arrivoCambiato && $dati['data_arrivo'] < Carbon::today()->toDateString()) {
            return 'Non puoi spostare l\'arrivo nel passato.';
        }

        // Camera di destinazione
        $camera = Camera::where('id', $dati['camera_id'])->where('attiva', true)->first();
        if (! $camera) {
            return 'Camera non disponibile.';
        }
        if ($cameraCambiata && $camera->stato_pulizia === 'manutenzione') {
            return 'La camera ' . $camera->numero . ' è in lavori.';
        }
        if ($cameraCambiata && DB::table('prenotazione_camera')
                ->where('prenotazione_id', $prenotazione->id)
                ->where('camera_id', $camera->id)->exists()) {
            return 'Questa prenotazione ha già la camera ' . $camera->numero . '.';
        }

        // Sovrapposizioni con altre prenotazioni nella camera di destinazione
        $conflitto = DB::table('prenotazione_camera as pc')
            ->join('prenotazioni as p', 'p.id', '=', 'pc.prenotazione_id')
            ->join('ospiti as o', 'o.id', '=', 'p.ospite_id')
            ->whereNull('p.deleted_at')
            ->whereNotIn('p.stato', self::STATI_ESCLUSI)
            ->where('pc.camera_id', $camera->id)
            ->where('pc.data_arrivo', '<', $dati['data_partenza'])
            ->where('pc.data_partenza', '>', $dati['data_arrivo'])
            ->whereNot(function ($q) use ($prenotazione, $dati) {
                $q->where('pc.prenotazione_id', $prenotazione->id)
                  ->where('pc.camera_id', $dati['camera_origine']);
            })
            ->select('o.cognome')
            ->first();
        if ($conflitto) {
            return 'Camera ' . $camera->numero . ' occupata da ' . $conflitto->cognome . ' in quelle date.';
        }

        DB::transaction(function () use ($prenotazione, $dati, $camera) {
            DB::table('prenotazione_camera')
                ->where('prenotazione_id', $prenotazione->id)
                ->where('camera_id', $dati['camera_origine'])
                ->update([
                    'camera_id'     => $camera->id,
                    'data_arrivo'   => $dati['data_arrivo'],
                    'data_partenza' => $dati['data_partenza'],
                    'updated_at'    => now(),
                ]);

            // Le date della prenotazione = estremi di tutte le sue assegnazioni camera
            $limiti = DB::table('prenotazione_camera')
                ->where('prenotazione_id', $prenotazione->id)
                ->selectRaw('MIN(data_arrivo) as a, MAX(data_partenza) as p')
                ->first();

            $prenotazione->update([
                'data_arrivo'   => $limiti->a,
                'data_partenza' => $limiti->p,
            ]);
        });

        return null;
    }

    /**
     * Drag & drop: sposta / allunga / accorcia una prenotazione sul tableau.
     * POST /prenotazioni/{prenotazione}/sposta  (JSON)
     */
    public function sposta(Request $request, Prenotazione $prenotazione)
    {
        $dati = $request->validate([
            'camera_origine' => 'required|integer|exists:camere,id',
            'camera_id'      => 'required|integer|exists:camere,id',
            'data_arrivo'    => 'required|date',
            'data_partenza'  => 'required|date|after:data_arrivo',
        ]);

        $errore = $this->applicaSpostamento($prenotazione, $dati);
        if ($errore) {
            return response()->json(['message' => $errore], 422);
        }

        // Se il conto è già aperto, camere e tassa del conto seguono il nuovo soggiorno
        $avviso = $this->riallineaConto($prenotazione);

        return response()->json(['ok' => true] + ($avviso ? ['avviso' => $avviso] : []));
    }


    /**
     * Scheda prenotazione: dati ospite, camere assegnate, importi e saldo.
     * Si apre cliccando una barra del tableau. GET /prenotazioni/{prenotazione}
     */
    public function scheda(Prenotazione $prenotazione)
    {
        $prenotazione->load('ospite', 'accompagnatori', 'camere.tipologia');
        $persone = $prenotazione->persone();

        // Notti e importo soggiorno calcolati dalle assegnazioni camera (pivot),
        // così tornano giusti anche dopo un cambio camera o un allungamento/accorciamento.
        $camere = $prenotazione->camere->map(function ($camera) {
            $dal = Carbon::parse($camera->pivot->data_arrivo);
            $al  = Carbon::parse($camera->pivot->data_partenza);
            $notti = (int) $dal->diffInDays($al);
            return (object) [
                'camera'    => $camera,
                'dal'       => $dal,
                'al'        => $al,
                'notti'     => $notti,
                'tariffa'   => (float) $camera->pivot->tariffa,
                'subtotale' => $notti * (float) $camera->pivot->tariffa,
            ];
        });

        $importoSoggiorno = $camere->sum('subtotale');
        $notti = (int) $prenotazione->data_arrivo->diffInDays($prenotazione->data_partenza);
        $tassa = $this->calcolaTassa((int) $prenotazione->adulti, $notti);

        // I totali si ricalcolano SEMPRE dalle assegnazioni camera (e dal conto, se aperto):
        // prenotazioni.importo_totale può essere 0 (es. dati demo) e non è una fonte affidabile.
        $conto = $this->contoDi($prenotazione);
        if ($conto) {
            $voci = DB::table('voci_conto')->where('conto_id', $conto->id)->get();
            // Rimborso e trattenuta della caparra (annullamento / no-show) si mostrano a parte, non come spese
            if ($prenotazione->caparra_gestita_il) {
                $voci = $voci->reject(fn ($v) => in_array($v->origine, ['caparra_rimborso', 'caparra_trattenuta'], true))
                    ->reject(fn ($v) => $v->tipo === 'caparra')->values();
            }
            $isCredito = fn ($v) => in_array($v->tipo, self::TIPI_CREDITO, true);
            $totale  = round($voci->reject($isCredito)->sum('importo'), 2);
            $caparra = round($voci->where('tipo', 'caparra')->sum('importo'), 2);
            $pagato  = round($voci->where('tipo', 'pagamento')->sum('importo'), 2);
            $extra   = max(0, round($totale - $importoSoggiorno - $tassa, 2));
        } else {
            $totale  = round($importoSoggiorno + $tassa, 2);
            $caparra = $prenotazione->caparra_gestita_il ? 0.0 : (float) $prenotazione->importo_caparra;
            $pagato  = 0.0;
            $extra   = 0.0;
        }
        $saldo = round($totale - $caparra - $pagato, 2);

        return view('frontdesk.scheda', compact('prenotazione', 'camere', 'importoSoggiorno', 'tassa', 'extra', 'totale', 'caparra', 'pagato', 'conto', 'saldo', 'persone'));
    }


    // ======================================================================
    // CHECK-IN: una sola pagina e un solo invio (checkin / checkinCompleta, più in fondo)
    // ======================================================================


    // ======================================================================
    // OSPITI DELLA PRENOTAZIONE (titolare + accompagnatori + bambini)
    // ======================================================================

    /** Dove tornare dopo un salvataggio: check-in oppure scheda prenotazione. */
    private function tornaA(Request $request, Prenotazione $prenotazione)
    {
        $rotta = $request->input('torna') === 'checkin' ? 'prenotazioni.checkin' : 'prenotazioni.scheda';
        return redirect()->route($rotta, $prenotazione);
    }

    /** L'ospite deve far parte di QUESTA prenotazione (titolare o accompagnatore), altrimenti 404. */
    private function ospiteDellaPrenotazione(Prenotazione $prenotazione, Ospite $ospite): Ospite
    {
        $appartiene = $prenotazione->ospite_id === $ospite->id
            || $prenotazione->accompagnatori()->where('ospiti.id', $ospite->id)->exists();
        abort_unless($appartiene, 404);

        return $ospite;
    }

    public function ospiteModifica(Prenotazione $prenotazione, Ospite $ospite)
    {
        $this->ospiteDellaPrenotazione($prenotazione, $ospite);
        $prenotazione->load('ospite', 'accompagnatori');

        $persona = $prenotazione->persone()->first(fn ($p) => $p->ospite->id === $ospite->id);

        return view('frontdesk.ospite', ['prenotazione' => $prenotazione, 'o' => $ospite, 'persona' => $persona]);
    }

    public function ospiteAggiorna(Request $request, Prenotazione $prenotazione, Ospite $ospite)
    {
        $this->ospiteDellaPrenotazione($prenotazione, $ospite);

        $dati = $request->validate([
            'nome'                     => 'required|string|max:100',
            'cognome'                  => 'required|string|max:100',
            'email'                    => 'nullable|email|max:150',
            'telefono'                 => 'nullable|string|max:30',
            'sesso'                    => 'nullable|in:M,F',
            'data_nascita'             => 'nullable|date|before:today',
            'luogo_nascita'            => 'nullable|string|max:100',
            'luogo_nascita_provincia'  => 'nullable|string|size:2',
            'nazionalita'              => 'nullable|string|size:2',
            'indirizzo'                => 'nullable|string|max:255',
            'citta'                    => 'nullable|string|max:100',
            'cap'                      => 'nullable|string|max:10',
            'paese'                    => 'nullable|string|size:2',
            'note'                     => 'nullable|string|max:1000',
        ]);
        $dati['nazionalita'] = ! empty($dati['nazionalita']) ? strtoupper($dati['nazionalita']) : null;
        $dati['paese']       = ! empty($dati['paese']) ? strtoupper($dati['paese']) : null;
        $dati['luogo_nascita_provincia'] = ! empty($dati['luogo_nascita_provincia']) ? strtoupper($dati['luogo_nascita_provincia']) : null;

        $ospite->update($dati);

        return $this->tornaA($request, $prenotazione)->with('ok', 'Dati di ' . $ospite->nome . ' ' . $ospite->cognome . ' aggiornati.');
    }

    /** Aggiunge una persona mancante (adulto o bambino) alla prenotazione. */
    public function ospiteAggiungi(Request $request, Prenotazione $prenotazione)
    {
        $dati = $request->validate([
            'nome'    => 'required|string|max:100',
            'cognome' => 'required|string|max:100',
            'bambino' => 'nullable|boolean',
        ]);

        if ($prenotazione->postiMancanti() < 1) {
            return back()->withErrors(['ospiti' => 'Tutte le persone della prenotazione sono già registrate. Per aggiungerne altre aumenta adulti o bambini nella prenotazione.']);
        }

        DB::transaction(function () use ($prenotazione, $dati, $request) {
            // Sempre un ospite NUOVO: due persone con lo stesso nome non vanno fuse
            $ospite = Ospite::create(['nome' => $dati['nome'], 'cognome' => $dati['cognome']]);

            $prenotazione->accompagnatori()->attach($ospite->id, [
                'bambino' => $request->boolean('bambino'),
                'ordine'  => $prenotazione->accompagnatori()->count() + 1,
            ]);
        });

        return back()->with('ok', 'Ospite aggiunto: ' . $dati['nome'] . ' ' . $dati['cognome'] . '.');
    }

    /** Toglie un accompagnatore dalla prenotazione (il titolare non si rimuove). */
    public function ospiteRimuovi(Request $request, Prenotazione $prenotazione, Ospite $ospite)
    {
        if ($prenotazione->ospite_id === $ospite->id) {
            return back()->withErrors(['ospiti' => 'Il titolare della prenotazione non si può rimuovere.']);
        }
        $this->ospiteDellaPrenotazione($prenotazione, $ospite);

        DB::transaction(function () use ($prenotazione, $ospite) {
            $prenotazione->accompagnatori()->detach($ospite->id);

            // Se questa persona non compare in nessun'altra prenotazione, il record non serve più
            $usato = Prenotazione::where('ospite_id', $ospite->id)->exists()
                || DB::table('prenotazione_ospite')->where('ospite_id', $ospite->id)->exists();
            if (! $usato) {
                $ospite->delete();
            }
        });

        return $this->tornaA($request, $prenotazione)->with('ok', 'Ospite rimosso dalla prenotazione.');
    }


    // ======================================================================
    // MODIFICA PRENOTAZIONE
    // Soggiorno (camera, date, tariffa), persone, caparra, canale, note.
    // Prima dell'arrivo si può anche annullare o segnare no-show.
    // Date e camere si possono cambiare anche trascinando la barra nel tableau: le regole sono le stesse.
    // ======================================================================

    public function prenotazioneModifica(Prenotazione $prenotazione)
    {
        $prenotazione->load('ospite', 'accompagnatori', 'camere.tipologia');

        return view('frontdesk.modifica', [
            'prenotazione' => $prenotazione,
            'persone'      => $prenotazione->persone(),
            'camere'       => Camera::where('attiva', true)->with('tipologia')->orderBy('numero')->get(),
            'conto'        => $this->contoDi($prenotazione),
        ]);
    }

    public function prenotazioneAggiorna(Request $request, Prenotazione $prenotazione)
    {
        if (! $prenotazione->modificabile()) {
            return back()->withErrors(['modifica' => 'Questa prenotazione non si può più modificare (stato: ' . $prenotazione->stato . ').']);
        }

        $dati = $request->validate([
            'adulti'                 => 'required|integer|min:1|max:10',
            'bambini'                => 'nullable|integer|min:0|max:10',
            'canale'                 => 'required|in:diretto,telefono,walk_in,ota,altro',
            'note'                   => 'nullable|string|max:1000',
            'caparra'                => 'nullable|numeric|min:0',
            'camere'                 => 'required|array|min:1',
            'camere.*.camera_id'     => 'required|integer|exists:camere,id',
            'camere.*.data_arrivo'   => 'required|date_format:Y-m-d',
            'camere.*.data_partenza' => 'required|date_format:Y-m-d|after:camere.*.data_arrivo',
            'camere.*.tariffa'       => 'required|numeric|min:0',
        ], [
            'camere.*.data_partenza.after' => 'La partenza deve essere dopo l\'arrivo.',
            'camere.*.tariffa.required'    => 'Inserisci la tariffa a notte.',
        ]);

        $prenotazione->load('ospite', 'accompagnatori', 'camere');

        $adulti  = (int) $dati['adulti'];
        $bambini = (int) ($dati['bambini'] ?? 0);

        // Non si può scendere sotto le persone già registrate, adulti e bambini separatamente
        if ($adulti < $prenotazione->adultiRegistrati()) {
            return back()->withInput()->withErrors(['modifica' => 'Hai già registrato ' . $prenotazione->adultiRegistrati() . ' adulti: rimuovi prima gli ospiti in più dal check-in.']);
        }
        if ($bambini < $prenotazione->bambiniRegistrati()) {
            return back()->withInput()->withErrors(['modifica' => 'Hai già registrato ' . $prenotazione->bambiniRegistrati() . ' bambini: rimuovi prima gli ospiti in più dal check-in.']);
        }

        // Le righe inviate devono essere esattamente le camere che la prenotazione ha ora
        $attuali = $prenotazione->camere->pluck('id')->map(fn ($i) => (int) $i)->sort()->values()->all();
        $inviate = collect(array_keys($dati['camere']))->map(fn ($i) => (int) $i)->sort()->values()->all();
        if ($attuali !== $inviate) {
            return back()->withInput()->withErrors(['modifica' => 'Le camere della prenotazione sono cambiate nel frattempo: ricarica la pagina.']);
        }

        try {
            DB::transaction(function () use ($dati, $prenotazione) {
                foreach ($dati['camere'] as $origine => $c) {
                    $origine = (int) $origine;
                    $destinazione = (int) $c['camera_id'];

                    $pivot = DB::table('prenotazione_camera')
                        ->where('prenotazione_id', $prenotazione->id)->where('camera_id', $origine)->first();

                    $cambia = $destinazione !== $origine
                        || $c['data_arrivo'] !== substr((string) $pivot->data_arrivo, 0, 10)
                        || $c['data_partenza'] !== substr((string) $pivot->data_partenza, 0, 10);

                    if ($cambia) {
                        $errore = $this->applicaSpostamento($prenotazione, [
                            'camera_origine' => $origine,
                            'camera_id'      => $destinazione,
                            'data_arrivo'    => $c['data_arrivo'],
                            'data_partenza'  => $c['data_partenza'],
                        ]);
                        if ($errore) {
                            throw new \DomainException($errore);
                        }
                    }

                    DB::table('prenotazione_camera')
                        ->where('prenotazione_id', $prenotazione->id)->where('camera_id', $destinazione)
                        ->update(['tariffa' => $c['tariffa'], 'updated_at' => now()]);
                }
            });
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['modifica' => $e->getMessage()]);
        }

        // Il totale segue il soggiorno: camere (notti x tariffa) + tassa di soggiorno sugli adulti
        $prenotazione = $prenotazione->fresh(['camere']);
        $soggiorno = $prenotazione->camere->sum(fn ($c) =>
            Carbon::parse($c->pivot->data_arrivo)->diffInDays($c->pivot->data_partenza) * (float) $c->pivot->tariffa);
        $notti = (int) $prenotazione->data_arrivo->diffInDays($prenotazione->data_partenza);

        $prenotazione->update([
            'adulti'          => $adulti,
            'bambini'         => $bambini,
            'canale'          => $dati['canale'],
            'note'            => $dati['note'] ?? null,
            'importo_caparra' => (float) ($dati['caparra'] ?? 0),
            'importo_totale'  => round($soggiorno + $this->calcolaTassa($adulti, $notti), 2),
        ]);

        // Se il conto è già aperto (ospite in casa), le sue voci seguono la prenotazione
        $avviso = $this->riallineaConto($prenotazione);

        return redirect()->route('prenotazioni.scheda', $prenotazione)
            ->with('ok', 'Prenotazione aggiornata.' . ($avviso ? ' ' . $avviso : ''));
    }

    /** Annulla una prenotazione non ancora arrivata. La camera torna libera nel tableau. */
    public function prenotazioneAnnulla(Request $request, Prenotazione $prenotazione)
    {
        return $this->chiudiPrenotazionePrimaDellArrivo($request, $prenotazione, 'annullata');
    }

    /** Segna il no-show (l'ospite non si è presentato). Solo dal giorno di arrivo in poi. */
    public function prenotazioneNoShow(Request $request, Prenotazione $prenotazione)
    {
        return $this->chiudiPrenotazionePrimaDellArrivo($request, $prenotazione, 'no_show');
    }

    private function chiudiPrenotazionePrimaDellArrivo(Request $request, Prenotazione $prenotazione, string $nuovoStato)
    {
        $caparra = round((float) $prenotazione->importo_caparra, 2);

        $dati = $request->validate([
            'motivo'             => $nuovoStato === 'annullata' ? 'required|string|max:255' : 'nullable|string|max:255',
            // Se c'è una caparra l'operatore deve dire che fine fa: rimborsarla, trattenerla o dividerla
            'caparra_esito'      => $caparra > 0 ? 'required|in:rimborsa,trattieni,parziale' : 'nullable',
            'caparra_trattenuta' => 'nullable|required_if:caparra_esito,parziale|numeric|min:0',
        ], [
            'motivo.required'               => 'Scrivi il motivo dell\'annullamento (serve per lo storico).',
            'caparra_esito.required'        => 'Scegli cosa fare della caparra: rimborsarla, trattenerla o dividerla.',
            'caparra_esito.in'              => 'Scegli cosa fare della caparra: rimborsarla, trattenerla o dividerla.',
            'caparra_trattenuta.required_if' => 'Indica quanto trattenere della caparra.',
            'caparra_trattenuta.numeric'    => 'L\'importo da trattenere deve essere un numero.',
            'caparra_trattenuta.min'        => 'L\'importo da trattenere non può essere negativo.',
        ]);

        if (! in_array($prenotazione->stato, ['confermata', 'in_attesa'], true)) {
            return back()->withErrors(['modifica' => 'Si può annullare o segnare no-show solo una prenotazione non ancora arrivata (stato attuale: ' . $prenotazione->stato . ').']);
        }
        if ($nuovoStato === 'no_show' && $prenotazione->data_arrivo->gt(Carbon::today())) {
            return back()->withErrors(['modifica' => 'Il no-show si segna dal giorno di arrivo in poi: l\'arrivo è previsto il ' . $prenotazione->data_arrivo->format('d/m/Y') . '.']);
        }

        // Quanto trattenere e quanto rimborsare (sempre: trattenuta + rimborsata = caparra)
        $trattenuta = 0.0;
        $rimborsata = 0.0;
        if ($caparra > 0) {
            $trattenuta = match ($dati['caparra_esito']) {
                'trattieni' => $caparra,
                'parziale'  => round((float) $dati['caparra_trattenuta'], 2),
                default     => 0.0,
            };
            if ($trattenuta > $caparra) {
                return back()->withInput()->withErrors(['modifica' => 'Non puoi trattenere più della caparra versata (€ ' . number_format($caparra, 2, ',', '.') . ').']);
            }
            $rimborsata = round($caparra - $trattenuta, 2);
        }

        // Un eventuale conto con importi da saldare va gestito dal folio prima di chiudere la prenotazione.
        // La caparra non conta: è proprio quella che si sta sistemando adesso.
        $conto = $this->contoDi($prenotazione);
        if ($conto) {
            $saldoSenzaCaparra = collect($this->folioRiepilogo($conto->id))->sum('saldo')
                + (float) DB::table('voci_conto')->where('conto_id', $conto->id)->where('tipo', 'caparra')->sum('importo');
            if (abs($saldoSenzaCaparra) > 0.005) {
                return back()->withInput()->withErrors(['modifica' => 'Esiste un conto con un saldo aperto: sistemalo dal folio prima di chiudere la prenotazione.']);
            }
            // Un folio con fattura emessa non si tocca: la caparra va sistemata a mano
            if ($caparra > 0 && $conto->stato === 'aperto' && $this->folioBloccato($conto->id, 'A')) {
                return back()->withInput()->withErrors(['modifica' => 'Il folio dell\'ospite ha una fattura già emessa: il conto non si può modificare, quindi l\'esito della caparra non si può registrare da qui.']);
            }
        }

        DB::transaction(function () use ($prenotazione, $nuovoStato, $dati, $conto, $caparra, $trattenuta, $rimborsata) {
            $modifiche = [
                'stato'         => $nuovoStato,
                'uscita_motivo' => filled($dati['motivo'] ?? null) ? trim($dati['motivo']) : null,
            ];
            if ($caparra > 0) {
                $modifiche['caparra_trattenuta'] = $trattenuta;
                $modifiche['caparra_rimborsata'] = $rimborsata;
                $modifiche['caparra_gestita_il'] = now();
            }
            $prenotazione->update($modifiche);

            if ($conto && $conto->stato === 'aperto') {
                // Nel conto la caparra versata è un credito: lo si pareggia con due addebiti,
                // uno per la parte che torna all'ospite e uno per la parte che l'hotel trattiene.
                // (Il rimborso è solo registrato: il bonifico o la restituzione del contante restano fuori dal sistema.)
                $voce = fn (string $tipo, string $descrizione, float $importo, string $origine) => DB::table('voci_conto')->insert([
                    'conto_id'      => $conto->id,
                    'folio'         => 'A',
                    'tipo'          => $tipo,
                    'descrizione'   => $descrizione,
                    'importo'       => $importo,
                    'origine'       => $origine,
                    'registrata_il' => now(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $causale = $nuovoStato === 'annullata' ? 'annullamento' : 'no-show';

                if ($rimborsata > 0) {
                    $voce('rettifica', 'Rimborso caparra (' . $causale . ')', $rimborsata, 'caparra_rimborso');
                }
                if ($trattenuta > 0) {
                    $voce('servizio', 'Caparra trattenuta (' . $causale . ')', $trattenuta, 'caparra_trattenuta');
                }

                $this->aggiornaSaldoConto($conto->id);

                DB::table('conti')->where('id', $conto->id)
                    ->update(['stato' => 'chiuso', 'chiuso_il' => now(), 'saldo' => 0, 'updated_at' => now()]);
            }
        });

        $esito = '';
        if ($caparra > 0) {
            $euro = fn (float $x) => '€ ' . number_format($x, 2, ',', '.');
            $esito = match (true) {
                $trattenuta <= 0         => ' Caparra di ' . $euro($caparra) . ' da rimborsare all\'ospite.',
                $rimborsata <= 0         => ' Caparra di ' . $euro($caparra) . ' trattenuta.',
                default                  => ' Caparra: ' . $euro($trattenuta) . ' trattenuti, ' . $euro($rimborsata) . ' da rimborsare.',
            };
        }

        return redirect()->route('prenotazioni.scheda', $prenotazione)
            ->with('ok', ($nuovoStato === 'annullata' ? 'Prenotazione annullata: la camera è di nuovo libera.' : 'Prenotazione segnata come no-show: la camera è di nuovo libera.') . $esito);
    }


    // ======================================================================
    // NUOVA PRENOTAZIONE
    // ======================================================================

    /** Legge la configurazione hotel (tassa di soggiorno, ecc.); [] se il file manca o è malformato. */
    private function configHotel(): array
    {
        try {
            return json_decode(file_get_contents(config_path('hotel_config.json')), true) ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Tassa di soggiorno (dalla configurazione hotel) per N adulti e N notti. */
    private function calcolaTassa(int $adulti, int $notti): float
    {
        $cfg = $this->configHotel()['fatturazione']['tassa_soggiorno'] ?? ['attiva' => false];
        if (empty($cfg['attiva'])) {
            return 0.0;
        }
        $nottiTassabili = min($notti, $cfg['max_notti'] ?? $notti);
        return $adulti * (float) ($cfg['importo_per_persona_notte'] ?? 0) * $nottiTassabili;
    }

    /** True se la camera è libera (nessuna prenotazione attiva) tra $dal (incluso) e $al (escluso). */
    private function cameraLibera(int $cameraId, string $dal, string $al): bool
    {
        return ! DB::table('prenotazione_camera as pc')
            ->join('prenotazioni as p', 'p.id', '=', 'pc.prenotazione_id')
            ->whereNull('p.deleted_at')
            ->whereNotIn('p.stato', self::STATI_ESCLUSI)
            ->where('pc.camera_id', $cameraId)
            ->where('pc.data_arrivo', '<', $al)
            ->where('pc.data_partenza', '>', $dal)
            ->exists();
    }


    /**
     * Disponibilità live mentre si compila "Nuova prenotazione": stessa regola
     * di nuovaCrea(), così l'avviso che l'utente vede non può disallinearsi
     * da quello che succede davvero al salvataggio.
     * GET /prenotazioni/nuova/disponibilita (chiamata via fetch, risponde JSON)
     */
    public function nuovaDisponibilita(Request $request)
    {
        $tipologiaId = (int) $request->query('tipologia_camera_id');
        $dal = $request->query('data_arrivo');
        $al  = $request->query('data_partenza');

        if (! $tipologiaId || ! $dal || ! $al) {
            return response()->json(['pronto' => false, 'camere' => []]);
        }
        try {
            $okDate = Carbon::parse($dal)->lt(Carbon::parse($al));
        } catch (\Throwable $e) {
            $okDate = false;
        }
        if (! $okDate) {
            return response()->json(['pronto' => false, 'camere' => []]);
        }

        $camere = Camera::where('tipologia_camera_id', $tipologiaId)
            ->where('attiva', true)->where('stato_pulizia', '!=', 'manutenzione')
            ->orderBy('numero')->get()
            ->filter(fn ($c) => $this->cameraLibera($c->id, $dal, $al))
            ->map(fn ($c) => ['id' => $c->id, 'numero' => $c->numero])
            ->values();

        return response()->json(['pronto' => true, 'camere' => $camere]);
    }

    public function nuovaForm(Request $request)
    {
        $tipologie = TipologiaCamera::where('attiva', true)->orderBy('prezzo_base')->get();
        $camere = Camera::where('attiva', true)->where('stato_pulizia', '!=', 'manutenzione')
            ->orderBy('numero')->get()->groupBy('tipologia_camera_id');
        $tassaCfg = $this->configHotel()['fatturazione']['tassa_soggiorno'] ?? ['attiva' => false, 'importo_per_persona_notte' => 0, 'max_notti' => 99];

        // Arrivo: ?data=YYYY-MM-DD (se non valida o nel passato, oggi); partenza proposta = giorno dopo
        try {
            $arrivo = Carbon::parse($request->query('data', Carbon::today()->toDateString()))->startOfDay();
        } catch (\Throwable $e) {
            $arrivo = Carbon::today();
        }
        if ($arrivo->lt(Carbon::today())) {
            $arrivo = Carbon::today();
        }
        $arrivoDefault = $arrivo->toDateString();
        $partenzaDefault = $arrivo->copy()->addDay()->toDateString();

        // Clic su una cella vuota del tableau: ?camera=ID preseleziona tipologia e camera
        $cameraScelta = $request->filled('camera')
            ? Camera::where('id', (int) $request->query('camera'))->where('attiva', true)->where('stato_pulizia', '!=', 'manutenzione')->first()
            : null;

        return view('frontdesk.nuova', compact('tipologie', 'camere', 'tassaCfg', 'arrivoDefault', 'partenzaDefault', 'cameraScelta'));
    }

    public function nuovaCrea(Request $request)
    {
        $dati = $request->validate([
            'ospite_nome'          => 'required|string|max:100',
            'ospite_cognome'       => 'required|string|max:100',
            'ospite_email'         => 'nullable|email|max:150',
            'ospite_telefono'      => 'nullable|string|max:30',
            'data_arrivo'          => 'required|date|after_or_equal:today',
            'data_partenza'        => 'required|date|after:data_arrivo',
            'adulti'               => 'required|integer|min:1|max:10',
            'bambini'              => 'nullable|integer|min:0|max:10',
            'canale'               => 'required|in:diretto,telefono,walk_in,ota,altro',
            'tipologia_camera_id'  => 'required|exists:tipologie_camera,id',
            'camera_id'            => 'nullable|exists:camere,id',
            'caparra'              => 'nullable|numeric|min:0',
        ]);

        $tipologia = TipologiaCamera::findOrFail($dati['tipologia_camera_id']);
        $automatica = $request->boolean('automatica');

        // --- Scelta della camera ---
        if ($automatica) {
            $camera = Camera::where('tipologia_camera_id', $tipologia->id)
                ->where('attiva', true)->where('stato_pulizia', '!=', 'manutenzione')
                ->orderBy('numero')->get()
                ->first(fn ($c) => $this->cameraLibera($c->id, $dati['data_arrivo'], $dati['data_partenza']));

            if (! $camera) {
                return back()->withInput()->withErrors(['nuova' => 'Nessuna camera di tipo "' . $tipologia->nome . '" libera per queste date.']);
            }
        } else {
            if (empty($dati['camera_id'])) {
                return back()->withInput()->withErrors(['nuova' => 'Scegli una camera oppure attiva l\'assegnazione automatica.']);
            }
            $camera = Camera::find($dati['camera_id']);
            if (! $camera || $camera->tipologia_camera_id !== $tipologia->id) {
                return back()->withInput()->withErrors(['nuova' => 'Camera non valida per la tipologia scelta.']);
            }
            if ($camera->stato_pulizia === 'manutenzione') {
                return back()->withInput()->withErrors(['nuova' => 'La camera ' . $camera->numero . ' è in lavori.']);
            }
            if (! $this->cameraLibera($camera->id, $dati['data_arrivo'], $dati['data_partenza'])) {
                return back()->withInput()->withErrors(['nuova' => 'La camera ' . $camera->numero . ' non è libera in quelle date.']);
            }
        }

        // --- Importi: soggiorno + tassa di soggiorno (dalla configurazione hotel) ---
        $notti = (int) Carbon::parse($dati['data_arrivo'])->diffInDays($dati['data_partenza']);
        $tassa = $this->calcolaTassa((int) $dati['adulti'], $notti);
        $soggiorno = $notti * (float) $tipologia->prezzo_base;
        $totale = $soggiorno + $tassa;
        $caparra = $request->filled('caparra') ? (float) $dati['caparra'] : round($totale * 0.30, 2);

        $prenotazione = DB::transaction(function () use ($dati, $camera, $tipologia, $notti, $totale, $caparra) {
            // Ospite esistente (stesso nome e cognome) o nuovo
            $ospite = Ospite::whereRaw('LOWER(nome) = ? AND LOWER(cognome) = ?', [
                strtolower($dati['ospite_nome']), strtolower($dati['ospite_cognome']),
            ])->first();

            if (! $ospite) {
                $ospite = Ospite::create([
                    'nome'     => $dati['ospite_nome'],
                    'cognome'  => $dati['ospite_cognome'],
                    'email'    => $dati['ospite_email'] ?? null,
                    'telefono' => $dati['ospite_telefono'] ?? null,
                ]);
            }

            $prenotazione = Prenotazione::create([
                'codice'          => 'R-' . now()->format('ymd') . '-' . Str::upper(Str::random(4)),
                'ospite_id'       => $ospite->id,
                'data_arrivo'     => $dati['data_arrivo'],
                'data_partenza'   => $dati['data_partenza'],
                'adulti'          => $dati['adulti'],
                'bambini'         => $dati['bambini'] ?? 0,
                'stato'           => 'confermata',
                'canale'          => $dati['canale'],
                'importo_totale'  => $totale,
                'importo_caparra' => $caparra,
            ]);

            $prenotazione->camere()->attach($camera->id, [
                'data_arrivo'   => $dati['data_arrivo'],
                'data_partenza' => $dati['data_partenza'],
                'tariffa'       => $tipologia->prezzo_base,
            ]);

            return $prenotazione;
        });

        return redirect()->route('prenotazioni.scheda', $prenotazione)->with('ok', 'Prenotazione creata · camera ' . $camera->numero . '.');
    }

    public function checkin(Prenotazione $prenotazione)
    {
        $prenotazione->load('ospite', 'accompagnatori', 'camere');
        $persone = $prenotazione->persone();
        $mancanti = $prenotazione->postiMancanti();

        // Tipo delle persone ancora da inserire: prima gli adulti, poi i bambini
        $adultiRegistrati = $persone->where('bambino', false)->count();
        $slotMancanti = [];
        for ($i = 0; $i < $mancanti; $i++) {
            $slotMancanti[] = (object) [
                'numero'  => $persone->count() + $i + 1,
                'bambino' => ($adultiRegistrati + $i) >= (int) $prenotazione->adulti,
            ];
        }

        return view('frontdesk.checkin', [
            'prenotazione'  => $prenotazione,
            'persone'       => $persone,
            'slotMancanti'  => $slotMancanti,
            'daCompletare'  => $prenotazione->checkinMancanti(),   // cosa manca, persona per persona
            'avvisi'        => $this->checkinAvvisi($prenotazione), // cose da confermare prima di procedere
        ]);
    }

    public function checkinAccettaPrivacy(Prenotazione $prenotazione, Ospite $ospite)
    {
        $this->ospiteDellaPrenotazione($prenotazione, $ospite);

        $ospite->update([
            'privacy_accettata'    => true,
            'privacy_accettata_il' => now(),
        ]);

        return back()->with('ok', 'Informativa privacy registrata per ' . $ospite->nome . ' ' . $ospite->cognome . '.');
    }

    /** Salva (o sostituisce) tipo, numero e scansione del documento di UNA persona. */
    public function ospiteSalvaDocumento(Request $request, Prenotazione $prenotazione, Ospite $ospite)
    {
        $this->ospiteDellaPrenotazione($prenotazione, $ospite);

        $dati = $request->validate([
            'tipo_documento'           => 'required|in:carta_identita,passaporto,patente',
            'numero_documento'         => 'required|string|max:50',
            'luogo_rilascio'           => 'nullable|string|max:100',
            'luogo_rilascio_provincia' => 'nullable|string|size:2',
            'scadenza_documento'       => 'nullable|date',
            'file'                     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5 MB
        ], [
            'tipo_documento.required'   => 'Scegli il tipo di documento.',
            'numero_documento.required' => 'Inserisci il numero del documento.',
            'scadenza_documento.date'   => 'La data di scadenza non è valida.',
            'file.uploaded' => 'Caricamento non riuscito: il file supera il limite del server. Controlla upload_max_filesize e post_max_size in php.ini (servono almeno 5M).',
            'file.mimes'    => 'Formato non valido: usa una foto JPG o PNG oppure un PDF.',
            'file.max'      => 'File troppo pesante: massimo 5 MB.',
        ]);

        $dati['luogo_rilascio']           = filled($dati['luogo_rilascio'] ?? null) ? trim($dati['luogo_rilascio']) : null;
        $dati['luogo_rilascio_provincia'] = filled($dati['luogo_rilascio_provincia'] ?? null) ? strtoupper(trim($dati['luogo_rilascio_provincia'])) : null;
        $dati['scadenza_documento']       = filled($dati['scadenza_documento'] ?? null) ? $dati['scadenza_documento'] : null;

        if ($request->hasFile('file')) {
            $vecchio = $ospite->documento_file;
            $dati['documento_file'] = $request->file('file')->store('documenti', 'public');
            if ($vecchio) {
                Storage::disk('public')->delete($vecchio);
            }
        }
        unset($dati['file']);

        $ospite->update($dati);

        return $this->tornaA($request, $prenotazione)->with('ok', 'Documento di ' . $ospite->nome . ' ' . $ospite->cognome . ' registrato.');
    }

    /**
     * Campi di UNA persona inviati dalla pagina di check-in, pronti per update() o create().
     * Gestisce anche la scansione del documento (sostituisce la vecchia) e la privacy
     * (una volta accettata resta accettata, con la sua data originale).
     */
   private function campiCheckinOspite(Request $request, array $campi, string $percorsoFile, ?Ospite $ospite = null): array
    {
        $modifiche = [
            'nome'                     => $campi['nome'] ?? null,
            'cognome'                  => $campi['cognome'] ?? null,
            'sesso'                    => in_array($campi['sesso'] ?? null, ['M', 'F'], true) ? $campi['sesso'] : null,
            'data_nascita'             => $campi['data_nascita'] ?? null,
            'luogo_nascita'            => filled($campi['luogo_nascita'] ?? null) ? trim($campi['luogo_nascita']) : null,
            'luogo_nascita_provincia'  => filled($campi['luogo_nascita_provincia'] ?? null) ? strtoupper(trim($campi['luogo_nascita_provincia'])) : null,
            'nazionalita'              => filled($campi['nazionalita'] ?? null) ? strtoupper($campi['nazionalita']) : null,
            'tipo_documento'           => $campi['tipo_documento'] ?? null,
            'numero_documento'         => $campi['numero_documento'] ?? null,
            'luogo_rilascio'           => filled($campi['luogo_rilascio'] ?? null) ? trim($campi['luogo_rilascio']) : null,
            'luogo_rilascio_provincia' => filled($campi['luogo_rilascio_provincia'] ?? null) ? strtoupper(trim($campi['luogo_rilascio_provincia'])) : null,
            'scadenza_documento'       => filled($campi['scadenza_documento'] ?? null) ? $campi['scadenza_documento'] : null,
        ];

        if ($request->hasFile($percorsoFile)) {
            $modifiche['documento_file'] = $request->file($percorsoFile)->store('documenti', 'public');
            if ($ospite && $ospite->documento_file) {
                Storage::disk('public')->delete($ospite->documento_file);
            }
        }

        if (! empty($campi['privacy']) && ! ($ospite && $ospite->privacy_accettata)) {
            $modifiche['privacy_accettata']    = true;
            $modifiche['privacy_accettata_il'] = now();
        }

        return $modifiche;
    }

    /**
     * Cose da confermare prima del check-in. Non bloccano: l'operatore spunta "Procedo comunque".
     * (Camera in lavori invece blocca, vedi checkinCompleta.)
     *
     * @return array<int, string>
     */
    private function checkinAvvisi(Prenotazione $prenotazione): array
    {
        $prenotazione->loadMissing('camere');
        $avvisi = [];

        if ($prenotazione->stato === 'in_attesa') {
            $avvisi[] = 'La prenotazione è ancora un\'opzione: non risulta confermata.';
        }
        if ($prenotazione->data_arrivo->gt(Carbon::today())) {
            $avvisi[] = 'Check-in anticipato: l\'arrivo previsto è il ' . $prenotazione->data_arrivo->format('d/m/Y') . '.';
        }
        foreach ($prenotazione->persone() as $p) {
            $scadenza = $p->ospite->scadenza_documento;
            if ($scadenza && $scadenza->lt(Carbon::today())) {
                $avvisi[] = 'Il documento di ' . trim($p->ospite->nome . ' ' . $p->ospite->cognome) . ' è scaduto il ' . $scadenza->format('d/m/Y') . '.';
            }
        }
        foreach ($prenotazione->camere as $camera) {
            if (in_array($camera->stato_pulizia, ['sporca', 'in_pulizia'], true)) {
                $avvisi[] = 'La camera ' . $camera->numero . ' non è ancora pronta (' . $camera->etichettaStato() . ').';
            }
        }

        return $avvisi;
    }

    /**
     * Un solo invio per tutta la pagina di check-in.
     *  - azione=salva     salva i dati di tutte le persone e resta sulla pagina
     *  - azione=completa  salva, controlla che tutto sia a posto, completa il check-in e APRE IL CONTO
     * Per ogni persona: anagrafica, documento (anche il file) e privacy; più le persone ancora da registrare.
     */
    public function checkinCompleta(Request $request, Prenotazione $prenotazione)
    {
        if (! $prenotazione->modificabile()) {
            return back()->withErrors(['checkin' => 'Questa prenotazione non è più modificabile.']);
        }

        $request->validate([
            'ospiti'                    => 'nullable|array',
            'ospiti.*.nome'             => 'required|string|max:100',
            'ospiti.*.cognome'          => 'required|string|max:100',
            'ospiti.*.data_nascita'     => 'nullable|date|before:today',
            'ospiti.*.sesso'                    => 'nullable|in:M,F',
            'ospiti.*.luogo_nascita'            => 'nullable|string|max:100',
            'ospiti.*.luogo_nascita_provincia'  => 'nullable|string|size:2',
            'ospiti.*.luogo_rilascio'           => 'nullable|string|max:100',
            'ospiti.*.luogo_rilascio_provincia' => 'nullable|string|size:2',
            'ospiti.*.nazionalita'      => 'nullable|string|size:2',
            'ospiti.*.tipo_documento'   => 'nullable|required_with:ospiti.*.numero_documento|in:carta_identita,passaporto,patente',
            'ospiti.*.numero_documento' => 'nullable|required_with:ospiti.*.tipo_documento|string|max:50',
            'ospiti.*.ente_rilascio'    => 'nullable|string|max:150',
            'ospiti.*.scadenza_documento' => 'nullable|date',
            'ospiti.*.file'             => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5 MB
            'ospiti.*.privacy'          => 'nullable|boolean',
            'nuovi'                     => 'nullable|array',
            'nuovi.*.nome'              => 'nullable|required_with:nuovi.*.cognome,nuovi.*.data_nascita,nuovi.*.nazionalita,nuovi.*.numero_documento|string|max:100',
            'nuovi.*.cognome'           => 'nullable|required_with:nuovi.*.nome,nuovi.*.data_nascita,nuovi.*.nazionalita,nuovi.*.numero_documento|string|max:100',
            'nuovi.*.bambino'           => 'nullable|boolean',
            'nuovi.*.data_nascita'      => 'nullable|date|before:today',
            'nuovi.*.sesso'                     => 'nullable|in:M,F',
            'nuovi.*.luogo_nascita'             => 'nullable|string|max:100',
            'nuovi.*.luogo_nascita_provincia'   => 'nullable|string|size:2',
            'nuovi.*.luogo_rilascio'            => 'nullable|string|max:100',
            'nuovi.*.luogo_rilascio_provincia'  => 'nullable|string|size:2',
            'nuovi.*.nazionalita'       => 'nullable|string|size:2',
            'nuovi.*.tipo_documento'    => 'nullable|required_with:nuovi.*.numero_documento|in:carta_identita,passaporto,patente',
            'nuovi.*.numero_documento'  => 'nullable|required_with:nuovi.*.tipo_documento|string|max:50',
            'nuovi.*.ente_rilascio'     => 'nullable|string|max:150',
            'nuovi.*.scadenza_documento' => 'nullable|date',
            'nuovi.*.file'              => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'nuovi.*.privacy'           => 'nullable|boolean',
        ], [
            'required'                  => 'Campo obbligatorio.',
            'required_with'             => 'Compila anche questo campo.',
            'ospiti.*.data_nascita.before' => 'La data di nascita deve essere nel passato.',
            'ospiti.*.nazionalita.size' => 'Usa il codice di 2 lettere (es. IT).',
            'ospiti.*.scadenza_documento.date' => 'La data di scadenza non è valida.',
            'nuovi.*.scadenza_documento.date'  => 'La data di scadenza non è valida.',
            'ospiti.*.file.uploaded'    => 'Caricamento non riuscito: il file supera il limite del server. Controlla upload_max_filesize e post_max_size in php.ini (servono almeno 5M).',
            'ospiti.*.file.mimes'       => 'Formato non valido: usa una foto JPG o PNG oppure un PDF.',
            'ospiti.*.file.max'         => 'File troppo pesante: massimo 5 MB.',
            'nuovi.*.data_nascita.before' => 'La data di nascita deve essere nel passato.',
            'nuovi.*.nazionalita.size'  => 'Usa il codice di 2 lettere (es. IT).',
            'nuovi.*.file.uploaded'     => 'Caricamento non riuscito: il file supera il limite del server. Controlla upload_max_filesize e post_max_size in php.ini (servono almeno 5M).',
            'nuovi.*.file.mimes'        => 'Formato non valido: usa una foto JPG o PNG oppure un PDF.',
            'nuovi.*.file.max'          => 'File troppo pesante: massimo 5 MB.',
        ]);

        // Solo le persone di QUESTA prenotazione: gli id inviati dal form non si accettano alla cieca
        $ammessi = $prenotazione->persone()->map(fn ($p) => (int) $p->ospite->id)->all();

        // Le righe vuote (persone che non si vogliono ancora registrare) si ignorano; la chiave resta quella del form
        $nuovi = collect($request->input('nuovi', []))
            ->filter(fn ($n) => filled($n['nome'] ?? null) && filled($n['cognome'] ?? null));
        if ($nuovi->count() > $prenotazione->postiMancanti()) {
            return back()->withInput()->withErrors(['checkin' => 'Hai inserito più persone di quante ne prevede la prenotazione. Se servono, aumenta adulti o bambini dalla modifica della prenotazione.']);
        }

        DB::transaction(function () use ($request, $prenotazione, $ammessi, $nuovi) {
            foreach ($request->input('ospiti', []) as $id => $campi) {
                if (! in_array((int) $id, $ammessi, true)) {
                    continue;
                }
                $ospite = Ospite::find((int) $id);
                if ($ospite) {
                    $ospite->update($this->campiCheckinOspite($request, $campi, "ospiti.$id.file", $ospite));
                }
            }

            foreach ($nuovi as $chiave => $n) {
                // Sempre un ospite NUOVO: due persone con lo stesso nome non vanno fuse
                $ospite = Ospite::create($this->campiCheckinOspite($request, $n, "nuovi.$chiave.file"));
                $prenotazione->accompagnatori()->attach($ospite->id, [
                    'bambino' => ! empty($n['bambino']),
                    'ordine'  => $prenotazione->accompagnatori()->count() + 1,
                ]);
            }
        });

        // Da qui in poi si lavora sui dati appena salvati
        $prenotazione = $prenotazione->fresh(['ospite', 'accompagnatori', 'camere']);

        $inCasa = $prenotazione->stato === 'in_casa';
        if ($inCasa || $request->input('azione') !== 'completa') {
            return redirect()->route('prenotazioni.checkin', $prenotazione)
                ->with('ok', $inCasa ? 'Dati aggiornati.' : 'Dati salvati. Il check-in non è ancora completato.');
        }

        // --- Completa il check-in: i dati sono già salvati, qui si controlla solo se si può procedere ---
        $problemi = [];

        $mancanti = $prenotazione->checkinMancanti();
        if ($mancanti) {
            $problemi[] = 'Per completare il check-in manca: ' . implode(' · ', $mancanti) . '.';
        }
        foreach ($prenotazione->camere as $camera) {
            if ($camera->stato_pulizia === 'manutenzione') {
                $problemi[] = 'La camera ' . $camera->numero . ' è in lavori: cambiala dalla modifica della prenotazione prima del check-in.';
            }
        }
        $avvisi = $this->checkinAvvisi($prenotazione);
        if ($avvisi && ! $request->boolean('procedi_comunque')) {
            $problemi[] = 'Ci sono avvisi da confermare: spunta "Procedo comunque" per completare.';
        }

        if ($problemi) {
            return redirect()->route('prenotazioni.checkin', $prenotazione)->withErrors(['checkin' => $problemi]);
        }

        // Check-in e apertura del conto insieme: o succedono tutti e due o nessuno dei due
        DB::transaction(function () use ($prenotazione) {
            $prenotazione->update(['stato' => 'in_casa', 'entrata_il' => now()]);
            $this->apriContoPer($prenotazione); // se il conto esiste già, usa quello
        });

        return redirect()->route('prenotazioni.scheda', $prenotazione)->with('ok', 'Check-in completato · conto aperto.');
    }

    // ======================================================================
    // CHECK-OUT
    // Fine normale del soggiorno oppure partenza anticipata (emergenza): in ogni caso
    // la camera si libera, passa a "sporca" e l'ospite risulta partito. Il saldo non
    // blocca mai la partenza: se resta da incassare il conto rimane aperto.
    // ======================================================================

    /** Notti e importi se l'ospite parte oggi: ogni camera si accorcia fino a oggi (minimo 1 notte, mai oltre il previsto). */
    private function calcolaRidotto(Prenotazione $prenotazione, Carbon $oggi): array
    {
        $perCamera = [];
        $soggiorno = 0.0;
        $partenza = null;

        foreach ($prenotazione->camere as $camera) {
            $arrivo = Carbon::parse($camera->pivot->data_arrivo)->startOfDay();
            $previsto = Carbon::parse($camera->pivot->data_partenza)->startOfDay();

            $fine = $oggi->copy();
            if ($fine->lte($arrivo)) {
                $fine = $arrivo->copy()->addDay();
            }
            if ($fine->gt($previsto)) {
                $fine = $previsto;
            }

            $notti = (int) $arrivo->diffInDays($fine);
            $perCamera[$camera->id] = ['notti' => $notti, 'fine' => $fine->toDateString(), 'numero' => $camera->numero];
            $soggiorno += $notti * (float) $camera->pivot->tariffa;
            $partenza = $partenza && $partenza->gt($fine) ? $partenza : $fine;
        }

        $nottiTotali = $partenza ? (int) $prenotazione->data_arrivo->diffInDays($partenza) : 0;

        return [
            'per_camera' => $perCamera,
            'partenza'   => $partenza,
            'notti'      => $nottiTotali,
            'soggiorno'  => round($soggiorno, 2),
            'tassa'      => round($this->calcolaTassa((int) $prenotazione->adulti, $nottiTotali), 2),
        ];
    }

    /** Saldo attuale della prenotazione (dal conto se aperto, altrimenti dai prezzi delle camere). */
    private function saldoPrenotazione(Prenotazione $prenotazione): array
    {
        $conto = $this->contoDi($prenotazione);
        if ($conto) {
            $r = $this->folioRiepilogo($conto->id);
            return ['conto' => $conto, 'saldo' => round(collect($r)->sum('saldo'), 2), 'riepilogo' => $r];
        }

        $soggiorno = 0.0;
        foreach ($prenotazione->camere as $camera) {
            $soggiorno += (int) Carbon::parse($camera->pivot->data_arrivo)->diffInDays($camera->pivot->data_partenza) * (float) $camera->pivot->tariffa;
        }
        $nottiTotali = (int) $prenotazione->data_arrivo->diffInDays($prenotazione->data_partenza);
        $totale = $soggiorno + $this->calcolaTassa((int) $prenotazione->adulti, $nottiTotali);

        return ['conto' => null, 'saldo' => round($totale - (float) $prenotazione->importo_caparra, 2), 'riepilogo' => []];
    }

    public function checkout(Prenotazione $prenotazione)
    {
        $prenotazione->load('ospite', 'camere');

        if ($prenotazione->stato !== 'in_casa') {
            return redirect()->route('prenotazioni.scheda', $prenotazione)
                ->withErrors(['checkout' => 'Il check-out si fa solo per un ospite in casa.']);
        }

        $oggi = Carbon::today();
        $dati = $this->saldoPrenotazione($prenotazione);

        $anticipata = $oggi->lt($prenotazione->data_partenza);
        $inRitardo  = $oggi->gt($prenotazione->data_partenza);
        $ridotto = $anticipata ? $this->calcolaRidotto($prenotazione, $oggi) : null;

        // Quanto costa il soggiorno prenotato (camere + tassa) e quanto costerebbe fermandolo a oggi
        $prenotato = null;
        if ($anticipata) {
            $soggiornoPrenotato = 0.0;
            foreach ($prenotazione->camere as $camera) {
                $soggiornoPrenotato += (int) Carbon::parse($camera->pivot->data_arrivo)->diffInDays($camera->pivot->data_partenza) * (float) $camera->pivot->tariffa;
            }
            $prenotato = round($soggiornoPrenotato + $this->calcolaTassa((int) $prenotazione->adulti, (int) $prenotazione->data_arrivo->diffInDays($prenotazione->data_partenza)), 2);
        }

        $minibarOggi = DB::table('minibar_controlli')->where('prenotazione_id', $prenotazione->id)
            ->where('created_at', '>=', $oggi->toDateTimeString())->orderByDesc('id')->first();

        $fatture = $dati['conto'] ? DB::table('fatture')->where('conto_id', $dati['conto']->id)->get()->keyBy('folio') : collect();
        $daFatturare = collect($dati['riepilogo'])
            ->filter(fn ($f) => $f->addebiti > 0 && optional($fatture->get($f->chiave))->stato !== 'emessa')
            ->pluck('chiave');

        return view('frontdesk.checkout', [
            'prenotazione' => $prenotazione,
            'conto'        => $dati['conto'],
            'riepilogo'    => $dati['riepilogo'],
            'saldo'        => $dati['saldo'],
            'anticipata'   => $anticipata,
            'inRitardo'    => $inRitardo,
            'ridotto'      => $ridotto,
            'prenotato'    => $prenotato,
            'minibarOggi'  => $minibarOggi,
            'daFatturare'  => $daFatturare,
            'camera'       => $prenotazione->camere->first(),
        ]);
    }

    public function checkoutConferma(Request $request, Prenotazione $prenotazione)
    {
        $prenotazione->load('camere');

        if ($prenotazione->stato !== 'in_casa') {
            return redirect()->route('prenotazioni.scheda', $prenotazione)
                ->withErrors(['checkout' => 'Il check-out si fa solo per un ospite in casa.']);
        }

        $dati = $request->validate([
            'motivo'       => 'nullable|string|max:255',
            'notti'        => 'nullable|in:tieni_tutte,solo_usate',
            'saldo_aperto' => 'nullable|boolean',
            'senza_minibar' => 'nullable|boolean',
        ]);

        $oggi = Carbon::today();
        $anticipata = $oggi->lt($prenotazione->data_partenza);
        $soloUsate = $anticipata && ($dati['notti'] ?? 'tieni_tutte') === 'solo_usate';

        if ($anticipata && trim((string) ($dati['motivo'] ?? '')) === '') {
            return back()->withInput()->withErrors(['checkout' => 'Partenza anticipata: scrivi il motivo (serve per lo storico e per eventuali contestazioni).']);
        }

        $conto = $this->contoDi($prenotazione);

        // Notti non usate: si possono togliere dal conto solo se nessun folio ha già una fattura emessa
        if ($soloUsate && $conto) {
            foreach (array_keys(self::FOLIO) as $f) {
                if ($this->folioBloccato($conto->id, $f)) {
                    return back()->withInput()->withErrors(['checkout' => 'Il folio ' . $f . ' ha già una fattura emessa: non si possono togliere le notti non usate. Scegli di addebitare tutte le notti.']);
                }
            }
        }

        $controllatoOggi = DB::table('minibar_controlli')->where('prenotazione_id', $prenotazione->id)
            ->where('created_at', '>=', $oggi->toDateTimeString())->exists();
        if (! $controllatoOggi && empty($dati['senza_minibar'])) {
            return back()->withInput()->withErrors(['checkout' => 'Il minibar non è stato controllato oggi: fai controllare la camera, oppure spunta "Procedi senza controllo minibar".']);
        }

        // Il saldo si valuta DOPO l'eventuale riduzione delle notti, quindi si applica prima la riduzione in transazione
        $ridotto = $soloUsate ? $this->calcolaRidotto($prenotazione, $oggi) : null;

        // Se qualcosa non torna (es. saldo aperto non confermato) si lancia un'eccezione: la transazione
        // torna indietro e anche l'eventuale accorciamento delle notti viene annullato.
        try {
            $esito = DB::transaction(function () use ($prenotazione, $conto, $ridotto, $soloUsate, $dati, $anticipata) {
            if ($soloUsate) {
                // Accorcia ogni camera e la prenotazione
                foreach ($ridotto['per_camera'] as $cameraId => $r) {
                    DB::table('prenotazione_camera')->where('prenotazione_id', $prenotazione->id)->where('camera_id', $cameraId)
                        ->update(['data_partenza' => $r['fine'], 'updated_at' => now()]);
                }
                $prenotazione->data_partenza = $ridotto['partenza'];
                $prenotazione->importo_totale = $ridotto['soggiorno'] + $ridotto['tassa'];
                $prenotazione->save();

                if ($conto) {
                    // Aggiorna le voci camera e la tassa già nel conto
                    $voci = DB::table('voci_conto')->where('conto_id', $conto->id)->get();
                    foreach ($voci->where('tipo', 'addebito_camera') as $v) {
                        if (preg_match('/^Camera (.+?) ·/u', $v->descrizione, $m)) {
                            $riga = collect($ridotto['per_camera'])->first(fn ($r) => (string) $r['numero'] === $m[1]);
                            $cam = $prenotazione->camere->first(fn ($c) => (string) $c->numero === $m[1]);
                            if ($riga && $cam) {
                                DB::table('voci_conto')->where('id', $v->id)->update([
                                    'descrizione' => 'Camera ' . $m[1] . ' · ' . $riga['notti'] . ' ' . ($riga['notti'] === 1 ? 'notte' : 'notti'),
                                    'importo'     => $riga['notti'] * (float) $cam->pivot->tariffa,
                                    'updated_at'  => now(),
                                ]);
                            }
                        }
                    }
                    $tassaVoce = $voci->first(fn ($v) => $v->tipo === 'servizio' && $v->descrizione === 'Tassa di soggiorno');
                    if ($tassaVoce) {
                        if ($ridotto['tassa'] > 0) {
                            DB::table('voci_conto')->where('id', $tassaVoce->id)->update(['importo' => $ridotto['tassa'], 'updated_at' => now()]);
                        } else {
                            DB::table('voci_conto')->where('id', $tassaVoce->id)->delete();
                        }
                    }
                    $this->aggiornaSaldoConto($conto->id);
                }
            }

            // Saldo dopo la riduzione
            $prenotazione->unsetRelation('camere')->load('camere');
            $s = $this->saldoPrenotazione($prenotazione);
            if (abs($s['saldo']) > 0.005 && empty($dati['saldo_aperto'])) {
                throw new \DomainException('Il conto ha un saldo di € ' . number_format($s['saldo'], 2, ',', '.') . ($s['saldo'] > 0 ? ' da incassare' : ' a credito dell\'ospite') . ': incassalo dal folio, oppure spunta "Lascia il conto aperto" per partire comunque.');
            }

            // Saldo da incassare ma conto mai aperto: lo si apre ora, così il debito resta registrato
            $contoId = $s['conto']->id ?? null;
            if (! $contoId && abs($s['saldo']) > 0.005) {
                $contoId = $this->apriContoPer($prenotazione);
            }
            if ($contoId && abs($s['saldo']) <= 0.005) {
                DB::table('conti')->where('id', $contoId)->update(['stato' => 'chiuso', 'chiuso_il' => now(), 'saldo' => 0, 'updated_at' => now()]);
            }

            $prenotazione->update([
                'stato'         => 'partita',
                'uscita_il'     => now(),
                'uscita_motivo' => $anticipata ? trim($dati['motivo']) : null,
            ]);

            // La camera si libera e va pulita (a meno che sia già in manutenzione)
            DB::table('camere')->whereIn('id', $prenotazione->camere->pluck('id'))
                ->where('stato_pulizia', '!=', 'manutenzione')
                ->update(['stato_pulizia' => 'sporca', 'updated_at' => now()]);

            return ['saldo' => $s['saldo']];
            });
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['checkout' => $e->getMessage()]);
        }

        $msg = 'Check-out effettuato: la camera è libera e da pulire.';
        if (abs($esito['saldo']) > 0.005) {
            $msg .= ' Il conto resta aperto con un saldo di € ' . number_format($esito['saldo'], 2, ',', '.') . '.';
        }

        return redirect()->route('prenotazioni.scheda', $prenotazione)->with('ok', $msg);
    }

    // ======================================================================
    // FOLIO / CONTO
    // Il conto si divide in due folio (A = ospite, B = extra): le voci si trascinano
    // da uno all'altro, ogni folio ha il suo saldo e la sua fattura (differita o emessa).
    // ======================================================================

    /** Folio in cui si divide il conto */
    private const FOLIO = ['A' => 'Ospite', 'B' => 'Extra'];

    /** Voci che riducono il saldo (incassi); tutte le altre lo aumentano */
    private const TIPI_CREDITO = ['pagamento', 'caparra'];

    public function contoDi(Prenotazione $prenotazione)
    {
        return DB::table('conti')->where('prenotazione_id', $prenotazione->id)->first();
    }

    /** Voci e totali di ciascun folio: [chiave => {chiave, nome, voci, addebiti, crediti, saldo}] */
    private function folioRiepilogo(int $contoId): array
    {
        $voci = DB::table('voci_conto')->where('conto_id', $contoId)
            ->orderBy('registrata_il')->orderBy('id')->get();

        $out = [];
        foreach (self::FOLIO as $chiave => $nome) {
            $v = $voci->where('folio', $chiave)->values();
            $addebiti = $v->reject(fn ($x) => in_array($x->tipo, self::TIPI_CREDITO, true))->sum('importo');
            $crediti  = $v->filter(fn ($x) => in_array($x->tipo, self::TIPI_CREDITO, true))->sum('importo');
            $out[$chiave] = (object) [
                'chiave'   => $chiave,
                'nome'     => $nome,
                'voci'     => $v,
                'addebiti' => round($addebiti, 2),
                'crediti'  => round($crediti, 2),
                'saldo'    => round($addebiti - $crediti, 2),
            ];
        }
        return $out;
    }

    /** Tiene allineato conti.saldo (somma dei saldi dei folio) a ogni modifica delle voci. */
    public function aggiornaSaldoConto(int $contoId): void
    {
        $saldo = collect($this->folioRiepilogo($contoId))->sum('saldo');
        DB::table('conti')->where('id', $contoId)->update(['saldo' => $saldo, 'updated_at' => now()]);
    }

    /** Un folio con fattura emessa è bloccato: niente voci, spostamenti o incassi. */
    public function folioBloccato(int $contoId, string $folio): bool
    {
        return DB::table('fatture')->where('conto_id', $contoId)
            ->where('folio', $folio)->where('stato', 'emessa')->exists();
    }

    public function folio(Prenotazione $prenotazione)
    {
        $prenotazione->load('ospite', 'camere');

        $conto = $this->contoDi($prenotazione);
        $riepilogo = $conto ? $this->folioRiepilogo($conto->id) : [];
        $fatture = $conto ? DB::table('fatture')->where('conto_id', $conto->id)->get()->keyBy('folio') : collect();
        $saldo = collect($riepilogo)->sum('saldo');
        $listino = DB::table('minibar_articoli')->where('attivo', true)->orderBy('categoria')->orderBy('nome')->get();

        return view('frontdesk.folio', compact('prenotazione', 'conto', 'riepilogo', 'fatture', 'saldo', 'listino'));
    }

    public function folioApri(Prenotazione $prenotazione)
    {
        if ($this->contoDi($prenotazione)) {
            return back()->withErrors(['folio' => 'Il conto è già aperto.']);
        }

        $this->apriContoPer($prenotazione);

        return redirect()->route('prenotazioni.folio', $prenotazione)->with('ok', 'Conto aperto.');
    }

    /**
     * Apre il conto con camera, tassa di soggiorno e caparra. Ritorna l'id del conto.
     * Lo chiamano il check-in (in automatico), il pulsante "Apri conto" del folio, il check-out e il controllo minibar.
     * È sicuro chiamarlo più volte: se il conto c'è già, restituisce quello senza crearne un secondo.
     * Ogni voce porta la sua `origine`, così riallineaConto() la ritrova quando la prenotazione cambia.
     */
    public function apriContoPer(Prenotazione $prenotazione): int
    {
        if ($esistente = $this->contoDi($prenotazione)) {
            return (int) $esistente->id;
        }

        $prenotazione->loadMissing('camere');

        $contoId = DB::transaction(function () use ($prenotazione) {
            $contoId = DB::table('conti')->insertGetId([
                'prenotazione_id' => $prenotazione->id,
                'codice'          => 'C-' . now()->format('ymd') . '-' . Str::upper(Str::random(4)),
                'stato'           => 'aperto',
                'saldo'           => 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $voce = fn (string $tipo, string $descrizione, $importo, string $origine) => DB::table('voci_conto')->insert([
                'conto_id'      => $contoId,
                'folio'         => 'A',
                'tipo'          => $tipo,
                'descrizione'   => $descrizione,
                'importo'       => $importo,
                'origine'       => $origine,
                'registrata_il' => now(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Addebito camera: una voce per ogni assegnazione camera della prenotazione
            foreach ($prenotazione->camere as $camera) {
                $notti = (int) Carbon::parse($camera->pivot->data_arrivo)->diffInDays($camera->pivot->data_partenza);
                $voce('addebito_camera',
                    'Camera ' . $camera->numero . ' · ' . $notti . ' ' . ($notti === 1 ? 'notte' : 'notti'),
                    $notti * $camera->pivot->tariffa,
                    'camera:' . $camera->id);
            }

            // Tassa di soggiorno (dalla configurazione hotel, sugli adulti)
            $nottiTotali = (int) $prenotazione->data_arrivo->diffInDays($prenotazione->data_partenza);
            $tassa = round($this->calcolaTassa((int) $prenotazione->adulti, $nottiTotali), 2);
            if ($tassa > 0) {
                $voce('servizio', 'Tassa di soggiorno', $tassa, 'tassa');
            }

            if ($prenotazione->importo_caparra > 0) {
                $voce('caparra', 'Caparra versata alla prenotazione', $prenotazione->importo_caparra, 'caparra');
            }

            return $contoId;
        });

        $this->aggiornaSaldoConto($contoId);

        return $contoId;
    }

    /**
     * Riporta nel conto APERTO le voci che dipendono dalla prenotazione (camere, tassa di soggiorno, caparra)
     * dopo uno spostamento, un cambio di tariffa, di adulti o di caparra.
     * Le voci si riconoscono dalla colonna `origine`; nei conti creati prima di questa colonna, dalla descrizione.
     * Le voci di un folio con fattura emessa non si toccano. Se un conto è chiuso o qualcosa non si è potuto
     * aggiornare, ritorna un messaggio da mostrare all'operatore; altrimenti null (anche se non c'è alcun conto).
     */
    public function riallineaConto(Prenotazione $prenotazione): ?string
    {
        $conto = $this->contoDi($prenotazione);
        if (! $conto) {
            return null;
        }
        if ($conto->stato !== 'aperto') {
            return 'Il conto è già chiuso: i suoi importi non sono stati modificati.';
        }

        $prenotazione = $prenotazione->fresh(['camere']);
        $saltate = 0;

        DB::transaction(function () use ($prenotazione, $conto, &$saltate) {
            $bloccato = [];
            foreach (array_keys(self::FOLIO) as $f) {
                $bloccato[$f] = $this->folioBloccato($conto->id, $f);
            }

            $voci = DB::table('voci_conto')->where('conto_id', $conto->id)->get();

            $aggiorna = function ($voce, array $campi) use ($bloccato, &$saltate) {
                if ($bloccato[$voce->folio] ?? false) {
                    $saltate++;
                    return;
                }
                DB::table('voci_conto')->where('id', $voce->id)->update($campi + ['updated_at' => now()]);
            };
            $marca = function ($voce, string $origine) use ($bloccato) {
                if (! ($bloccato[$voce->folio] ?? false)) {
                    DB::table('voci_conto')->where('id', $voce->id)->update(['origine' => $origine, 'updated_at' => now()]);
                }
            };
            $elimina = function ($voce) use ($bloccato, &$saltate) {
                if ($bloccato[$voce->folio] ?? false) {
                    $saltate++;
                    return;
                }
                DB::table('voci_conto')->where('id', $voce->id)->delete();
            };
            $inserisci = function (string $tipo, string $descrizione, $importo, string $origine) use ($conto, $bloccato, &$saltate) {
                if ($bloccato['A'] ?? false) {
                    $saltate++;
                    return;
                }
                DB::table('voci_conto')->insert([
                    'conto_id'      => $conto->id,
                    'folio'         => 'A',
                    'tipo'          => $tipo,
                    'descrizione'   => $descrizione,
                    'importo'       => $importo,
                    'origine'       => $origine,
                    'registrata_il' => now(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            };

            // ---- Camere: una voce per assegnazione ----
            $vociCamera = $voci->where('tipo', 'addebito_camera')->values();
            $usate = [];
            $senzaVoce = [];

            foreach ($prenotazione->camere as $camera) {
                $notti = (int) Carbon::parse($camera->pivot->data_arrivo)->diffInDays($camera->pivot->data_partenza);
                $descr = 'Camera ' . $camera->numero . ' · ' . $notti . ' ' . ($notti === 1 ? 'notte' : 'notti');
                $importo = round($notti * (float) $camera->pivot->tariffa, 2);
                $origine = 'camera:' . $camera->id;

                $voce = $vociCamera->first(function ($v) use ($usate, $origine, $camera) {
                    if (in_array($v->id, $usate, true)) {
                        return false;
                    }
                    if ($v->origine === $origine) {
                        return true;
                    }
                    // conto vecchio, senza origine: si riconosce dal numero camera nella descrizione
                    return $v->origine === null
                        && preg_match('/^Camera (.+?) ·/u', $v->descrizione, $m)
                        && (string) $m[1] === (string) $camera->numero;
                });

                if (! $voce) {
                    $senzaVoce[] = ['descr' => $descr, 'importo' => $importo, 'origine' => $origine];
                    continue;
                }

                $usate[] = $voce->id;
                if ($voce->descrizione !== $descr || round((float) $voce->importo, 2) !== $importo) {
                    $aggiorna($voce, ['descrizione' => $descr, 'importo' => $importo, 'origine' => $origine]);
                } elseif ($voce->origine !== $origine) {
                    $marca($voce, $origine);
                }
            }

            // Una camera cambiata = una voce "orfana" (la vecchia camera) e una camera senza voce (la nuova):
            // si abbinano, così la voce resta nel suo folio e cambia solo camera e importo.
            $orfane = $vociCamera->reject(fn ($v) => in_array($v->id, $usate, true))->values();
            $abbinate = min($orfane->count(), count($senzaVoce));
            for ($i = 0; $i < $abbinate; $i++) {
                $aggiorna($orfane[$i], [
                    'descrizione' => $senzaVoce[$i]['descr'],
                    'importo'     => $senzaVoce[$i]['importo'],
                    'origine'     => $senzaVoce[$i]['origine'],
                ]);
            }
            foreach ($orfane->slice($abbinate) as $v) {
                $elimina($v);
            }
            foreach (array_slice($senzaVoce, $abbinate) as $s) {
                $inserisci('addebito_camera', $s['descr'], $s['importo'], $s['origine']);
            }

            // ---- Tassa di soggiorno ----
            $nottiTotali = (int) $prenotazione->data_arrivo->diffInDays($prenotazione->data_partenza);
            $tassa = round($this->calcolaTassa((int) $prenotazione->adulti, $nottiTotali), 2);
            $vTassa = $voci->first(fn ($v) => $v->origine === 'tassa'
                || ($v->origine === null && $v->tipo === 'servizio' && $v->descrizione === 'Tassa di soggiorno'));

            if ($vTassa && $tassa > 0) {
                if (round((float) $vTassa->importo, 2) !== $tassa) {
                    $aggiorna($vTassa, ['importo' => $tassa, 'origine' => 'tassa']);
                } elseif ($vTassa->origine !== 'tassa') {
                    $marca($vTassa, 'tassa');
                }
            } elseif ($vTassa) {
                $elimina($vTassa);
            } elseif ($tassa > 0) {
                $inserisci('servizio', 'Tassa di soggiorno', $tassa, 'tassa');
            }

            // ---- Caparra versata alla prenotazione ----
            $caparra = round((float) $prenotazione->importo_caparra, 2);
            $vCaparra = $voci->first(fn ($v) => $v->origine === 'caparra'
                || ($v->origine === null && $v->tipo === 'caparra' && $v->descrizione === 'Caparra versata alla prenotazione'));

            if ($vCaparra && $caparra > 0) {
                if (round((float) $vCaparra->importo, 2) !== $caparra) {
                    $aggiorna($vCaparra, ['importo' => $caparra, 'origine' => 'caparra']);
                } elseif ($vCaparra->origine !== 'caparra') {
                    $marca($vCaparra, 'caparra');
                }
            } elseif ($vCaparra) {
                $elimina($vCaparra);
            } elseif ($caparra > 0) {
                $inserisci('caparra', 'Caparra versata alla prenotazione', $caparra, 'caparra');
            }
        });

        $this->aggiornaSaldoConto($conto->id);

        return $saltate > 0
            ? 'Attenzione: alcune voci del conto non sono state aggiornate perché il folio ha già una fattura emessa. Controlla il folio.'
            : null;
    }

    public function folioAggiungiVoce(Request $request, Prenotazione $prenotazione)
    {
        $dati = $request->validate([
            'descrizione' => 'required|string|max:255',
            'importo'     => 'required|numeric|min:0.01',
            'folio'       => 'nullable|in:A,B',
        ]);
        $folio = $dati['folio'] ?? 'B';

        $conto = $this->contoDi($prenotazione);
        if (! $conto || $conto->stato !== 'aperto') {
            return back()->withErrors(['folio' => 'Il conto non è aperto.']);
        }
        if ($this->folioBloccato($conto->id, $folio)) {
            return back()->withErrors(['folio' => 'Il folio ' . $folio . ' ha una fattura emessa: non si può modificare.']);
        }

        DB::table('voci_conto')->insert([
            'conto_id'      => $conto->id,
            'folio'         => $folio,
            'tipo'          => 'servizio',
            'descrizione'   => $dati['descrizione'],
            'importo'       => $dati['importo'],
            'registrata_il' => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        $this->aggiornaSaldoConto($conto->id);

        return back()->with('ok', 'Voce aggiunta al folio ' . $folio . '.');
    }

    public function folioRegistraPagamento(Request $request, Prenotazione $prenotazione)
    {
        $dati = $request->validate([
            'metodo'  => 'required|in:carta,contanti,bonifico',
            'importo' => 'required|numeric|min:0.01',
            'folio'   => 'nullable|in:A,B',
        ]);
        $folio = $dati['folio'] ?? 'A';

        $conto = $this->contoDi($prenotazione);
        if (! $conto || $conto->stato !== 'aperto') {
            return back()->withErrors(['folio' => 'Il conto non è aperto.']);
        }
        if ($this->folioBloccato($conto->id, $folio)) {
            return back()->withErrors(['folio' => 'Il folio ' . $folio . ' ha una fattura emessa: non si può modificare.']);
        }

        $saldoFolio = $this->folioRiepilogo($conto->id)[$folio]->saldo;
        if ((float) $dati['importo'] > $saldoFolio + 0.005) {
            return back()->withErrors(['folio' => 'Il pagamento (€ ' . number_format($dati['importo'], 2, ',', '.') . ') supera il saldo del folio ' . $folio . ' (€ ' . number_format($saldoFolio, 2, ',', '.') . ').']);
        }

        DB::table('voci_conto')->insert([
            'conto_id'      => $conto->id,
            'folio'         => $folio,
            'tipo'          => 'pagamento',
            'descrizione'   => 'Pagamento · ' . ucfirst($dati['metodo']),
            'importo'       => $dati['importo'],
            'registrata_il' => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        $this->aggiornaSaldoConto($conto->id);

        return back()->with('ok', 'Pagamento registrato sul folio ' . $folio . '.');
    }

    /** Sposta una voce da un folio all'altro (drag & drop dalla pagina folio, risponde JSON). */
    public function folioSpostaVoce(Request $request, Prenotazione $prenotazione, int $voce)
    {
        $dati = $request->validate(['folio' => 'required|in:A,B']);

        $rifiuta = fn (string $msg) => $request->expectsJson()
            ? response()->json(['message' => $msg], 422)
            : back()->withErrors(['folio' => $msg]);

        $conto = $this->contoDi($prenotazione);
        $riga = $conto ? DB::table('voci_conto')->where('id', $voce)->where('conto_id', $conto->id)->first() : null;
        if (! $riga) {
            return $rifiuta('Voce non trovata: ricarica la pagina.');
        }
        if ($conto->stato !== 'aperto') {
            return $rifiuta('Il conto è chiuso.');
        }
        if (in_array($riga->tipo, self::TIPI_CREDITO, true)) {
            return $rifiuta('I pagamenti e la caparra restano nel folio in cui sono stati incassati.');
        }
        if ($riga->folio === $dati['folio']) {
            return $request->expectsJson() ? response()->json(['ok' => true]) : back();
        }
        foreach ([$riga->folio, $dati['folio']] as $f) {
            if ($this->folioBloccato($conto->id, $f)) {
                return $rifiuta('Il folio ' . $f . ' ha una fattura emessa: non si può modificare.');
            }
        }

        DB::table('voci_conto')->where('id', $riga->id)->update(['folio' => $dati['folio'], 'updated_at' => now()]);

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('ok', 'Voce spostata nel folio ' . $dati['folio'] . '.');
    }

    /** Storna una spesa (es. minibar addebitato per errore) con una rettifica negativa nello stesso folio. */
    public function folioStornaVoce(Prenotazione $prenotazione, int $voce)
    {
        $conto = $this->contoDi($prenotazione);
        $riga = $conto ? DB::table('voci_conto')->where('id', $voce)->where('conto_id', $conto->id)->first() : null;
        if (! $riga) {
            return back()->withErrors(['folio' => 'Voce non trovata.']);
        }
        if ($conto->stato !== 'aperto') {
            return back()->withErrors(['folio' => 'Il conto è chiuso.']);
        }
        if ($riga->tipo !== 'servizio') {
            return back()->withErrors(['folio' => 'Si possono stornare solo le spese (servizi, minibar...).']);
        }
        if ($this->folioBloccato($conto->id, $riga->folio)) {
            return back()->withErrors(['folio' => 'Il folio ' . $riga->folio . ' ha una fattura emessa: non si può modificare.']);
        }
        $tag = 'storno:' . $riga->id;
        if (DB::table('voci_conto')->where('conto_id', $conto->id)->where('riferimento', 'like', $tag . '%')->exists()) {
            return back()->withErrors(['folio' => 'Questa voce è già stata stornata.']);
        }

        // l'aliquota IVA della voce originale (se nota) viaggia nel riferimento
        $iva = preg_match('/:(\d+(?:\.\d+)?)$/', (string) $riga->riferimento, $m) ? ':' . $m[1] : '';

        DB::table('voci_conto')->insert([
            'conto_id'      => $conto->id,
            'folio'         => $riga->folio,
            'tipo'          => 'rettifica',
            'descrizione'   => 'Storno · ' . $riga->descrizione,
            'importo'       => -1 * (float) $riga->importo,
            'riferimento'   => $tag . $iva,
            'registrata_il' => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        $this->aggiornaSaldoConto($conto->id);

        return back()->with('ok', 'Voce stornata.');
    }

    /** Unisce i due conti: tutte le voci del folio B passano nel folio A. */
    public function folioUnisci(Prenotazione $prenotazione)
    {
        $conto = $this->contoDi($prenotazione);
        if (! $conto || $conto->stato !== 'aperto') {
            return back()->withErrors(['folio' => 'Il conto non è aperto.']);
        }
        foreach (array_keys(self::FOLIO) as $f) {
            if ($this->folioBloccato($conto->id, $f)) {
                return back()->withErrors(['folio' => 'Il folio ' . $f . ' ha una fattura emessa: non si possono unire i conti.']);
            }
        }

        DB::transaction(function () use ($conto) {
            DB::table('voci_conto')->where('conto_id', $conto->id)->where('folio', 'B')
                ->update(['folio' => 'A', 'updated_at' => now()]);
            // Una fattura differita del folio B non ha più senso
            DB::table('fatture')->where('conto_id', $conto->id)->where('folio', 'B')->where('stato', 'differita')->delete();
        });

        return back()->with('ok', 'Conti uniti: tutte le voci sono nel folio A.');
    }

    /** Emette la fattura del folio oppure la segna come differita (da emettere in seguito). */
    public function folioFatturaEmetti(Request $request, Prenotazione $prenotazione, string $folio)
    {
        abort_unless(isset(self::FOLIO[$folio]), 404);

        $dati = $request->validate([
            'azione'              => 'required|in:emetti,differisci',
            'intestatario'        => 'required_if:azione,emetti|nullable|string|max:150',
            'partita_iva'         => 'nullable|string|max:20',
            'codice_fiscale'      => 'nullable|string|max:20',
            'codice_destinatario' => 'nullable|alpha_num|size:7',
            'pec'                 => 'nullable|email|max:150',
            'indirizzo'           => 'nullable|string|max:255',
        ], [
            'intestatario.required_if' => 'Per emettere la fattura serve l\'intestatario.',
        ]);

        $conto = $this->contoDi($prenotazione);
        if (! $conto) {
            return back()->withErrors(['folio' => 'Nessun conto aperto per questa prenotazione.']);
        }

        $piva = strtoupper(preg_replace('/\s+/', '', (string) ($dati['partita_iva'] ?? '')));
        $piva = preg_replace('/^IT/', '', $piva);
        $cf   = strtoupper(preg_replace('/\s+/', '', (string) ($dati['codice_fiscale'] ?? '')));

        if ($piva !== '' && ! preg_match('/^\d{11}$/', $piva)) {
            return back()->withErrors(['folio' => 'Partita IVA non valida: servono 11 cifre.']);
        }
        if ($cf !== '' && ! preg_match('/^([A-Z0-9]{16}|\d{11})$/', $cf)) {
            return back()->withErrors(['folio' => 'Codice fiscale non valido: 16 caratteri (persona) oppure 11 cifre (società).']);
        }
        if ($dati['azione'] === 'emetti' && $piva === '' && $cf === '') {
            return back()->withErrors(['folio' => 'Per emettere la fattura inserisci la partita IVA o il codice fiscale dell\'intestatario.']);
        }

        $f = $this->folioRiepilogo($conto->id)[$folio];
        if ($f->addebiti <= 0) {
            return back()->withErrors(['folio' => 'Il folio ' . $folio . ' non ha voci da fatturare.']);
        }

        $esistente = DB::table('fatture')->where('conto_id', $conto->id)->where('folio', $folio)->first();
        if ($esistente && $esistente->stato === 'emessa') {
            return back()->withErrors(['folio' => 'Per il folio ' . $folio . ' la fattura n. ' . $esistente->numero . ' è già stata emessa.']);
        }

        DB::transaction(function () use ($dati, $conto, $folio, $f, $esistente, $piva, $cf) {
            $record = [
                'intestatario'        => $dati['intestatario'] ?? null,
                'partita_iva'         => $piva !== '' ? $piva : null,
                'codice_fiscale'      => $cf !== '' ? $cf : null,
                'codice_destinatario' => ! empty($dati['codice_destinatario']) ? strtoupper($dati['codice_destinatario']) : null,
                'pec'                 => $dati['pec'] ?? null,
                'indirizzo'           => $dati['indirizzo'] ?? null,
                'importo_totale'      => $f->addebiti,
                'updated_at'          => now(),
            ];

            if ($dati['azione'] === 'emetti') {
                // Numerazione progressiva annuale, senza buchi né doppioni
                $anno = (int) now()->year;
                $prog = (int) DB::table('fatture')->where('anno', $anno)->lockForUpdate()->max('progressivo') + 1;
                $record += [
                    'stato'          => 'emessa',
                    'anno'           => $anno,
                    'progressivo'    => $prog,
                    'numero'         => $prog . '/' . $anno,
                    'data_emissione' => now()->toDateString(),
                ];
            } else {
                $record['stato'] = 'differita';
            }

            if ($esistente) {
                DB::table('fatture')->where('id', $esistente->id)->update($record);
            } else {
                DB::table('fatture')->insert($record + ['conto_id' => $conto->id, 'folio' => $folio, 'created_at' => now()]);
            }
        });

        if ($dati['azione'] === 'emetti') {
            return redirect()->route('prenotazioni.folio.fattura', [$prenotazione, $folio])->with('ok', 'Fattura emessa.');
        }
        return back()->with('ok', 'Folio ' . $folio . ' segnato come fattura differita.');
    }

    /** Fattura emessa, pronta da stampare / salvare in PDF dal browser. */
    public function folioFatturaVedi(Prenotazione $prenotazione, string $folio)
    {
        abort_unless(isset(self::FOLIO[$folio]), 404);

        $conto = $this->contoDi($prenotazione);
        $fattura = $conto
            ? DB::table('fatture')->where('conto_id', $conto->id)->where('folio', $folio)->where('stato', 'emessa')->first()
            : null;
        abort_unless($fattura, 404);

        $cfg = $this->configHotel();
        $emittente = $cfg['fatturazione'] ?? [];
        $aliqAlloggio = (float) ($emittente['aliquota_iva_alloggio'] ?? 10);
        $aliqDefault  = (float) ($emittente['aliquota_iva_default'] ?? 22);

        $f = $this->folioRiepilogo($conto->id)[$folio];

        // I prezzi del folio sono IVA inclusa: si scorpora l'IVA riga per riga.
        // La tassa di soggiorno è fuori campo IVA.
        $righe = $f->voci
            ->reject(fn ($v) => in_array($v->tipo, self::TIPI_CREDITO, true))
            ->map(function ($v) use ($aliqAlloggio, $aliqDefault) {
                $tassa = str_contains(mb_strtolower($v->descrizione), 'tassa di soggiorno');
                $aliq  = $tassa ? 0.0 : ($v->tipo === 'addebito_camera' ? $aliqAlloggio : $aliqDefault);
                // minibar e storni portano la loro aliquota nel riferimento ("minibar:12:22")
                if (! $tassa && preg_match('/:(\d+(?:\.\d+)?)$/', (string) $v->riferimento, $m)) {
                    $aliq = (float) $m[1];
                }
                $lordo = (float) $v->importo;
                $imponibile = $aliq > 0 ? round($lordo / (1 + $aliq / 100), 2) : $lordo;
                return (object) [
                    'descrizione' => $v->descrizione,
                    'aliquota'    => $aliq,
                    'lordo'       => $lordo,
                    'imponibile'  => $imponibile,
                    'imposta'     => round($lordo - $imponibile, 2),
                ];
            })->values();

        $riepilogoIva = $righe->groupBy(fn ($r) => (string) $r->aliquota)
            ->map(fn ($g, $a) => (object) [
                'aliquota'   => (float) $a,
                'imponibile' => round($g->sum('imponibile'), 2),
                'imposta'    => round($g->sum('imposta'), 2),
            ])->sortKeys()->values();

        $prenotazione->load('ospite');

        return view('frontdesk.fattura', [
            'prenotazione' => $prenotazione,
            'fattura'      => $fattura,
            'emittente'    => $emittente,
            'hotel'        => $cfg['hotel'] ?? [],
            'righe'        => $righe,
            'riepilogoIva' => $riepilogoIva,
            'totale'       => round($righe->sum('lordo'), 2),
            'incassato'    => $f->crediti,
            'daPagare'     => $f->saldo,
        ]);
    }

    public function folioChiudi(Prenotazione $prenotazione)
    {
        $conto = $this->contoDi($prenotazione);
        if (! $conto) {
            return back()->withErrors(['folio' => 'Nessun conto da chiudere.']);
        }

        $saldo = collect($this->folioRiepilogo($conto->id))->sum('saldo');
        if (abs($saldo) > 0.01) {
            return back()->withErrors(['folio' => 'Il conto ha ancora un saldo di € ' . number_format($saldo, 2, ',', '.') . ': non può essere chiuso.']);
        }

        DB::table('conti')->where('id', $conto->id)->update(['stato' => 'chiuso', 'chiuso_il' => now(), 'saldo' => 0, 'updated_at' => now()]);

        return back()->with('ok', 'Conto chiuso.');
    }

    // ======================================================================
    // ADEMPIMENTI (Alloggiati Web, ISTAT) — pagina a livello hotel, non per singola prenotazione:
    // in un giorno normale ci sono più ospiti da inviare insieme, come nel mockup.
    // ======================================================================

    /** Il link "Adempimenti" di una scheda prenotazione porta qui, con quella camera evidenziata. */
    public function adempimentiVaiA(Prenotazione $prenotazione)
    {
        $camera = $prenotazione->camere->first();
        $redirect = redirect()->route('adempimenti.mensile');
        return $camera ? $redirect->withFragment('camera-' . $camera->numero) : $redirect;
    }

    /** Ospiti da trattare oggi: già in casa, o in arrivo oggi (non annullati né no-show). */
    private function prenotazioniAdempimenti()
    {
        $oggi = Carbon::today()->toDateString();

        return Prenotazione::with('ospite', 'accompagnatori', 'camere')
            ->whereNotIn('stato', self::STATI_ESCLUSI)
            ->where(function ($q) use ($oggi) {
                $q->where('stato', 'in_casa')->orWhereDate('data_arrivo', $oggi);
            })
            ->get()
            ->sortBy(fn ($p) => $p->camere->first()->numero ?? '')
            ->values();
    }

    private function invioMassivoDiOggi(): ?InvioAlloggiati
    {
        return InvioAlloggiati::where('tipo', InvioAlloggiati::MASSIVO)
            ->whereDate('data_riferimento', Carbon::today()->toDateString())
            ->latest('inviato_il')
            ->first();
    }

    public function adempimenti()
    {
        $oggi = Carbon::today()->toDateString();

        $prenotazioni = $this->prenotazioniAdempimenti();

        // Alloggiati Web: solo il giorno corrente (inviate + da inviare)
        $invioMassivoOggi = $this->invioMassivoDiOggi();
        $nCompletiDaInviare = $prenotazioni
            ->filter(fn ($p) => ! $p->alloggiati_inviato_il && $p->alloggiatiCompleto())
            ->count();

        // Solo invii di OGGI (massivo + singoli)
        $inviiOggi = InvioAlloggiati::whereDate('data_riferimento', $oggi)
            ->orderByDesc('inviato_il')
            ->get();

        // ISTAT: quanti giorni del mese in corso hanno già un invio registrato, sui giorni già trascorsi
        $inizioMese = Carbon::today()->startOfMonth()->toDateString();
        $giorniIstatInviati = Prenotazione::whereNotNull('istat_inviato_il')
            ->whereDate('istat_inviato_il', '>=', $inizioMese)
            ->get()
            ->map(fn ($p) => $p->istat_inviato_il->toDateString())
            ->unique()
            ->count();
        $giorniTrascorsiMese = Carbon::today()->day;
        $istatInviatoOggi = Prenotazione::whereDate('istat_inviato_il', $oggi)->exists();

// Mostra SOLO gli arrivi di oggi (sia già inviati che da inviare).
// Gli ospiti in casa da giorni precedenti non devono comparire qui: stanno nello Storico invii.
$prenotazioni = $prenotazioni->filter(function ($p) {
    return $p->data_arrivo && \Carbon\Carbon::parse($p->data_arrivo)->isSameDay(\Carbon\Carbon::today());
})->values();

return view('frontdesk.adempimenti', compact(
    'prenotazioni', 'invioMassivoOggi', 'nCompletiDaInviare', 'inviiOggi',
    'giorniIstatInviati', 'giorniTrascorsiMese', 'istatInviatoOggi'
));
    }

    /** Copia dei dati di una prenotazione come vanno comunicati: una voce per persona. */
    /**
     * Copia dei dati di una prenotazione come vanno comunicati ad Alloggiati Web:
     * una voce per persona, con tipo alloggiato e flag documento.
     *
     * Tipi (codici portale):
     *  16 singolo · 17 capofamiglia · 18 capogruppo · 19 familiare · 20 membro gruppo
     * Documento obbligatorio solo per 16/17/18.
     */
    private function schedineDi(Prenotazione $p): array
    {
        $camera = $p->camere->first()->numero ?? null;
        $persone = $p->persone();
        $nPersone = $persone->count();
        $giorni = $p->data_arrivo && $p->data_partenza
            ? (int) $p->data_arrivo->diffInDays($p->data_partenza)
            : null;

        return $persone->map(function ($r) use ($p, $camera, $nPersone, $giorni) {
            // Tipo alloggiato: 1 persona = singolo; titolare con altri = capofamiglia; resto = familiare
            if ($nPersone === 1) {
                $tipo = 16; // singolo
            } elseif ($r->titolare) {
                $tipo = 17; // capofamiglia (in hotel il caso più comune; capogruppo=18 se servirà un flag dedicato)
            } else {
                $tipo = 19; // familiare
            }

            // Documento nel file ufficiale solo per singolo / capofamiglia / capogruppo
            $includeDoc = in_array($tipo, [16, 17, 18], true);

            return [
                'prenotazione'             => $p->codice,
                'camera'                   => $camera,
                'ruolo'                    => $r->ruolo,
                'tipo_alloggiato'          => $tipo,
                'include_documento'        => $includeDoc,
                'cognome'                  => $r->ospite->cognome,
                'nome'                     => $r->ospite->nome,
                'sesso'                    => $r->ospite->sesso,
                'data_nascita'             => $r->ospite->data_nascita?->toDateString(),
                'luogo_nascita'            => $r->ospite->luogo_nascita,
                'luogo_nascita_provincia'  => $r->ospite->luogo_nascita_provincia,
                'nazionalita'              => $r->ospite->nazionalita,
                'tipo_documento'           => $includeDoc ? $r->ospite->tipo_documento : null,
                'numero_documento'         => $includeDoc ? $r->ospite->numero_documento : null,
                'luogo_rilascio'           => $includeDoc ? $r->ospite->luogo_rilascio : null,
                'luogo_rilascio_provincia' => $includeDoc ? $r->ospite->luogo_rilascio_provincia : null,
                'arrivo'                   => $p->data_arrivo?->toDateString(),
                'partenza'                 => $p->data_partenza?->toDateString(),
                'giorni_permanenza'        => $giorni,
            ];
        })->all();
    }

    /** Registra un invio con le prenotazioni indicate e le marca come inviate. */
    private function registraInvio(string $tipo, $prenotazioni): InvioAlloggiati
    {
        return DB::transaction(function () use ($tipo, $prenotazioni) {
            $invio = InvioAlloggiati::create([
                'tipo'             => $tipo,
                'data_riferimento' => Carbon::today()->toDateString(),
                'inviato_il'       => now(),
                'utente_id'        => auth()->id(),
                'schedine'         => $prenotazioni->flatMap(fn ($p) => $this->schedineDi($p))->values()->all(),
            ]);

            foreach ($prenotazioni as $p) {
                // Prova: registra solo l'invio, nessuna comunicazione verso l'esterno
                $p->update(['alloggiati_inviato_il' => $invio->inviato_il, 'invio_alloggiati_id' => $invio->id]);
            }

            return $invio;
        });
    }

    /** Invio massivo: tutte le schedine complete del giorno, in un unico invio. Una volta al giorno. */
    public function adempimentiInviaMassivo()
    {
        if ($this->invioMassivoDiOggi()) {
            return back()->withErrors(['adempimenti' => "L'invio massivo di oggi è già stato fatto: chi manca si invia singolarmente."]);
        }

        $daInviare = $this->prenotazioniAdempimenti()
            ->filter(fn ($p) => ! $p->alloggiati_inviato_il && $p->alloggiatiCompleto())
            ->values();

        if ($daInviare->isEmpty()) {
            return back()->withErrors(['adempimenti' => 'Nessuna schedina completa da inviare.']);
        }

        $invio = $this->registraInvio(InvioAlloggiati::MASSIVO, $daInviare);
        $n = count($invio->schedine);

        return back()->with('ok', 'Alloggiati Web: invio massivo registrato (' . $n . ($n === 1 ? ' schedina' : ' schedine') . ').');
    }

    /** Invio singolo: solo dopo l'invio massivo del giorno, per chi è arrivato o è stato completato dopo. */
    public function adempimentiInvia(Prenotazione $prenotazione)
    {
        $ospite = $prenotazione->ospite;

        if (! $this->invioMassivoDiOggi()) {
            return back()->withErrors(['adempimenti' => "Prima va fatto l'invio massivo di oggi: l'invio singolo serve per chi arriva dopo."]);
        }
        if ($prenotazione->alloggiati_inviato_il) {
            return back()->withErrors(['adempimenti' => 'Schedine di ' . $ospite->cognome . ' già inviate.']);
        }
        if (! $prenotazione->alloggiatiCompleto()) {
            return back()->withErrors(['adempimenti' => 'Dati incompleti per ' . $ospite->cognome . ': servono tutte le persone registrate, con data di nascita e nazionalità (e documento per gli adulti).']);
        }

        $this->registraInvio(InvioAlloggiati::SINGOLO, collect([$prenotazione]));

        return back()->with('ok', 'Alloggiati Web: invio singolo registrato per ' . $ospite->cognome . '.');
    }

    /** PDF dell'invio, da aprire in una nuova scheda. */
    public function adempimentiInvioPdf(InvioAlloggiati $invio)
    {
        $hotel = $this->configHotel()['hotel']['nome_commerciale'] ?? $this->configHotel()['hotel']['nome'] ?? 'Hotel';
        $nome = 'schedine-alloggiati-' . $invio->inviato_il->format('Ymd-Hi') . '.pdf';

        return response(SchedinePdf::genera($invio, $hotel), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nome . '"',
        ]);
    }

    /** File .txt nel tracciato ufficiale Alloggiati Web, da caricare a mano sul portale. */
    public function adempimentiInvioTxt(InvioAlloggiati $invio)
    {
        $contenuto = SchedineTxt::genera($invio);
        $nome = SchedineTxt::nomeFile($invio);

        return response($contenuto, 200, [
            'Content-Type'        => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nome . '"',
        ]);
    }

    /** Pagina storico invii con ricerca AJAX. */
    public function adempimentiStorico()
    {
        return view('frontdesk.adempimenti-storico');
    }

    /** Endpoint AJAX: ricerca invii su tutti i campi. */
    public function adempimentiStoricoSearch(Request $request)
    {
        $q    = trim((string) $request->get('q', ''));
        $dal  = $request->get('dal');
        $al   = $request->get('al');
        $tipo = $request->get('tipo');

        $query = InvioAlloggiati::query()->orderByDesc('inviato_il');

        if ($dal) {
            $query->whereDate('data_riferimento', '>=', $dal);
        }
        if ($al) {
            $query->whereDate('data_riferimento', '<=', $al);
        }
        if (in_array($tipo, ['massivo', 'singolo'], true)) {
            $query->where('tipo', $tipo);
        }

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function ($w) use ($like, $q) {
                $w->where('schedine', 'like', $like);
                if (is_numeric($q)) {
                    $w->orWhere('id', (int) $q);
                }
            });
        }

        $invii = $query->limit(100)->get()->map(function (InvioAlloggiati $i) {
            $nomi = collect($i->schedine ?? [])
                ->map(fn ($s) => trim(($s['cognome'] ?? '') . ' ' . ($s['nome'] ?? '')))
                ->filter()
                ->take(5)
                ->values()
                ->all();

            return [
                'id'               => $i->id,
                'tipo'             => $i->tipo,
                'data_riferimento' => $i->data_riferimento?->format('d/m/Y'),
                'inviato_il'       => $i->inviato_il?->format('d/m/Y H:i'),
                'n_schedine'       => count($i->schedine ?? []),
                'nomi'             => $nomi,
                'url_pdf'          => route('adempimenti.alloggiati.pdf', $i),
                'url_txt'          => route('adempimenti.alloggiati.txt', $i),
            ];
        });

        return response()->json(['data' => $invii]);
    }

    public function adempimentiInviaIstatOggi()
    {
        $oggi = Carbon::today()->toDateString();

        $daInviare = Prenotazione::whereNotIn('stato', self::STATI_ESCLUSI)
            ->where(fn ($q) => $q->where('stato', 'in_casa')->orWhereDate('data_arrivo', $oggi))
            ->whereNull('istat_inviato_il')
            ->get();

        foreach ($daInviare as $p) {
            $p->update(['istat_inviato_il' => now()]);
        }

        return back()->with('ok', 'ISTAT: movimento di oggi registrato (' . $daInviare->count() . ' schede).');
    }

    // ======================================================================
    // VISTA MENSILE DELL'OCCUPAZIONE
    // ======================================================================

    public function occupazioneMensile(Request $request)
    {
        try {
            $mese = $request->filled('mese')
                ? Carbon::parse($request->query('mese') . '-01')
                : Carbon::today()->startOfMonth();
        } catch (\Throwable $e) {
            $mese = Carbon::today()->startOfMonth();
        }
        $mese = $mese->startOfMonth();
        $fineMese = $mese->copy()->endOfMonth();

        $totaleCamere = Camera::where('attiva', true)->count();

        // Notti occupate per giorno: conto le assegnazioni camera che toccano ogni giorno del mese
        $assegnazioni = DB::table('prenotazione_camera as pc')
            ->join('prenotazioni as p', 'p.id', '=', 'pc.prenotazione_id')
            ->whereNull('p.deleted_at')
            ->whereNotIn('p.stato', self::STATI_ESCLUSI)
            ->where('pc.data_arrivo', '<', $fineMese->copy()->addDay()->toDateString())
            ->where('pc.data_partenza', '>', $mese->toDateString())
            ->select('pc.data_arrivo', 'pc.data_partenza')
            ->get();

        $giorni = [];
        for ($d = $mese->copy(); $d->lte($fineMese); $d->addDay()) {
            $occupate = 0;
            foreach ($assegnazioni as $a) {
                if ($a->data_arrivo <= $d->toDateString() && $a->data_partenza > $d->toDateString()) {
                    $occupate++;
                }
            }
            $giorni[] = (object) [
                'data'       => $d->copy(),
                'occupate'   => $occupate,
                'percentuale'=> $totaleCamere > 0 ? round($occupate / $totaleCamere * 100) : 0,
            ];
        }

        // Settimane complete (lun-dom) per disegnare il calendario a griglia
        $settimane = [];
        $cursore = $mese->copy()->startOfWeek(Carbon::MONDAY);
        $fineGriglia = $fineMese->copy()->endOfWeek(Carbon::SUNDAY);
        $perData = collect($giorni)->keyBy(fn ($g) => $g->data->toDateString());
        while ($cursore->lte($fineGriglia)) {
            $settimana = [];
            for ($i = 0; $i < 7; $i++) {
                $chiave = $cursore->toDateString();
                $settimana[] = (object) [
                    'data'        => $cursore->copy(),
                    'fuoriMese'   => ! $cursore->isSameMonth($mese),
                    'percentuale' => $perData->get($chiave)?->percentuale,
                    'occupate'    => $perData->get($chiave)?->occupate,
                ];
                $cursore->addDay();
            }
            $settimane[] = $settimana;
        }

        $mediaOccupazione = count($giorni) > 0 ? round(collect($giorni)->avg('percentuale')) : 0;

        return view('frontdesk.occupazione', compact('mese', 'settimane', 'totaleCamere', 'mediaOccupazione'));
    }

    public function index()
    {
        $camere = Camera::with("tipologia")->get();
        return view("camere.index", compact("camere"));
    }

    public function cercaOspiti(Request $request)
{
    $q = $request->input('q', '');
    if (strlen($q) < 2) {
        return response()->json([]);
    }

    // Cerca per nome o cognome che iniziano con la lettera digitata (o contengono)
    $ospiti = \App\Models\Ospite::where('nome', 'like', "%{$q}%")
        ->orWhere('cognome', 'like', "%{$q}%")
        ->limit(5)
        ->get(['id', 'nome', 'cognome', 'email', 'telefono']);

    return response()->json($ospiti);
}

// CONFIGURAZIONI SOFTWARE (solo ID 3)
public function configurazioniSoftware()
{
    abort_unless(auth()->id() === 3, 403);

    $cfg = $this->configHotel();
    $hotel = $cfg['hotel'] ?? [];
    $sistema = $cfg['sistema'] ?? [];
    $alloggiati = $cfg['alloggiati'] ?? [];
    $sicurezza = $cfg['sicurezza'] ?? [];

    return view('frontdesk.configurazioni-software', compact('hotel', 'sistema', 'alloggiati', 'sicurezza'));
}

public function salvaConfigurazioniSoftware(Request $request)
{
    abort_unless(auth()->id() === 3, 403);

    $dati = $request->validate([
        'app_url'             => 'nullable|url|max:255',
        'ambiente'            => 'nullable|in:locale,produzione',
        'timeout_sessione'    => 'nullable|integer|min:1|max:1440',
        'tentativi_login'     => 'nullable|integer|min:1|max:20',
        'ip_whitelist'        => 'nullable|string|max:1000',
        'manutenzione'        => 'nullable|boolean',
        'backup_attivo'       => 'nullable|boolean',
        'giorni_log'          => 'nullable|integer|min:1|max:365',
        'alloggiati_codice'   => 'nullable|string|max:50',
        'alloggiati_utente'   => 'nullable|string|max:100',
        'alloggiati_password' => 'nullable|string|max:100',
    ]);

    $percorso = config_path('hotel_config.json');
    $config = file_exists($percorso) ? json_decode(file_get_contents($percorso), true) ?? [] : [];

    $config['sistema'] = [
        'app_url'          => $dati['app_url'] ?? null,
        'ambiente'         => $dati['ambiente'] ?? 'locale',
        'timeout_sessione' => (int) ($dati['timeout_sessione'] ?? 30),
        'giorni_log'       => (int) ($dati['giorni_log'] ?? 30),
        'manutenzione'     => $request->boolean('manutenzione'),
        'backup_attivo'    => $request->boolean('backup_attivo'),
    ];

    $config['sicurezza'] = [
        'tentativi_login' => (int) ($dati['tentativi_login'] ?? 5),
        'ip_whitelist'    => $dati['ip_whitelist'] ?? null,
    ];

    $config['alloggiati'] = [
        'codice_struttura' => $dati['alloggiati_codice'] ?? null,
        'utente_portale'   => $dati['alloggiati_utente'] ?? null,
        'password_portale' => $dati['alloggiati_password'] ?? null,
    ];

    file_put_contents($percorso, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return back()->with('ok', 'Configurazioni software salvate.');
}

// LICENZE E MODULI 
public function licenzeModuli()
{

    // Tabella di esempio: sostituisci con la logica reale quando avrai un DB licenze
    $moduli = [
        (object) ['nome' => 'PMS Base', 'stato' => 'attivo', 'scadenza' => null, 'versione' => '1.0.0'],
        (object) ['nome' => 'Alloggiati Web', 'stato' => 'attivo', 'scadenza' => '31/12/2026', 'versione' => '1.2.0'],
        (object) ['nome' => 'ISTAT', 'stato' => 'attivo', 'scadenza' => '31/12/2026', 'versione' => '1.1.0'],
        (object) ['nome' => 'Bistro', 'stato' => 'in_scadenza', 'scadenza' => '15/11/2026', 'versione' => '2.0.1'],
        (object) ['nome' => 'Housekeeping', 'stato' => 'attivo', 'scadenza' => null, 'versione' => '1.0.3'],
        (object) ['nome' => 'Minibar', 'stato' => 'attivo', 'scadenza' => null, 'versione' => '1.0.2'],
    ];

    return view('frontdesk.licenze-moduli', compact('moduli'));
}
}
