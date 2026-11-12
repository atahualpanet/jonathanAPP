<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Prenotazione;
use App\Support\MagazzinoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class BistroController extends Controller
{
    public const ZONE = [
        'sala' => 'Sala',
        'terrazza' => 'Terrazza',
        'bar' => 'Bar',
    ];

    public const RISTORANTE = [
        'antipasti' => 'Antipasti',
        'primi' => 'Primi',
        'secondi' => 'Secondi',
        'dessert' => 'Dessert',
        'bevande' => 'Bevande',
    ];

    public const CASSA = [
        'caffe' => 'Caffè',
        'colazione' => 'Colazione',
        'bar' => 'Bibite',
    ];

    public const CATEGORIE = self::RISTORANTE + self::CASSA;

    private const PIATTI = ['antipasti', 'primi', 'secondi', 'dessert'];
    private const SOGLIA_RITARDO_MIN = 10;

    private const LISTINO_BASE = [
        ['Tagliere', 'antipasti', 14],
        ['Risotto zafferano', 'primi', 13],
        ['Tortelli di zucca', 'primi', 12],
        ['Tagliatelle', 'primi', 11],
        ['Gnocchi', 'primi', 11],
        ['Lasagne', 'primi', 12],
        ['Spaghetti', 'primi', 15],
        ['Pappardelle', 'primi', 14],
        ['Minestra', 'primi', 9],
        ['Gnocchetti', 'primi', 10],
        ['Acqua', 'bevande', 3],
        ['Vino della casa', 'bevande', 18],
    ];

    private const LISTINO_BASE_CASSA = [
        ['Cappuccino', 'caffe', 1.80],
        ['Espresso', 'caffe', 1.20],
        ['Tè', 'caffe', 2.00],
        ['Brioche', 'colazione', 1.50],
        ['Spremuta', 'colazione', 4.00],
        ['Toast', 'colazione', 5.50],
        ['Yogurt', 'colazione', 3.00],
        ['Acqua', 'bar', 1.50],
        ['Succo', 'bar', 2.50],
    ];

    private function consenti(): void
    {
        abort_unless(
            auth()->user()->isGestore() || auth()->user()->puo('comande'),
            403
        );
    }

    private function soloGestori(): void
    {
        abort_unless(auth()->user()->isGestore(), 403);
    }

    public function mappa(Request $request)
    {
        $this->consenti();

        $zona = array_key_exists(
            (string) $request->query('zona'),
            self::ZONE
        ) ? $request->query('zona') : 'sala';

        $tavoli = DB::table('bistro_tavoli as t')
            ->leftJoin('bistro_comande as c', function ($join) {
                $join->on('c.tavolo_id', '=', 't.id')
                    ->where('c.stato', '!=', 'chiusa');
            })
            ->where('t.zona', $zona)
            ->where('t.attivo', true)
            ->orderBy('t.numero')
            ->select(
                't.id',
                't.numero',
                'c.stato as comanda_stato',
                'c.coperti',
                'c.aperta_il'
            )
            ->get()
            ->map(function ($tavolo) {
                $tavolo->minuti = $tavolo->aperta_il
                    ? (int) Carbon::parse($tavolo->aperta_il)->diffInMinutes(now(), true)
                    : null;

                return $tavolo;
            });

        $sottoSogliaCentrale = DB::table('articoli_magazzino')
            ->where('attivo', true)
            ->whereNotNull('soglia_riordino')
            ->whereColumn('scorta_attuale', '<=', 'soglia_riordino')
            ->pluck('nome');

        return view('bistro.mappa', [
            'zona' => $zona,
            'zone' => self::ZONE,
            'tavoli' => $tavoli,
            'riepilogo' => [
                'occupati' => $tavoli->where('comanda_stato', 'aperta')->count(),
                'conti' => $tavoli->where('comanda_stato', 'conto')->count(),
                'liberi' => $tavoli->whereNull('comanda_stato')->count(),
            ],
            'copertiOggi' => (int) DB::table('bistro_comande')
                ->whereDate('aperta_il', Carbon::today()->toDateString())
                ->sum('coperti'),
            'daRifornire' => $sottoSogliaCentrale->values(),
            'inArrivoTot' => 0,
        ]);
    }

    private function tavolo(int $id): object
    {
        return DB::table('bistro_tavoli')
            ->where('id', $id)
            ->where('attivo', true)
            ->first() ?? abort(404);
    }

    private function comandaAperta(int $tavoloId): ?object
    {
        return DB::table('bistro_comande')
            ->where('tavolo_id', $tavoloId)
            ->where('stato', '!=', 'chiusa')
            ->first();
    }

    private function righeDi(int $comandaId)
    {
        return DB::table('bistro_comanda_righe')
            ->where('comanda_id', $comandaId)
            ->orderBy('id')
            ->get();
    }

    private function totaleDi($righe): float
    {
        return round(
            $righe->sum(fn ($riga) => $riga->prezzo * $riga->quantita),
            2
        );
    }

    public function comanda(Request $request, int $tavolo)
    {
        $this->consenti();

        $t = $this->tavolo($tavolo);
        $comanda = $this->comandaAperta($t->id);

        if ($comanda && $comanda->stato === 'conto') {
            return redirect()->route('bistro.conto', $t->id);
        }

        $cat = array_key_exists(
            (string) $request->query('cat'),
            self::RISTORANTE
        ) ? $request->query('cat') : 'antipasti';

        $articoli = DB::table('bistro_articoli')
            ->where('attivo', true)
            ->where('categoria', $cat)
            ->orderBy('nome')
            ->get();

        $righe = $comanda ? $this->righeDi($comanda->id) : collect();

        return view('bistro.comanda', [
            'tavolo' => $t,
            'zone' => self::ZONE,
            'comanda' => $comanda,
            'categorie' => self::RISTORANTE,
            'cat' => $cat,
            'articoli' => $articoli,
            'righe' => $righe,
            'totale' => $this->totaleDi($righe),
        ]);
    }

    public function apri(Request $request, int $tavolo)
    {
        $this->consenti();

        $t = $this->tavolo($tavolo);

        $dati = $request->validate([
            'coperti' => 'required|integer|min:1|max:99',
        ]);

        if ($this->comandaAperta($t->id)) {
            return back()->withErrors([
                'bistro' => 'Il tavolo è già aperto.',
            ]);
        }

        DB::table('bistro_comande')->insert([
            'tavolo_id' => $t->id,
            'coperti' => $dati['coperti'],
            'stato' => 'aperta',
            'aperta_il' => now(),
            'utente_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('bistro.comanda', $t->id);
    }

    public function aggiungi(Request $request, int $tavolo)
    {
        $this->consenti();

        $t = $this->tavolo($tavolo);

        $dati = $request->validate([
            'articolo_id' => 'required|integer',
        ]);

        $comanda = $this->comandaAperta($t->id);

        if (!$comanda || $comanda->stato !== 'aperta') {
            return back()->withErrors([
                'bistro' => 'Apri prima il tavolo indicando i coperti.',
            ]);
        }

        $articolo = DB::table('bistro_articoli')
            ->where('id', $dati['articolo_id'])
            ->where('attivo', true)
            ->first();

        if (!$articolo) {
            return back()->withErrors([
                'bistro' => 'Articolo non disponibile.',
            ]);
        }

        $esistente = DB::table('bistro_comanda_righe')
            ->where('comanda_id', $comanda->id)
            ->where('articolo_id', $articolo->id)
            ->where('stato', 'nuova')
            ->first();

        if ($esistente) {
            DB::table('bistro_comanda_righe')
                ->where('id', $esistente->id)
                ->update([
                    'quantita' => $esistente->quantita + 1,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('bistro_comanda_righe')->insert([
                'comanda_id' => $comanda->id,
                'articolo_id' => $articolo->id,
                'nome' => $articolo->nome,
                'categoria' => $articolo->categoria,
                'prezzo' => $articolo->prezzo,
                'aliquota_iva' => $articolo->aliquota_iva,
                'quantita' => 1,
                'stato' => 'nuova',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('bistro.comanda', [
            'tavolo' => $t->id,
            'cat' => $articolo->categoria,
        ]);
    }

    public function meno(Request $request, int $riga)
    {
        $this->consenti();

        $rigaComanda = DB::table('bistro_comanda_righe as r')
            ->join('bistro_comande as c', 'c.id', '=', 'r.comanda_id')
            ->where('r.id', $riga)
            ->where('r.stato', 'nuova')
            ->where('c.stato', 'aperta')
            ->select('r.id', 'r.quantita', 'c.tavolo_id')
            ->first() ?? abort(404);

        if ($rigaComanda->quantita > 1) {
            DB::table('bistro_comanda_righe')
                ->where('id', $rigaComanda->id)
                ->update([
                    'quantita' => $rigaComanda->quantita - 1,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('bistro_comanda_righe')
                ->where('id', $rigaComanda->id)
                ->delete();
        }

        return redirect()->route('bistro.comanda', [
            'tavolo' => $rigaComanda->tavolo_id,
            'cat' => $request->query('cat'),
        ]);
    }

    public function invia(int $tavolo)
    {
        $this->consenti();

        $t = $this->tavolo($tavolo);
        $comanda = $this->comandaAperta($t->id);

        $righeInviabili = $comanda
            ? DB::table('bistro_comanda_righe')
                ->where('comanda_id', $comanda->id)
                ->where('stato', 'nuova')
            : null;

        $numeroRighe = $righeInviabili
            ? $righeInviabili->update([
                'stato' => 'inviata',
                'inviata_il' => now(),
                'updated_at' => now(),
            ])
            : 0;

        if (!$numeroRighe) {
            return back()->withErrors([
                'bistro' => 'Non ci sono righe nuove da inviare in cucina.',
            ]);
        }

        return redirect()
            ->route('bistro.mappa', ['zona' => $t->zona])
            ->with('ok', 'Comanda del tavolo ' . $t->numero . ' inviata in cucina.');
    }

    public function cucina()
    {
        $this->consenti();

        $comande = DB::table('bistro_comanda_righe as r')
            ->join('bistro_comande as c', 'c.id', '=', 'r.comanda_id')
            ->join('bistro_tavoli as t', 't.id', '=', 'c.tavolo_id')
            ->where('r.stato', 'inviata')
            ->orderBy('r.inviata_il')
            ->orderBy('r.id')
            ->select(
                'r.nome',
                'r.quantita',
                'r.inviata_il',
                'c.id as comanda_id',
                't.zona',
                't.numero'
            )
            ->get()
            ->groupBy('comanda_id');

        return view('bistro.cucina', [
            'comande' => $comande,
            'zone' => self::ZONE,
            'soglia' => self::SOGLIA_RITARDO_MIN,
        ]);
    }

    public function pronto(int $comanda)
    {
        $this->consenti();

        DB::table('bistro_comanda_righe')
            ->where('comanda_id', $comanda)
            ->where('stato', 'inviata')
            ->update([
                'stato' => 'pronta',
                'pronta_il' => now(),
                'updated_at' => now(),
            ]);

        return back()->with('ok', 'Comanda segnata come pronta.');
    }

    public function chiediConto(int $tavolo)
    {
        $this->consenti();

        $t = $this->tavolo($tavolo);
        $comanda = $this->comandaAperta($t->id);

        $righe = $comanda
            ? $this->righeDi($comanda->id)
            : collect();

        if (!$comanda || $righe->isEmpty()) {
            return back()->withErrors([
                'bistro' => 'Il tavolo non ha ancora ordini.',
            ]);
        }

        if ($righe->contains('stato', 'nuova')) {
            return back()->withErrors([
                'bistro' => 'Ci sono righe non ancora inviate in cucina.',
            ]);
        }

        DB::table('bistro_comande')
            ->where('id', $comanda->id)
            ->update([
                'stato' => 'conto',
                'updated_at' => now(),
            ]);

        return redirect()->route('bistro.conto', $t->id);
    }

    private function righeDaPagare(int $comandaId)
    {
        return DB::table('bistro_comanda_righe')
            ->where('comanda_id', $comandaId)
            ->whereNull('pagamento_id')
            ->orderBy('id')
            ->get();
    }

    public function conto(int $tavolo)
    {
        $this->consenti();

        $t = $this->tavolo($tavolo);
        $comanda = $this->comandaAperta($t->id);

        if (!$comanda || $comanda->stato !== 'conto') {
            return redirect()->route('bistro.comanda', $t->id);
        }

        $righe = $this->righeDaPagare($comanda->id);

        return view('bistro.conto', [
            'tavolo' => $t,
            'zone' => self::ZONE,
            'comanda' => $comanda,
            'righe' => $righe,
            'totale' => $this->totaleDi($righe),
            'pagamenti' => DB::table('bistro_pagamenti')
                ->where('comanda_id', $comanda->id)
                ->orderBy('id')
                ->get(),
        ]);
    }

    private function prenotazioneInCasa(string $numero): ?Prenotazione
    {
        $camera = Camera::where('numero', trim($numero))->first();

        if (!$camera) {
            return null;
        }

        return Prenotazione::with('ospite')
            ->where('stato', 'in_casa')
            ->whereHas('camere', fn ($query) => $query->where('camere.id', $camera->id))
            ->orderByDesc('data_arrivo')
            ->first();
    }

    public function verificaCamera(Request $request)
    {
        $this->consenti();

        $prenotazione = $this->prenotazioneInCasa(
            (string) $request->query('camera', '')
        );

        if (!$prenotazione) {
            return response()->json(['valida' => false]);
        }

        return response()->json([
            'valida' => true,
            'ospite' => trim(
                $prenotazione->ospite->nome . ' ' . $prenotazione->ospite->cognome
            ),
            'fino_al' => $prenotazione->data_partenza
                ->locale('it')
                ->translatedFormat('j M'),
        ]);
    }

    private function aliquotaIva(): float
    {
        try {
            $config = json_decode(
                file_get_contents(config_path('hotel_config.json')),
                true
            )['fatturazione'] ?? [];
        } catch (Throwable $e) {
            $config = [];
        }

        return (float) (
            $config['aliquota_iva_ristorazione']
            ?? $config['aliquota_iva_default']
            ?? 22
        );
    }

    private function selezioneDa(array $quantita, $residue): array
    {
        $selezione = [];

        foreach ($quantita as $id => $quantitaRichiesta) {
            $riga = $residue->firstWhere('id', (int) $id);

            if ($riga && (int) $quantitaRichiesta > 0) {
                $selezione[$riga->id] = min(
                    (int) $quantitaRichiesta,
                    $riga->quantita
                );
            }
        }

        return $selezione;
    }

    private function incassa(
        object $comanda,
        string $metodo,
        ?string $camera,
        array $selezione,
        $residue,
        string $descrizione
    ) {
        $importo = round(
            collect($selezione)->map(function ($quantita, $id) use ($residue) {
                $riga = $residue->firstWhere('id', $id);

                return $riga->prezzo * $quantita;
            })->sum(),
            2
        );

        $prenotazione = null;
        $conto = null;
        $frontDesk = app(FrontDeskController::class);

        if ($metodo === 'camera') {
            $prenotazione = $this->prenotazioneInCasa((string) $camera);

            if (!$prenotazione) {
                return 'Camera non valida o nessun ospite in casa.';
            }

            $conto = $frontDesk->contoDi($prenotazione);

            if ($conto && $conto->stato !== 'aperto') {
                return 'Il conto di questa prenotazione è chiuso.';
            }

            if ($conto && $frontDesk->folioBloccato($conto->id, 'B')) {
                return 'Il folio B ha già una fattura emessa.';
            }
        }

        $iva = $this->aliquotaIva();

        $gruppi = [];

        foreach ($selezione as $id => $quantita) {
            $riga = $residue->firstWhere('id', $id);

            $aliquota = $riga->aliquota_iva !== null
                ? (float) $riga->aliquota_iva
                : $iva;

            $chiave = (string) $aliquota;

            $gruppi[$chiave] = [
                'aliquota' => $aliquota,
                'lordo' => ($gruppi[$chiave]['lordo'] ?? 0) + $riga->prezzo * $quantita,
            ];
        }

        foreach ($gruppi as $chiave => $gruppo) {
            $lordo = round($gruppo['lordo'], 2);

            $imponibile = $gruppo['aliquota'] > 0
                ? round($lordo / (1 + $gruppo['aliquota'] / 100), 2)
                : $lordo;

            $gruppi[$chiave] = $gruppo + [
                'lordo_r' => $lordo,
                'imponibile' => $imponibile,
                'imposta' => round($lordo - $imponibile, 2),
            ];
        }

        $chiusa = false;
        $senzaRicetta = [];
        $avvisiMagazzino = [];

        DB::transaction(function () use (
            $comanda,
            $metodo,
            $camera,
            $selezione,
            $residue,
            $descrizione,
            $importo,
            $prenotazione,
            $conto,
            $frontDesk,
            $gruppi,
            &$chiusa,
            &$senzaRicetta,
            &$avvisiMagazzino
        ) {
            $voceContoId = null;

            if ($prenotazione) {
                $contoId = $conto->id ?? $frontDesk->apriContoPer($prenotazione);

                foreach ($gruppi as $gruppo) {
                    $id = DB::table('voci_conto')->insertGetId([
                        'conto_id' => $contoId,
                        'folio' => 'B',
                        'tipo' => 'servizio',
                        'descrizione' => count($gruppi) > 1
                            ? $descrizione . ' · IVA ' . $this->fmtAliquota($gruppo['aliquota']) . '%'
                            : $descrizione,
                        'importo' => $gruppo['lordo_r'],
                        'riferimento' => 'bistro:' . $comanda->id . ':' . $this->fmtAliquota($gruppo['aliquota']),
                        'registrata_il' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $voceContoId ??= $id;
                }

                $frontDesk->aggiornaSaldoConto($contoId);
            }

            $pagamentoId = DB::table('bistro_pagamenti')->insertGetId([
                'comanda_id' => $comanda->id,
                'metodo' => $metodo,
                'camera' => $prenotazione ? trim((string) $camera) : null,
                'importo' => $importo,
                'prenotazione_id' => $prenotazione?->id,
                'voce_conto_id' => $voceContoId,
                'utente_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($gruppi as $gruppo) {
                DB::table('bistro_pagamento_iva')->insert([
                    'pagamento_id' => $pagamentoId,
                    'aliquota' => $gruppo['aliquota'],
                    'lordo' => $gruppo['lordo_r'],
                    'imponibile' => $gruppo['imponibile'],
                    'imposta' => $gruppo['imposta'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($selezione as $id => $quantita) {
                $riga = $residue->firstWhere('id', $id);

                if ($quantita < $riga->quantita) {
                    DB::table('bistro_comanda_righe')
                        ->where('id', $id)
                        ->update([
                            'quantita' => $riga->quantita - $quantita,
                            'updated_at' => now(),
                        ]);

                    $copia = (array) $riga;
                    unset($copia['id']);

                    DB::table('bistro_comanda_righe')->insert(
                        $copia + [
                            'quantita' => $quantita,
                            'pagamento_id' => $pagamentoId,
                            'updated_at' => now(),
                        ]
                    );
                } else {
                    DB::table('bistro_comanda_righe')
                        ->where('id', $id)
                        ->update([
                            'pagamento_id' => $pagamentoId,
                            'updated_at' => now(),
                        ]);
                }

                if ($riga->articolo_id) {
                    $articoloListino = DB::table('bistro_articoli')
                        ->where('id', $riga->articolo_id)
                        ->first();

                    $ricette = DB::table('bistro_ricette')
                        ->where('bistro_articolo_id', $riga->articolo_id)
                        ->get();

                    if ($articoloListino && $ricette->isNotEmpty()) {
                        foreach ($ricette as $ricetta) {
                            $avvisi = MagazzinoService::scaricaDiretto(
                                (int) $ricetta->magazzino_articolo_id,
                                (float) $quantita * (float) $ricetta->quantita,
                                'bistro',
                                'comanda:' . $comanda->id,
                                auth()->id()
                            );

                            $avvisiMagazzino = array_merge($avvisiMagazzino, $avvisi);
                        }
                    } elseif (
                        !in_array($riga->categoria, self::PIATTI, true)
                        && !$articoloListino?->senza_scorta
                    ) {
                        $senzaRicetta[] = $riga->nome . ' (' . (
                            self::CATEGORIE[$riga->categoria] ?? $riga->categoria
                        ) . ')';
                    }
                }
            }

            $rimaste = DB::table('bistro_comanda_righe')
                ->where('comanda_id', $comanda->id)
                ->whereNull('pagamento_id')
                ->exists();

            if (!$rimaste) {
                $pagamenti = DB::table('bistro_pagamenti')
                    ->where('comanda_id', $comanda->id)
                    ->get();

                $unico = $pagamenti->count() === 1
                    ? $pagamenti->first()
                    : null;

                $metodi = $pagamenti->pluck('metodo')->unique();

                DB::table('bistro_comande')
                    ->where('id', $comanda->id)
                    ->update([
                        'stato' => 'chiusa',
                        'chiusa_il' => now(),
                        'totale' => round($pagamenti->sum('importo'), 2),
                        'metodo_pagamento' => $metodi->count() === 1
                            ? $metodi->first()
                            : null,
                        'prenotazione_id' => $unico?->prenotazione_id,
                        'voce_conto_id' => $unico?->voce_conto_id,
                        'updated_at' => now(),
                    ]);

                $chiusa = true;
            }
        });

        return [
            'importo' => $importo,
            'chiusa' => $chiusa,
            'senza_ricetta' => array_values(array_unique($senzaRicetta)),
            'avvisi_magazzino' => $avvisiMagazzino,
        ];
    }

    private function fmtAliquota(float $aliquota): string
    {
        return rtrim(
            rtrim(number_format($aliquota, 2, '.', ''), '0'),
            '.'
        );
    }

    private function messaggioIncasso(
        string $metodo,
        ?string $camera,
        float $importo
    ): string {
        $eur = number_format($importo, 2, ',', '.');

        return $metodo === 'camera'
            ? 'Addebitati € ' . $eur . ' al conto della camera ' . trim((string) $camera)
            : 'Incassati € ' . $eur . ' (' . $metodo . ')';
    }

    public function paga(Request $request, int $tavolo)
    {
        $this->consenti();

        $t = $this->tavolo($tavolo);

        $dati = $request->validate([
            'metodo' => 'required|in:camera,carta,contanti',
            'camera' => 'required_if:metodo,camera|nullable|string|max:20',
            'quantita' => 'nullable|array',
            'quantita.*' => 'nullable|integer|min:0|max:255',
        ]);

        $comanda = $this->comandaAperta($t->id);

        if (!$comanda || $comanda->stato !== 'conto') {
            return redirect()
                ->route('bistro.comanda', $t->id)
                ->withErrors([
                    'bistro' => 'Il conto non è stato richiesto.',
                ]);
        }

        $residue = $this->righeDaPagare($comanda->id);

        $dividi = $request->boolean('dividi');

        $selezione = $dividi
            ? $this->selezioneDa($dati['quantita'] ?? [], $residue)
            : $residue->pluck('quantita', 'id')->all();

        if (!$selezione) {
            return back()
                ->withInput()
                ->withErrors([
                    'bistro' => 'Seleziona almeno un articolo da incassare.',
                ]);
        }

        $descrizione = 'Bistro · ' . self::ZONE[$t->zona]
            . ' tavolo ' . $t->numero
            . ' · ' . $comanda->coperti . ' coperti'
            . ($dividi ? ' · quota' : '');

        $esito = $this->incassa(
            $comanda,
            $dati['metodo'],
            $dati['camera'] ?? null,
            $selezione,
            $residue,
            $descrizione
        );

        if (is_string($esito)) {
            return back()
                ->withInput()
                ->withErrors(['bistro' => $esito]);
        }

        $messaggio = $this->messaggioIncasso(
            $dati['metodo'],
            $dati['camera'] ?? null,
            $esito['importo']
        );

        $avvisi = $esito['avvisi_magazzino']
            ? ' | ' . implode(' | ', $esito['avvisi_magazzino'])
            : '';

        $avvisi .= $this->avvisoRicette($esito['senza_ricetta']);

        if ($esito['chiusa']) {
            return redirect()
                ->route('bistro.mappa', ['zona' => $t->zona])
                ->with(
                    'ok',
                    $messaggio
                    . '. Tavolo ' . $t->numero . ' chiuso.'
                    . $avvisi
                );
        }

        $resto = $this->totaleDi(
            $this->righeDaPagare($comanda->id)
        );

        return redirect()
            ->route('bistro.conto', $t->id)
            ->with(
                'ok',
                $messaggio
                . '. Restano € '
                . number_format($resto, 2, ',', '.')
                . '.'
                . $avvisi
            );
    }

    private function avvisoRicette(array $nomi): string
    {
        return $nomi
            ? ' Attenzione: ' . implode(', ', $nomi) . ' non collegato a un articolo di magazzino.'
            : '';
    }

    private function tavoloCassa(): int
    {
        $tavolo = DB::table('bistro_tavoli')
            ->where('zona', 'bar')
            ->where('numero', 0)
            ->first();

        if ($tavolo) {
            return (int) $tavolo->id;
        }

        return (int) DB::table('bistro_tavoli')->insertGetId([
            'zona' => 'bar',
            'numero' => 0,
            'attivo' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ordineCassa(bool $crea = false): ?object
    {
        $tavoloId = $this->tavoloCassa();

        $query = fn () => DB::table('bistro_comande')
            ->where('tavolo_id', $tavoloId)
            ->where('stato', 'aperta')
            ->where('utente_id', auth()->id())
            ->first();

        $ordine = $query();

        if (!$ordine && $crea) {
            DB::table('bistro_comande')->insert([
                'tavolo_id' => $tavoloId,
                'coperti' => 0,
                'stato' => 'aperta',
                'aperta_il' => now(),
                'utente_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $ordine = $query();
        }

        return $ordine;
    }

    public function cassa(Request $request)
    {
        $this->consenti();

        $cat = array_key_exists(
            (string) $request->query('cat'),
            self::CASSA
        ) ? $request->query('cat') : array_key_first(self::CASSA);

        $articoli = DB::table('bistro_articoli')
            ->where('attivo', true)
            ->where('categoria', $cat)
            ->orderBy('nome')
            ->get();

        $ordine = $this->ordineCassa();

        $righe = $ordine
            ? $this->righeDi($ordine->id)
            : collect();

        return view('bistro.cassa', [
            'categorie' => self::CASSA,
            'cat' => $cat,
            'articoli' => $articoli,
            'ordine' => $ordine,
            'righe' => $righe,
            'totale' => $this->totaleDi($righe),
        ]);
    }

    public function cassaAggiungi(Request $request)
    {
        $this->consenti();

        $dati = $request->validate([
            'articolo_id' => 'required|integer',
        ]);

        $articolo = DB::table('bistro_articoli')
            ->where('id', $dati['articolo_id'])
            ->where('attivo', true)
            ->whereIn('categoria', array_keys(self::CASSA))
            ->first();

        if (!$articolo) {
            return back()->withErrors([
                'bistro' => 'Articolo non disponibile al punto cassa.',
            ]);
        }

        $ordine = $this->ordineCassa(true);

        $esistente = DB::table('bistro_comanda_righe')
            ->where('comanda_id', $ordine->id)
            ->where('articolo_id', $articolo->id)
            ->first();

        if ($esistente) {
            DB::table('bistro_comanda_righe')
                ->where('id', $esistente->id)
                ->update([
                    'quantita' => $esistente->quantita + 1,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('bistro_comanda_righe')->insert([
                'comanda_id' => $ordine->id,
                'articolo_id' => $articolo->id,
                'nome' => $articolo->nome,
                'categoria' => $articolo->categoria,
                'prezzo' => $articolo->prezzo,
                'aliquota_iva' => $articolo->aliquota_iva,
                'quantita' => 1,
                'stato' => 'pronta',
                'pronta_il' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('bistro.cassa', [
            'cat' => $articolo->categoria,
        ]);
    }

    public function cassaMeno(Request $request, int $riga)
    {
        $this->consenti();

        $ordine = $this->ordineCassa() ?? abort(404);

        $rigaComanda = DB::table('bistro_comanda_righe')
            ->where('id', $riga)
            ->where('comanda_id', $ordine->id)
            ->first() ?? abort(404);

        if ($rigaComanda->quantita > 1) {
            DB::table('bistro_comanda_righe')
                ->where('id', $rigaComanda->id)
                ->update([
                    'quantita' => $rigaComanda->quantita - 1,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('bistro_comanda_righe')
                ->where('id', $rigaComanda->id)
                ->delete();
        }

        return redirect()->route('bistro.cassa', [
            'cat' => $request->query('cat'),
        ]);
    }

    public function cassaAnnulla()
    {
        $this->consenti();

        $ordine = $this->ordineCassa();

        if ($ordine) {
            DB::table('bistro_comande')
                ->where('id', $ordine->id)
                ->delete();
        }

        return redirect()
            ->route('bistro.cassa')
            ->with('ok', 'Ordine svuotato.');
    }

    public function cassaPaga(Request $request)
    {
        $this->consenti();

        $dati = $request->validate([
            'metodo' => 'required|in:camera,carta,contanti',
            'camera' => 'required_if:metodo,camera|nullable|string|max:20',
        ]);

        $ordine = $this->ordineCassa();

        $residue = $ordine
            ? $this->righeDaPagare($ordine->id)
            : collect();

        if ($residue->isEmpty()) {
            return back()->withErrors([
                'bistro' => "L'ordine è vuoto.",
            ]);
        }

        $esito = $this->incassa(
            $ordine,
            $dati['metodo'],
            $dati['camera'] ?? null,
            $residue->pluck('quantita', 'id')->all(),
            $residue,
            'Bistro · punto cassa'
        );

        if (is_string($esito)) {
            return back()
                ->withInput()
                ->withErrors(['bistro' => $esito]);
        }

        $avvisi = $esito['avvisi_magazzino']
            ? ' | ' . implode(' | ', $esito['avvisi_magazzino'])
            : '';

        $avvisi .= $this->avvisoRicette($esito['senza_ricetta']);

        return redirect()
            ->route('bistro.cassa')
            ->with(
                'ok',
                $this->messaggioIncasso(
                    $dati['metodo'],
                    $dati['camera'] ?? null,
                    $esito['importo']
                ) . '.' . $avvisi
            );
    }

    public function listino()
    {
        $this->soloGestori();

        return view('bistro.listino', [
            'articoli' => DB::table('bistro_articoli')
                ->orderByDesc('attivo')
                ->orderBy('categoria')
                ->orderBy('nome')
                ->get(),
            'tavoli' => DB::table('bistro_tavoli')
                ->where('attivo', true)
                ->orderBy('zona')
                ->orderBy('numero')
                ->get()
                ->groupBy('zona'),
            'categorie' => self::CATEGORIE,
            'zone' => self::ZONE,
            'magazzino' => DB::table('articoli_magazzino')
                ->where('attivo', true)
                ->orderBy('nome')
                ->get(),
            'collegamenti' => DB::table('bistro_ricette')
                ->orderBy('id')
                ->get()
                ->groupBy('bistro_articolo_id')
                ->map(fn ($righe) => $righe->first()),
            'ivaPredefinita' => $this->aliquotaIva(),
            'sezioni' => [
                'Bar' => self::CASSA,
                'Ristorante' => self::RISTORANTE,
            ],
        ]);
    }

    public function caricaListinoBase()
    {
        $this->soloGestori();

        $haRistorante = DB::table('bistro_articoli')
            ->whereIn('categoria', array_keys(self::RISTORANTE))
            ->exists();

        $haCassa = DB::table('bistro_articoli')
            ->whereIn('categoria', array_keys(self::CASSA))
            ->exists();

        if ($haRistorante && $haCassa) {
            return back()->withErrors([
                'bistro' => 'Il listino non è vuoto.',
            ]);
        }

        $righe = array_merge(
            $haRistorante ? [] : self::LISTINO_BASE,
            $haCassa ? [] : self::LISTINO_BASE_CASSA
        );

        foreach ($righe as [$nome, $categoria, $prezzo]) {
            $dati = [
                'nome' => $nome,
                'categoria' => $categoria,
                'prezzo' => $prezzo,
                'senza_scorta' => in_array($categoria, self::PIATTI, true),
                'attivo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('bistro_articoli', 'reparto_id')) {
                $reparto = DB::table('reparti')
                    ->whereRaw('LOWER(nome) = ?', [
                        in_array($categoria, self::CASSA, true) ? 'bar' : 'ristorante',
                    ])
                    ->first();

                if ($reparto) {
                    $dati['reparto_id'] = $reparto->id;
                }
            }

            DB::table('bistro_articoli')->insert($dati);
        }

        return back()->with('ok', 'Listino base caricato.');
    }

    private function validaArticolo(Request $request): array
    {
        $dati = $request->validate([
            'nome' => 'required|string|max:100',
            'sezione' => 'required|string|max:40',
            'prezzo' => 'required|numeric|min:0|max:9999',
            'aliquota_iva' => 'nullable|numeric|min:0|max:100',
            'scarica' => 'nullable|string|max:20',
            'quantita_per_vendita' => 'required|numeric|gt:0|max:999999',
        ]);

        $parti = explode('|', $dati['sezione']);

        $nomeReparto = trim($parti[0] ?? '');
        $categoria = trim($parti[1] ?? '');

        if ($nomeReparto === '' || $categoria === '') {
            throw ValidationException::withMessages([
                'bistro' => 'Sezione non valida.',
            ]);
        }

        $atteso = array_key_exists($categoria, self::CASSA)
            ? 'Bar'
            : (array_key_exists($categoria, self::RISTORANTE) ? 'Ristorante' : null);

        if ($atteso === null || strcasecmp($atteso, $nomeReparto) !== 0) {
            throw ValidationException::withMessages([
                'bistro' => 'Sezione non valida.',
            ]);
        }

        $reparto = DB::table('reparti')
            ->whereRaw('LOWER(nome) = ?', [strtolower($nomeReparto)])
            ->first();

        if (!$reparto) {
            try {
                $repartoId = DB::table('reparti')->insertGetId([
                    'nome' => $nomeReparto,
                    'attivo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (Throwable $e) {
                report($e);

                throw ValidationException::withMessages([
                    'bistro' => 'Impossibile trovare o creare il reparto "' . $nomeReparto . '". Verifica la tabella reparti.',
                ]);
            }
        } else {
            $repartoId = $reparto->id;
        }

        $scarica = trim((string) ($dati['scarica'] ?? ''));
        $magazzinoId = null;

        if ($scarica !== '' && $scarica !== 'no' && ctype_digit($scarica)) {
            $magazzinoId = (int) $scarica;

            $esiste = DB::table('articoli_magazzino')
                ->where('id', $magazzinoId)
                ->exists();

            if (!$esiste) {
                throw ValidationException::withMessages([
                    'bistro' => "L'articolo di magazzino selezionato non esiste più.",
                ]);
            }
        }

        $risultato = [
            'nome' => $dati['nome'],
            'categoria' => $categoria,
            'prezzo' => $dati['prezzo'],
            'aliquota_iva' => $dati['aliquota_iva'] ?? null,
            'senza_scorta' => $scarica === 'no',
        ];

        if (Schema::hasColumn('bistro_articoli', 'reparto_id')) {
            $risultato['reparto_id'] = $repartoId;
        }

        // Il collegamento Bistro → Magazzino è gestito esclusivamente da bistro_ricette.
        // I campi temporanei vengono rimossi prima di scrivere bistro_articoli.
        $risultato['_ricetta_magazzino_id'] = $magazzinoId;
        $risultato['_quantita_per_vendita'] = (float) $dati['quantita_per_vendita'];

        return $risultato;
    }

    private function salvaRicetta(int $articoloBistroId, ?int $magazzinoId, float $quantita): void
    {
        DB::table('bistro_ricette')
            ->where('bistro_articolo_id', $articoloBistroId)
            ->delete();

        if ($magazzinoId !== null) {
            DB::table('bistro_ricette')->insert([
                'bistro_articolo_id' => $articoloBistroId,
                'magazzino_articolo_id' => $magazzinoId,
                'quantita' => round($quantita, 3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function articoloCrea(Request $request)
    {
        $this->soloGestori();

        try {
            $dati = $this->validaArticolo($request);
            $magazzinoId = $dati['_ricetta_magazzino_id'];
            $quantita = $dati['_quantita_per_vendita'];
            unset($dati['_ricetta_magazzino_id'], $dati['_quantita_per_vendita']);

            DB::transaction(function () use (&$dati, $magazzinoId, $quantita) {
                $dati['attivo'] = true;
                $dati['created_at'] = now();
                $dati['updated_at'] = now();

                $id = DB::table('bistro_articoli')->insertGetId($dati);
                $this->salvaRicetta((int) $id, $magazzinoId, $quantita);
            });

            return back()->with('ok', 'Articolo aggiunto al listino con successo.');

        } catch (ValidationException $e) {
            throw $e;

        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'bistro' => 'ERRORE DI SALVATAGGIO: ' . $e->getMessage(),
                ]);
        }
    }

    public function articoloAggiorna(Request $request, int $articolo)
    {
        $this->soloGestori();

        try {
            $dati = $this->validaArticolo($request);
            $magazzinoId = $dati['_ricetta_magazzino_id'];
            $quantita = $dati['_quantita_per_vendita'];
            unset($dati['_ricetta_magazzino_id'], $dati['_quantita_per_vendita']);

            $dati['attivo'] = $request->boolean('attivo');
            $dati['updated_at'] = now();

            $aggiornato = DB::transaction(function () use ($articolo, $dati, $magazzinoId, $quantita) {
                $count = DB::table('bistro_articoli')
                    ->where('id', $articolo)
                    ->update($dati);

                if ($count || DB::table('bistro_articoli')->where('id', $articolo)->exists()) {
                    $this->salvaRicetta($articolo, $magazzinoId, $quantita);
                    return true;
                }

                return false;
            });

            if (!$aggiornato) {
                return back()
                    ->withInput()
                    ->withErrors(['bistro' => 'Articolo non trovato.']);
            }

            return back()->with('ok', 'Articolo e collegamento magazzino aggiornati con successo.');

        } catch (ValidationException $e) {
            throw $e;

        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'bistro' => 'ERRORE DI AGGIORNAMENTO: ' . $e->getMessage(),
                ]);
        }
    }

    public function tavoloCrea(Request $request)
    {
        $this->soloGestori();

        $dati = $request->validate([
            'zona' => 'required|in:' . implode(',', array_keys(self::ZONE)),
            'numero' => 'required|integer|min:1|max:999',
        ]);

        $esistente = DB::table('bistro_tavoli')
            ->where('zona', $dati['zona'])
            ->where('numero', $dati['numero'])
            ->first();

        if ($esistente) {
            DB::table('bistro_tavoli')
                ->where('id', $esistente->id)
                ->update([
                    'attivo' => true,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('bistro_tavoli')->insert(
                $dati + [
                    'attivo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return back()->with(
            'ok',
            'Tavolo ' . $dati['numero'] . ' disponibile.'
        );
    }

    public function tavoloDisattiva(int $tavolo)
    {
        $this->soloGestori();

        if ($this->comandaAperta($tavolo)) {
            return back()->withErrors([
                'bistro' => 'Il tavolo ha una comanda aperta.',
            ]);
        }

        DB::table('bistro_tavoli')
            ->where('id', $tavolo)
            ->update([
                'attivo' => false,
                'updated_at' => now(),
            ]);

        return back()->with('ok', 'Tavolo disattivato.');
    }

    public function scorte(Request $request)
    {
        $this->consenti();

        $sezioni = [
            'Bar' => self::CASSA,
            'Ristorante' => self::RISTORANTE,
        ];

        $sezione = $request->query('sezione', 'Bar');

        $categorieMostrate = $sezioni[$sezione] ?? self::CASSA;

        $categoriaAttiva = $request->query(
            'categoria',
            array_key_first($categorieMostrate)
        );

        $reparti = collect([
            (object) [
                'id' => 0,
                'nome' => 'Magazzino Centrale',
            ],
        ]);

        $rep = $reparti->first();

        $articoli = DB::table('bistro_articoli as ba')
            ->leftJoin('bistro_ricette as br', 'ba.id', '=', 'br.bistro_articolo_id')
            ->leftJoin('articoli_magazzino as am', 'br.magazzino_articolo_id', '=', 'am.id')
            ->where('ba.attivo', true)
            ->where('ba.categoria', $categoriaAttiva)
            ->orderBy('ba.nome')
            ->select(
                'ba.id as articolo_id',
                'ba.nome',
                'ba.categoria',
                'ba.prezzo',
                'am.id as magazzino_id',
                'am.nome as magazzino_nome',
                'br.quantita as quantita_per_vendita',
                'am.unita_misura',
                'am.scorta_attuale as centrale',
                'am.scorta_attuale as scorta',
                'am.soglia_riordino as soglia',
                'am.quantita_standard_ordine as quantita_rifornimento'
            )
            ->get();

        return view('bistro.scorte', [
            'sezioni' => $sezioni,
            'sezione' => $sezione,
            'categorie' => $categorieMostrate,
            'categoriaAttiva' => $categoriaAttiva,
            'reparti' => $reparti,
            'rep' => $rep,
            'articoli' => $articoli,
            'righe' => $articoli,
            'inArrivo' => collect(),
            'recenti' => collect(),
            'richieste' => collect(),
            'usati' => collect(),
        ]);
    }

    public function incassi(Request $request)
    {
        $this->soloGestori();

        $dal = Carbon::parse(
            $request->query('dal', now()->toDateString())
        )->toDateString();

        $al = Carbon::parse(
            $request->query('al', now()->toDateString())
        )->toDateString();

        $da = $dal . ' 00:00:00';
        $a = $al . ' 23:59:59';

        return view('bistro.incassi', [
            'dal' => $dal,
            'al' => $al,
            'perMetodo' => DB::table('bistro_pagamenti')
                ->whereBetween('created_at', [$da, $a])
                ->selectRaw('metodo, count(*) as n, sum(importo) as totale')
                ->groupBy('metodo')
                ->get()
                ->keyBy('metodo'),
            'perAliquota' => DB::table('bistro_pagamento_iva as i')
                ->join('bistro_pagamenti as p', 'p.id', '=', 'i.pagamento_id')
                ->whereBetween('p.created_at', [$da, $a])
                ->selectRaw(
                    'p.metodo, i.aliquota, sum(i.lordo) as lordo, sum(i.imponibile) as imponibile, sum(i.imposta) as imposta'
                )
                ->groupBy('p.metodo', 'i.aliquota')
                ->orderBy('i.aliquota')
                ->get(),
        ]);
    }

    public function addebitiIva()
    {
        $this->soloGestori();

        $frontDesk = app(FrontDeskController::class);

        $voci = DB::table('voci_conto')
            ->where('folio', 'B')
            ->where('riferimento', 'like', 'bistro:%')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(function ($voce) use ($frontDesk) {
                $voce->aliquota = preg_match(
                    '/:(\d+(?:\.\d+)?)$/',
                    (string) $voce->riferimento,
                    $match
                ) ? (float) $match[1] : null;

                $voce->bloccata = $frontDesk->folioBloccato(
                    $voce->conto_id,
                    'B'
                );

                return $voce;
            });

        return view('bistro.addebiti-iva', [
            'voci' => $voci,
        ]);
    }

    public function addebitoIvaSalva(Request $request, int $voce)
    {
        $this->soloGestori();

        $dati = $request->validate([
            'aliquota' => 'required|numeric|min:0|max:100',
        ]);

        $voceConto = DB::table('voci_conto')
            ->where('id', $voce)
            ->where('folio', 'B')
            ->where('riferimento', 'like', 'bistro:%')
            ->first() ?? abort(404);

        $bloccato = app(FrontDeskController::class)->folioBloccato(
            $voceConto->conto_id,
            'B'
        );

        if ($bloccato) {
            return back()->withErrors([
                'bistro' => 'Il folio B ha già una fattura emessa.',
            ]);
        }

        $nuovoRiferimento = preg_replace(
            '/:\d+(?:\.\d+)?$/',
            ':' . $this->fmtAliquota((float) $dati['aliquota']),
            (string) $voceConto->riferimento
        );

        DB::table('voci_conto')
            ->where('id', $voceConto->id)
            ->update([
                'riferimento' => $nuovoRiferimento,
                'updated_at' => now(),
            ]);

        return back()->with(
            'ok',
            'Aliquota aggiornata al ' . $this->fmtAliquota((float) $dati['aliquota']) . '%.'
        );
    }
}