<?php

namespace App\Http\Controllers;

use App\Support\MagazzinoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Magazzino & Fornitori (back-office): solo amministratore e direttore (i profili "gestore").
 * Bistro e Minibar producono gli scarichi (MagazzinoService); qui si amministrano scorte, carichi e ordini.
 */
class MagazzinoController extends Controller
{
    public const STATI_ORDINE = ['bozza' => 'Bozza', 'approvato' => 'Approvato', 'inviato' => 'Inviato', 'consegnato' => 'Consegnato', 'annullato' => 'Annullato'];
    public const SORGENTI = ['bistro' => 'Bistro', 'minibar' => 'Minibar', 'bar' => 'Bar', 'ordine' => 'Ordine fornitore', 'trasferimento' => 'Trasferimento', 'manuale' => 'Manuale'];

    private function consenti(): void
    {
        abort_unless(auth()->user()->isGestore(), 403);
    }

    // ======================================================================
    // SCORTE E ARTICOLI
    // ======================================================================

    public function index(Request $request)
    {
        $this->consenti();

        $q = trim((string) $request->query('q', ''));
        $soloSotto = $request->boolean('sotto');

        $query = DB::table('articoli_magazzino as a')->leftJoin('fornitori as f', 'f.id', '=', 'a.fornitore_id')
            ->when($q !== '', fn ($w) => $w->where('a.nome', 'like', '%' . $q . '%'))
            ->when($soloSotto, fn ($w) => $w->where('a.attivo', true)->whereNotNull('a.soglia_riordino')->whereColumn('a.scorta_attuale', '<=', 'a.soglia_riordino'))
            ->orderByDesc('a.attivo')->orderBy('a.nome')
            ->select('a.*', 'f.nome as fornitore');

        if (Schema::hasTable('giacenze_reparto')) {
            $query->selectSub(DB::table('giacenze_reparto as g')->whereColumn('g.articolo_id', 'a.id')->selectRaw('coalesce(sum(g.scorta), 0)'), 'nei_reparti');
        } else {
            $query->selectRaw('0 as nei_reparti');
        }

        $articoli = $query->get();

        $tutti = DB::table('articoli_magazzino')->where('attivo', true)->get();

        $daRifornire = collect();
        if (Schema::hasTable('reparti')) {
            $daRifornire = DB::table('reparti')->where('attivo', true)->get()->flatMap(fn ($r) => MagazzinoService::sottoSogliaReparto($r->id)->map(fn ($x) => $x->nome . ' (' . $r->nome . ')'))->values();
        }

        $nBozze = Schema::hasTable('ordini_fornitore')
            ? DB::table('ordini_fornitore')->where('stato', 'bozza')->count()
            : 0;

        return view('magazzino.index', [
            'articoli' => $articoli, 'q' => $q, 'soloSotto' => $soloSotto,
            'nSotto' => MagazzinoService::sottoSoglia()->count(),
            'valore' => round($tutti->sum(fn ($a) => max(0, $a->scorta_attuale) * (float) $a->prezzo_acquisto), 2),
            'nBozze' => $nBozze,
            'avvisi' => MagazzinoService::avvisi(),
            'daRifornire' => $daRifornire,
        ]);
    }

    public function articolo(?int $articolo = null)
    {
        $this->consenti();

        $a = $articolo ? (DB::table('articoli_magazzino')->where('id', $articolo)->first() ?? abort(404)) : null;

        return view('magazzino.articolo', [
            'a' => $a,
            'fornitori' => DB::table('fornitori')->orderBy('nome')->get(),
            'movimenti' => $a ? DB::table('movimenti_magazzino as m')->leftJoin('utenti as u', 'u.id', '=', 'm.utente_id')
                ->where('m.articolo_id', $a->id)->orderByDesc('m.id')->limit(20)->select('m.*', 'u.nome as utente')->get() : collect(),
        ]);
    }

    public function articoloSalva(Request $request, ?int $articolo = null)
    {
        $this->consenti();

        $dati = $request->validate([
            'nome'                     => 'required|string|max:100',
            'unita_misura'             => 'required|string|max:10',
            'prezzo_acquisto'          => 'nullable|numeric|min:0|max:999999',
            'fornitore_id'             => 'nullable|integer|exists:fornitori,id',
            'soglia_riordino'          => 'nullable|numeric|min:0|max:999999',
            'quantita_standard_ordine' => 'nullable|numeric|min:0|max:999999',
        ]);
        $dati['attivo'] = $articolo ? $request->boolean('attivo') : true;

        if ($articolo) {
            abort_unless(DB::table('articoli_magazzino')->where('id', $articolo)->exists(), 404);
            DB::table('articoli_magazzino')->where('id', $articolo)->update($dati + ['updated_at' => now()]);
            $id = $articolo;
            $msg = 'Articolo aggiornato.';
        } else {
            $id = DB::table('articoli_magazzino')->insertGetId($dati + ['scorta_attuale' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $msg = 'Articolo creato: ora puoi caricare la scorta iniziale.';
        }

        return redirect()->route('magazzino.articolo.modifica', $id)->with('ok', $msg);
    }

    /** Carico o scarico manuale (scorta iniziale, inventario, rotture). */
    public function movimentoSalva(Request $request, int $articolo)
    {
        $this->consenti();
        abort_unless(DB::table('articoli_magazzino')->where('id', $articolo)->exists(), 404);

        $dati = $request->validate([
            'tipo'     => 'required|in:carico,scarico',
            'quantita' => 'required|numeric|min:0.001|max:999999',
            'nota'     => 'nullable|string|max:255',
        ]);

        DB::transaction(fn () => MagazzinoService::muovi($articolo, $dati['tipo'], (float) $dati['quantita'], 'manuale', null, auth()->id(), $dati['nota'] ?? null));

        return back()->with('ok', $dati['tipo'] === 'carico' ? 'Carico registrato.' : 'Scarico registrato.');
    }

    public function movimenti(Request $request)
    {
        $this->consenti();

        $sorgente = array_key_exists((string) $request->query('sorgente'), self::SORGENTI) ? $request->query('sorgente') : null;

        $righe = DB::table('movimenti_magazzino as m')
            ->join('articoli_magazzino as a', 'a.id', '=', 'm.articolo_id')->leftJoin('utenti as u', 'u.id', '=', 'm.utente_id')->leftJoin('reparti as r', 'r.id', '=', 'm.reparto_id')
            ->when($sorgente, fn ($w) => $w->where('m.sorgente', $sorgente))
            ->orderByDesc('m.id')->limit(100)
            ->select('m.*', 'a.nome', 'a.unita_misura', 'u.nome as utente', 'r.nome as reparto')->get();

        return view('magazzino.movimenti', ['righe' => $righe, 'sorgente' => $sorgente, 'sorgenti' => self::SORGENTI]);
    }

    // ======================================================================
    // FORNITORI
    // ======================================================================

    public function fornitori()
    {
        $this->consenti();

        return view('magazzino.fornitori', [
            'fornitori' => DB::table('fornitori')->orderBy('nome')->get(),
            'conOrdini' => DB::table('ordini_fornitore')->distinct()->pluck('fornitore_id')->all(),
        ]);
    }

    private function validaFornitore(Request $request): array
    {
        return $request->validate([
            'nome'             => 'required|string|max:150',
            'email'            => 'nullable|email|max:150',
            'telefono'         => 'nullable|string|max:40',
            'lead_time_giorni' => 'nullable|integer|min:0|max:365',
        ]);
    }

    public function fornitoreCrea(Request $request)
    {
        $this->consenti();
        DB::table('fornitori')->insert($this->validaFornitore($request) + ['created_at' => now(), 'updated_at' => now()]);

        return back()->with('ok', 'Fornitore aggiunto.');
    }

    public function fornitoreAggiorna(Request $request, int $fornitore)
    {
        $this->consenti();
        abort_unless(DB::table('fornitori')->where('id', $fornitore)->exists(), 404);
        DB::table('fornitori')->where('id', $fornitore)->update($this->validaFornitore($request) + ['updated_at' => now()]);

        return back()->with('ok', 'Fornitore aggiornato.');
    }

    public function fornitoreElimina(int $fornitore)
    {
        $this->consenti();

        if (DB::table('ordini_fornitore')->where('fornitore_id', $fornitore)->exists()) {
            return back()->withErrors(['magazzino' => 'Questo fornitore ha degli ordini: non si può eliminare.']);
        }
        DB::table('fornitori')->where('id', $fornitore)->delete();

        return back()->with('ok', 'Fornitore eliminato. Gli articoli collegati restano senza fornitore abituale.');
    }

    // ======================================================================
    // ORDINI AI FORNITORI: bozza → approvato → inviato → consegnato
    // ======================================================================

    public function ordini(Request $request)
    {
        $this->consenti();

        $stato = array_key_exists((string) $request->query('stato'), self::STATI_ORDINE) ? $request->query('stato') : null;

        $ordini = DB::table('ordini_fornitore as o')->join('fornitori as f', 'f.id', '=', 'o.fornitore_id')
            ->when($stato, fn ($w) => $w->where('o.stato', $stato), fn ($w) => $w->whereIn('o.stato', ['bozza', 'approvato', 'inviato']))
            ->orderByRaw("case o.stato when 'bozza' then 0 when 'approvato' then 1 else 2 end")->orderByDesc('o.id')
            ->select('o.*', 'f.nome as fornitore')->get();

        $totali = DB::table('righe_ordine')->whereIn('ordine_id', $ordini->pluck('id'))
            ->selectRaw('ordine_id, count(*) as righe, sum(quantita_ordinata * coalesce(prezzo_acquisto, 0)) as totale')->groupBy('ordine_id')->get()->keyBy('ordine_id');

        return view('magazzino.ordini', [
            'ordini' => $ordini, 'totali' => $totali, 'stato' => $stato, 'stati' => self::STATI_ORDINE,
            'fornitori' => DB::table('fornitori')->orderBy('nome')->get(),
            'sottoSoglia' => MagazzinoService::sottoSoglia(),
        ]);
    }

    /** Scansiona tutti gli articoli sotto soglia e prepara le bozze (utile dopo aver impostato le soglie). */
    public function generaBozze()
    {
        $this->consenti();

        $nuove = 0;
        $senzaFornitore = [];
        foreach (MagazzinoService::sottoSoglia() as $a) {
            if (! $a->fornitore_id || ! (float) $a->quantita_standard_ordine) {
                $senzaFornitore[] = $a->nome;
            } elseif (DB::transaction(fn () => MagazzinoService::controllaSoglia($a->id))) {
                $nuove++;
            }
        }

        $msg = $nuove . ' ' . ($nuove === 1 ? 'articolo aggiunto' : 'articoli aggiunti') . ' alle bozze d\'ordine.';
        if ($senzaFornitore) {
            $msg .= ' Da completare (serve fornitore abituale e quantità standard): ' . implode(', ', $senzaFornitore) . '.';
        }

        return redirect()->route('magazzino.ordini')->with('ok', $msg);
    }

    public function ordineNuovo(Request $request)
    {
        $this->consenti();
        $dati = $request->validate(['fornitore_id' => 'required|integer|exists:fornitori,id']);

        $id = DB::table('ordini_fornitore')->insertGetId([
            'fornitore_id' => $dati['fornitore_id'], 'stato' => 'bozza', 'creato_da' => auth()->id(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return redirect()->route('magazzino.ordine', $id);
    }

    private function ordineDi(int $id): object
    {
        return DB::table('ordini_fornitore as o')->join('fornitori as f', 'f.id', '=', 'o.fornitore_id')
            ->where('o.id', $id)->select('o.*', 'f.nome as fornitore', 'f.email as fornitore_email')->first() ?? abort(404);
    }

    public function ordine(int $ordine)
    {
        $this->consenti();
        $o = $this->ordineDi($ordine);

        $righe = DB::table('righe_ordine as r')->join('articoli_magazzino as a', 'a.id', '=', 'r.articolo_id')
            ->where('r.ordine_id', $o->id)->orderBy('a.nome')->select('r.*', 'a.nome', 'a.unita_misura')->get();

        $utenti = DB::table('utenti')->whereIn('id', array_filter([$o->creato_da, $o->approvato_da]))->pluck('nome', 'id');

        return view('magazzino.ordine', [
            'o' => $o, 'righe' => $righe, 'stati' => self::STATI_ORDINE, 'utenti' => $utenti,
            'disponibili' => $o->stato === 'bozza'
                ? DB::table('articoli_magazzino')->where('attivo', true)->whereNotIn('id', $righe->pluck('articolo_id'))->orderBy('nome')->get()
                : collect(),
        ]);
    }

    private function soloBozza(int $ordine): object
    {
        $o = $this->ordineDi($ordine);
        abort_unless($o->stato === 'bozza', 422, 'L\'ordine non è più una bozza: non si può modificare.');

        return $o;
    }

    public function rigaAggiungi(Request $request, int $ordine)
    {
        $this->consenti();
        $o = $this->soloBozza($ordine);

        $dati = $request->validate([
            'articolo_id' => 'required|integer|exists:articoli_magazzino,id',
            'quantita'    => 'nullable|numeric|min:0.001|max:999999',
        ]);
        $a = DB::table('articoli_magazzino')->where('id', $dati['articolo_id'])->first();

        DB::table('righe_ordine')->updateOrInsert(
            ['ordine_id' => $o->id, 'articolo_id' => $a->id],
            ['quantita_ordinata' => $dati['quantita'] ?? ($a->quantita_standard_ordine ?: 1), 'prezzo_acquisto' => $a->prezzo_acquisto, 'created_at' => now(), 'updated_at' => now()]
        );

        return back()->with('ok', 'Articolo aggiunto alla bozza.');
    }

    public function rigaAggiorna(Request $request, int $ordine, int $riga)
    {
        $this->consenti();
        $o = $this->soloBozza($ordine);

        $dati = $request->validate([
            'quantita_ordinata' => 'required|numeric|min:0.001|max:999999',
            'prezzo_acquisto'   => 'nullable|numeric|min:0|max:999999',
        ]);
        DB::table('righe_ordine')->where('id', $riga)->where('ordine_id', $o->id)->update($dati + ['updated_at' => now()]);

        return back()->with('ok', 'Riga aggiornata.');
    }

    public function rigaElimina(int $ordine, int $riga)
    {
        $this->consenti();
        $o = $this->soloBozza($ordine);
        DB::table('righe_ordine')->where('id', $riga)->where('ordine_id', $o->id)->delete();

        return back()->with('ok', 'Riga tolta dalla bozza.');
    }

    /** Approvazione di un gestore: da qui in poi l'ordine non si modifica più. Resta traccia di chi e quando. */
    public function approva(int $ordine)
    {
        $this->consenti();
        $o = $this->soloBozza($ordine);

        if (! DB::table('righe_ordine')->where('ordine_id', $o->id)->exists()) {
            return back()->withErrors(['magazzino' => 'La bozza è vuota: aggiungi almeno un articolo.']);
        }
        DB::table('ordini_fornitore')->where('id', $o->id)->update([
            'stato' => 'approvato', 'approvato_da' => auth()->id(), 'approvato_il' => now(), 'updated_at' => now(),
        ]);

        return back()->with('ok', 'Ordine approvato. Ora puoi inviarlo al fornitore.');
    }

    /** Segna l'ordine come inviato (l'invio vero lo fai tu dalla mail o per telefono: il testo è pronto nella pagina). */
    public function invia(int $ordine)
    {
        $this->consenti();
        $o = $this->ordineDi($ordine);
        abort_unless($o->stato === 'approvato', 422);

        DB::table('ordini_fornitore')->where('id', $o->id)->update(['stato' => 'inviato', 'inviato_il' => now(), 'updated_at' => now()]);

        return back()->with('ok', 'Ordine segnato come inviato.');
    }

    /** Arrivo della merce: il carico chiude il cerchio. Si registra la quantità davvero consegnata (può differire dall'ordinata). */
    public function consegna(Request $request, int $ordine)
    {
        $this->consenti();
        $o = $this->ordineDi($ordine);
        abort_unless($o->stato === 'inviato', 422);

        $dati = $request->validate(['consegnata' => 'required|array', 'consegnata.*' => 'nullable|numeric|min:0|max:999999']);

        DB::transaction(function () use ($o, $dati) {
            foreach (DB::table('righe_ordine')->where('ordine_id', $o->id)->get() as $r) {
                $q = (float) ($dati['consegnata'][$r->id] ?? 0);
                DB::table('righe_ordine')->where('id', $r->id)->update(['quantita_consegnata' => $q, 'updated_at' => now()]);
                MagazzinoService::muovi($r->articolo_id, 'carico', $q, 'ordine', 'ordine:' . $o->id, auth()->id());
            }
            DB::table('ordini_fornitore')->where('id', $o->id)->update(['stato' => 'consegnato', 'consegnato_il' => now(), 'updated_at' => now()]);
        });

        return back()->with('ok', 'Consegna registrata: scorte caricate.');
    }

    public function annulla(int $ordine)
    {
        $this->consenti();
        $o = $this->ordineDi($ordine);
        abort_unless(in_array($o->stato, ['bozza', 'approvato', 'inviato'], true), 422);

        DB::table('ordini_fornitore')->where('id', $o->id)->update(['stato' => 'annullato', 'updated_at' => now()]);

        return redirect()->route('magazzino.ordini')->with('ok', 'Ordine annullato.');
    }

    // ======================================================================
    // REPARTI: magazzini di Bar, Ristorante... rifornati dal centrale
    // ======================================================================

    public function reparti(Request $request)
    {
        $this->consenti();

        if (!Schema::hasTable('reparti')) {
            return view('magazzino.reparti', [
                'reparti' => collect(), 'rep' => null, 'righe' => collect(),
                'richieste' => collect(), 'inArrivo' => collect(), 'recenti' => collect(),
            ]);
        }

        $reparti = DB::table('reparti')->where('attivo', true)->orderBy('nome')->get();
        $rep = $reparti->firstWhere('id', (int) $request->query('reparto')) ?? $reparti->first();

        $righe = collect();
        if ($rep && Schema::hasTable('giacenze_reparto')) {
            $righe = DB::table('articoli_magazzino as a')
                ->leftJoin('giacenze_reparto as g', fn ($j) => $j->on('g.articolo_id', '=', 'a.id')->where('g.reparto_id', $rep->id))
                ->where('a.attivo', true)->orderBy('a.nome')
                ->select('a.id as articolo_id', 'a.nome', 'a.unita_misura', 'a.scorta_attuale as centrale', 'g.scorta', 'g.soglia', 'g.quantita_rifornimento')->get();
        }

        $trasf = fn ($stato) => ($rep && Schema::hasTable('trasferimenti_reparto')) ? DB::table('trasferimenti_reparto as t')->join('articoli_magazzino as a', 'a.id', '=', 't.articolo_id')
            ->where('t.reparto_id', $rep->id)->where('t.stato', $stato)->orderByDesc('t.id')->select('t.*', 'a.nome', 'a.unita_misura') : null;

        $richieste = (Schema::hasTable('richieste_reparto') && Schema::hasTable('reparti'))
            ? DB::table('richieste_reparto as q')->join('articoli_magazzino as a', 'a.id', '=', 'q.articolo_id')->join('reparti as r', 'r.id', '=', 'q.reparto_id')
                ->where('q.stato', 'aperta')->orderBy('q.id')->select('q.*', 'a.nome', 'a.unita_misura', 'a.scorta_attuale as centrale', 'r.nome as reparto')->get()
            : collect();

        return view('magazzino.reparti', [
            'reparti' => $reparti, 'rep' => $rep, 'righe' => $righe,
            'richieste' => $richieste,
            'inArrivo' => ($trasf && $trasf('in_arrivo')) ? $trasf('in_arrivo')->get() : collect(),
            'recenti'  => ($trasf && $trasf('ricevuto')) ? $trasf('ricevuto')->limit(8)->get() : collect(),
        ]);
    }

    public function repartoCrea(Request $request)
    {
        $this->consenti();
        $dati = $request->validate(['nome' => 'required|string|max:60|unique:reparti,nome']);

        $id = DB::table('reparti')->insertGetId(['nome' => $dati['nome'], 'attivo' => true, 'created_at' => now(), 'updated_at' => now()]);

        return redirect()->route('magazzino.reparti', ['reparto' => $id])->with('ok', 'Reparto creato.');
    }

    public function giacenzaSalva(Request $request, int $reparto, int $articolo)
    {
        $this->consenti();
        abort_unless(DB::table('reparti')->where('id', $reparto)->exists() && DB::table('articoli_magazzino')->where('id', $articolo)->exists(), 404);

        $dati = $request->validate([
            'soglia'                => 'nullable|numeric|min:0|max:999999',
            'quantita_rifornimento' => 'nullable|numeric|min:0|max:999999',
        ]);

        DB::table('giacenze_reparto')->insertOrIgnore(['reparto_id' => $reparto, 'articolo_id' => $articolo, 'scorta' => 0, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('giacenze_reparto')->where('reparto_id', $reparto)->where('articolo_id', $articolo)
            ->update(['soglia' => $dati['soglia'] ?? null, 'quantita_rifornimento' => $dati['quantita_rifornimento'] ?? null, 'updated_at' => now()]);

        return back()->with('ok', 'Soglia del reparto salvata.');
    }

    /** Rifornimento del reparto: esce dal magazzino centrale ed entra in quello del reparto. */
    public function trasferisci(Request $request, int $reparto, int $articolo)
    {
        $this->consenti();
        abort_unless(DB::table('reparti')->where('id', $reparto)->exists(), 404);
        $a = DB::table('articoli_magazzino')->where('id', $articolo)->first() ?? abort(404);

        $dati = $request->validate(['quantita' => 'required|numeric|min:0.001|max:999999']);

        if ((float) $dati['quantita'] > (float) $a->scorta_attuale) {
            return back()->withErrors(['magazzino' => 'Nel magazzino centrale ci sono solo ' . rtrim(rtrim(number_format($a->scorta_attuale, 3, ',', ''), '0'), ',') . ' ' . $a->unita_misura . ' di ' . $a->nome . '.']);
        }

        DB::transaction(fn () => MagazzinoService::inviaTrasferimento($articolo, $reparto, (float) $dati['quantita'], auth()->id()));

        return back()->with('ok', 'Trasferimento inviato: la merce è uscita dal centrale e il reparto deve confermare la ricezione.');
    }

    public function trasferimentoAnnulla(int $trasferimento)
    {
        $this->consenti();
        abort_unless(DB::table('trasferimenti_reparto')->where('id', $trasferimento)->where('stato', 'in_arrivo')->exists(), 404);

        DB::transaction(fn () => MagazzinoService::annullaTrasferimento($trasferimento, auth()->id()));

        return back()->with('ok', 'Trasferimento annullato: la merce è tornata nel centrale.');
    }

    /** Evade la richiesta di un reparto: parte il trasferimento (quantità modificabile) e il reparto lo conferma quando arriva. */
    public function richiestaEvadi(Request $request, int $richiesta)
    {
        $this->consenti();
        $q = DB::table('richieste_reparto')->where('id', $richiesta)->where('stato', 'aperta')->first() ?? abort(404);
        $a = DB::table('articoli_magazzino')->where('id', $q->articolo_id)->first();

        $dati = $request->validate(['quantita' => 'required|numeric|min:0.001|max:999999']);
        if ((float) $dati['quantita'] > (float) $a->scorta_attuale) {
            return back()->withErrors(['magazzino' => 'Nel magazzino centrale ci sono solo ' . rtrim(rtrim(number_format($a->scorta_attuale, 3, ',', ''), '0'), ',') . ' ' . $a->unita_misura . ' di ' . $a->nome . ': riduci la quantità o ordina al fornitore.']);
        }

        DB::transaction(function () use ($q, $dati) {
            $trasf = MagazzinoService::inviaTrasferimento($q->articolo_id, $q->reparto_id, (float) $dati['quantita'], auth()->id(), 'Su richiesta del reparto');
            DB::table('richieste_reparto')->where('id', $q->id)->update(['stato' => 'evasa', 'evasa_il' => now(), 'trasferimento_id' => $trasf, 'updated_at' => now()]);
        });

        return back()->with('ok', 'Richiesta evasa: merce inviata, il reparto deve confermare la ricezione.');
    }

    public function richiestaAnnulla(int $richiesta)
    {
        $this->consenti();
        DB::table('richieste_reparto')->where('id', $richiesta)->where('stato', 'aperta')->update(['stato' => 'annullata', 'updated_at' => now()]);

        return back()->with('ok', 'Richiesta rifiutata.');
    }
}
