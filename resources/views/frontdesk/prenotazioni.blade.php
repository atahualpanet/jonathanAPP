@php
    app()->setLocale(session('locale', 'it'));
    $ultimoGiorno = $periodoGiorni[count($periodoGiorni) - 1];
    $tutteLeCamere = $camerePerPiano->collapse();

    // Camere da pulire: se in DB esistono altri stati (es. 'in_pulizia') basta aggiungerli qui
    $statiDaPulire = ['sporca', 'in_pulizia'];
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — FrontDesk')
@section('page_title', 'Frontdesk')
@section('page_color', '#4F86B0')

@section('content')

@include('frontdesk.partials.messaggi')

{{-- ===== KPI + NAVIGAZIONE: un'unica riga, KPI a sinistra e il resto allineato a destra ===== --}}
<div class="flex items-center justify-between gap-4 mb-6 overflow-x-auto whitespace-nowrap pb-1">

    <div class="shrink-0 flex items-center gap-4">
        @foreach ([['Arrivi oggi', $arriviOggi], ['Partenze', $partenzeOggi], ['In casa', $inCasaCount]] as [$etichetta, $valore])
            <div class="bg-white rounded-2xl border border-gray-200 px-5 py-3">
                <p class="text-xs text-slate-500">{{ $etichetta }}</p>
                <p class="text-xl font-medium leading-tight">{{ $valore }}</p>
            </div>
        @endforeach
    </div>

    <div class="shrink-0 flex items-center gap-2 text-sm">
        <a href="{{ route('frontdesk.prenotazioni', ['data' => $dataInizio->copy()->subDays($settimane * 7)->format('Y-m-d'), 'settimane' => $settimane]) }}"
           class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg font-medium">&larr;</a>
        <span class="text-slate-500 px-1">{{ $dataInizio->format('d/m') }} – {{ $ultimoGiorno->format('d/m/Y') }}</span>
        <a href="{{ route('frontdesk.prenotazioni', ['data' => $dataInizio->copy()->addDays($settimane * 7)->format('Y-m-d'), 'settimane' => $settimane]) }}"
           class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg font-medium">&rarr;</a>
        <a href="{{ route('frontdesk.prenotazioni') }}"
           class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg font-medium text-slate-600">Oggi</a>

        <div class="flex bg-white border border-gray-200 rounded-lg overflow-hidden ml-1">
            @foreach ([1 => '1 sett.', 2 => '2 sett.', 3 => '3 sett.'] as $n => $etichetta)
                <a href="{{ route('frontdesk.prenotazioni', ['data' => $dataInizio->format('Y-m-d'), 'settimane' => $n]) }}"
                   @class(['px-3 py-2 font-medium', 'bg-deepNavy text-white' => $settimane === $n, 'text-slate-600 hover:bg-gray-50' => $settimane !== $n])>{{ $etichetta }}</a>
            @endforeach
        </div>

        <a href="{{ route('occupazione.mensile') }}"
           class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg font-medium text-slate-600 ml-1">Vista mensile</a>
        <a href="{{ route('adempimenti.mensile') }}"
           class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg font-medium text-slate-600 ml-1">Adempimenti</a>

        <span class="w-px h-9 bg-gray-200 ml-1"></span>

        <a href="{{ route('prenotazioni.nuova', ['data' => $dataInizio->format('Y-m-d')]) }}"
           class="bg-fdBlue hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg ml-1">+ Nuova prenotazione</a>
    </div>
</div>

{{-- ===== GRIGLIA ===== --}}
<div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table data-tableau data-url-sposta="{{ route('prenotazioni.sposta', ['prenotazione' => '__ID__']) }}"
               data-url-scheda="{{ route('prenotazioni.scheda', ['prenotazione' => '__ID__']) }}"
               class="w-full table-fixed border-collapse min-w-[820px]">
            <colgroup>
                <col class="col-camera">
                @foreach ($periodoGiorni as $giorno)
                    <col @class(['bg-cream' => $giorno->isToday()])>
                    <col @class(['bg-cream' => $giorno->isToday()])>
                @endforeach
            </colgroup>

            <thead>
                <tr class="border-b border-gray-200 text-slate-500">
                    <th class="h-11"></th>
                    @foreach ($periodoGiorni as $giorno)
                        <th colspan="2" data-giorno="{{ $giorno->format('Y-m-d') }}" class="h-11 text-center font-normal text-[15px]">
                            {{ ucfirst($giorno->translatedFormat('D')) }} {{ $giorno->format('j') }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach ($tutteLeCamere as $camera)
                    @php
                        $sporca = in_array($camera->stato_pulizia, $statiDaPulire, true);
                    @endphp
                    <tr data-camera="{{ $camera->id }}" @if ($camera->stato_pulizia === 'manutenzione') data-lavori="1" @endif
                        @class(['border-b border-gray-200 last:border-b-0 h-[60px]', 'riga-sporca' => $sporca])>
                        @php
                            $tipNome   = $camera->tipologia->nome ?? 'Standard';
                            $tipCodice = strtoupper($camera->tipologia->codice ?? '');
                            // Badge corto ed elegante: preferisce il codice (max 4 char), altrimenti deriva dal nome
                            $badge = match (true) {
                                $tipCodice !== '' && strlen($tipCodice) <= 4 => $tipCodice,
                                str_contains(strtolower($tipNome), 'suite')  => 'SUITE',
                                str_contains(strtolower($tipNome), 'singol') => 'SGL',
                                str_contains(strtolower($tipNome), 'doppi') || str_contains(strtolower($tipNome), 'matrimon') => 'DBL',
                                str_contains(strtolower($tipNome), 'tripl')  => 'TPL',
                                str_contains(strtolower($tipNome), 'family') || str_contains(strtolower($tipNome), 'familiare') => 'FAM',
                                default => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $tipNome), 0, 3)) ?: 'STD',
                            };
                            $titleCella = $tipNome . ($sporca ? ' · Da pulire' : '');
                        @endphp
                        <td class="px-4 text-[15px]" title="{{ $titleCella }}">
                            <div class="flex items-center gap-2">
                                <span class="font-medium tabular-nums">{{ $camera->numero }}</span>
                                <span class="inline-flex items-center justify-center min-w-[1.6rem] h-[1.15rem] px-1.5 rounded text-[10px] font-semibold tracking-wide
                                             bg-slate-100 text-slate-500 border border-slate-200/80 leading-none select-none"
                                      title="{{ $tipNome }}">{{ $badge }}</span>
                            </div>
                        </td>

                        @foreach ($righe[$camera->id] as $seg)
                            @if ($seg['tipo'] === 'prenotazione')
                                @php
                                    $p = $seg['p'];
                                    $colore = match ($p->stato) {
                                        'in_casa'    => 'bg-okGreen text-white',
                                        'confermata' => 'bg-fdBlue text-white',
                                        'partita'    => 'bg-slate-300 text-slate-700',
                                        default      => 'bg-warmGold text-white', // in_attesa = opzione
                                    };
                                    $tooltip = ($p->ospite->cognome ?? 'Ospite') . ' · ' . $p->codice
                                        . ' · ' . $p->adulti . ' adulti · '
                                        . $p->data_arrivo->format('d/m') . '–' . $p->data_partenza->format('d/m');
                                @endphp
                                <td colspan="{{ $seg['span'] }}" class="p-1 align-middle border-l border-gray-100">
                                    @php
                                        // chi può fare cosa: all = sposta + allunga/accorcia, end = solo partenza, none = bloccata
                                        $drag = match ($p->stato) { 'partita' => 'none', 'in_casa' => 'move', default => 'all' };
                                    @endphp
                                    <div title="{{ $tooltip }}"
                                         data-pren="{{ $p->id }}" data-camera="{{ $seg['camera'] }}"
                                         data-dal="{{ $seg['dal'] }}" data-al="{{ $seg['al'] }}" data-drag="{{ $drag }}"
                                         @class([
                                             'relative select-none touch-none h-10 leading-10 px-3 text-[15px] truncate hover:opacity-90 transition-opacity',
                                             $colore,
                                             'cursor-grab' => in_array($drag, ['all', 'move'], true),
                                             'rounded-lg' => ! $seg['da_sinistra'] && ! $seg['a_destra'],
                                             'rounded-l-none rounded-r-lg' => $seg['da_sinistra'] && ! $seg['a_destra'],
                                             'rounded-l-lg rounded-r-none' => ! $seg['da_sinistra'] && $seg['a_destra'],
                                             'rounded-none' => $seg['da_sinistra'] && $seg['a_destra'],
                                         ])>
                                        @if ($drag === 'all' && ! $seg['da_sinistra'])
                                            <span data-handle="start" class="absolute left-0 top-0 h-full w-2.5 cursor-ew-resize"></span>
                                        @endif
                                        <span class="pointer-events-none">{{ $p->ospite->cognome ?? 'Ospite' }}</span>
                                        @if ($drag !== 'none' && ! $seg['a_destra'])
                                            <span data-handle="end" class="absolute right-0 top-0 h-full w-2.5 cursor-ew-resize"></span>
                                        @endif
                                    </div>
                                </td>

                            @elseif ($seg['tipo'] === 'lavori')
                                <td colspan="{{ $seg['span'] }}" class="p-1 align-middle border-l border-gray-100">
                                    <div class="h-10 leading-10 px-3 rounded-lg text-[15px] bg-[#B3B8C2] text-white truncate">Lavori</div>
                                </td>

                            @else
                                <td @if ($seg['span'] === 2) colspan="2" @endif @class(['p-1 align-middle', 'border-l border-gray-100' => $seg['meta'] !== 'pm'])>
                                    @if ($seg['meta'] === 'am')
                                        {{-- mattina libera prima di un arrivo: la camera si prenota dal pomeriggio --}}
                                    @elseif ($seg['giorno']->lt(\Carbon\Carbon::today()))
                                        {{-- giorno passato: non si può prenotare --}}
                                        <span class="cella-passata" title="Giorno passato"></span>
                                    @else
                                        <a href="{{ route('prenotazioni.nuova', ['data' => $seg['giorno']->format('Y-m-d'), 'camera' => $camera->id]) }}"
                                           class="cella-vuota"
                                           aria-label="Nuova prenotazione, camera {{ $camera->numero }}, {{ $seg['giorno']->format('d/m') }}"
                                           title="Nuova prenotazione · camera {{ $camera->numero }} · {{ $seg['giorno']->format('d/m') }}"></a>
                                    @endif
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ===== LEGENDA ===== --}}
<div class="flex items-center gap-12 mt-8 px-1 text-[15px] text-slate-500">
    <span class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded-full bg-okGreen"></span> In casa</span>
    <span class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded-full bg-fdBlue"></span> In arrivo</span>
    <span class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded-full bg-warmGold"></span> Opzione</span>
    <span class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded-full bg-slate-300"></span> Partita</span>
    <span class="flex items-center gap-2"><span class="hk-legenda"></span> Da pulire</span>
</div>

<p class="mt-4 px-1 text-sm text-slate-400">In ogni giorno la metà sinistra è la mattina (partenze), la metà destra il pomeriggio (arrivi): partenza e arrivo dello stesso giorno compaiono uno dopo l'altro. Clicca un giorno libero per creare una prenotazione. Trascina una barra per spostarla (anche su un'altra camera); trascina i bordi per allungare o accorciare il soggiorno. Esc annulla. Le camere da pulire hanno il numero su sfondo marrone chiaro.</p>
@endsection

@push('scripts')
<script>
(function () {
    const table = document.querySelector('[data-tableau]');
    if (!table) return;

    const ths   = [...table.querySelectorAll('thead th[data-giorno]')];
    const giorni = ths.map(t => t.dataset.giorno);
    const rows  = [...table.querySelectorAll('tbody tr[data-camera]')];
    const csrf  = document.querySelector('meta[name="csrf-token"]').content;
    const urlSposta = id => table.dataset.urlSposta.replace('__ID__', id);

    // ---- date (UTC, niente problemi di ora legale) ----
    const toMs = s => { const [y, m, d] = s.split('-').map(Number); return Date.UTC(y, m - 1, d); };
    const addDays = (s, n) => new Date(toMs(s) + n * 86400000).toISOString().slice(0, 10);
    const diff = (a, b) => Math.round((toMs(b) - toMs(a)) / 86400000);
    const breve = s => s.slice(8, 10) + '/' + s.slice(5, 7);

    // ---- geometria ----
    function dayIndexAt(x) {
        const first = ths[0].getBoundingClientRect(), last = ths[ths.length - 1].getBoundingClientRect();
        if (x < first.left) return 0;
        if (x >= last.right) return ths.length - 1;
        for (let i = 0; i < ths.length; i++) {
            const r = ths[i].getBoundingClientRect();
            if (x >= r.left && x < r.right) return i;
        }
        return 0;
    }
    // giorno il cui centro è più vicino a x: la barra inizia/finisce a metà giornata
    function nearestDayAt(x) {
        let best = 0, bestDist = Infinity;
        ths.forEach((th, i) => {
            const r = th.getBoundingClientRect();
            const dist = Math.abs(x - (r.left + r.right) / 2);
            if (dist < bestDist) { bestDist = dist; best = i; }
        });
        return best;
    }
    function rowAt(y) {
        for (const tr of rows) {
            const r = tr.getBoundingClientRect();
            if (y >= r.top && y < r.bottom) return tr;
        }
        return null;
    }

    // ---- anteprima + messaggi ----
    const preview = document.createElement('div');
    preview.className = 'tableau-anteprima';
    document.body.appendChild(preview);

    function toast(msg, errore) {
        const t = document.createElement('div');
        t.textContent = msg;
        t.className = 'toast' + (errore ? ' is-errore' : '');
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 4000);
    }

    // ---- stato del drag ----
    let d = null;

    const urlScheda = id => table.dataset.urlScheda.replace('__ID__', id);

    // Click semplice sulla barra (senza trascinare) = apre la scheda della prenotazione
    table.addEventListener('click', e => {
        if (table.dataset.ultimoDrag === 'si') { table.dataset.ultimoDrag = 'no'; return; }
        const bar = e.target.closest('[data-pren]');
        if (bar) window.location.href = urlScheda(bar.dataset.pren);
    });

    table.addEventListener('pointerdown', e => {
        if (e.button !== 0) return;
        const bar = e.target.closest('[data-pren]');
        if (!bar) return;
        const handle = e.target.closest('[data-handle]');
        const puoTrascinare = bar.dataset.drag !== 'none';
        const mode = puoTrascinare ? (handle ? 'resize-' + handle.dataset.handle : (['all', 'move'].includes(bar.dataset.drag) ? 'move' : null)) : null;

        // Barra non trascinabile (es. partita): un click la apre comunque, nessun drag da gestire
        if (!mode) { return; }

        e.preventDefault();
        d = {
            el: bar, mode, pren: bar.dataset.pren, camera: bar.dataset.camera,
            dal: bar.dataset.dal, al: bar.dataset.al,
            startIdx: dayIndexAt(e.clientX), x0: e.clientX, y0: e.clientY,
            moved: false, target: null
        };
        bar.setPointerCapture(e.pointerId);
        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);
        window.addEventListener('pointercancel', annulla);
        window.addEventListener('keydown', onKey);
    });

    function calcolaTarget(e) {
        const idx = dayIndexAt(e.clientX);
        const idxMid = nearestDayAt(e.clientX);
        let camera = d.camera, dal = d.dal, al = d.al;

        if (d.mode === 'move') {
            const delta = idx - d.startIdx;
            dal = addDays(d.dal, delta);
            al  = addDays(d.al, delta);
            const tr = rowAt(e.clientY);
            if (tr) camera = tr.dataset.camera;
        } else if (d.mode === 'resize-start') {
            dal = giorni[idxMid];                               // arrivo = giorno sotto il bordo sinistro
            if (dal >= d.al) dal = addDays(d.al, -1);          // minimo 1 notte
        } else { // resize-end
            al = giorni[idxMid];                                // partenza = giorno sotto il bordo destro
            if (al <= d.dal) al = addDays(d.dal, 1);
        }

        // controllo rapido lato client (il server ricontrolla comunque tutto)
        let errore = null;
        const trDest = rows.find(r => r.dataset.camera === String(camera));
        if (trDest && trDest.dataset.lavori && camera !== d.camera) errore = 'Camera in lavori';
        if (!errore) {
            const altro = [...table.querySelectorAll('[data-pren]')].find(b =>
                b !== d.el && b.dataset.camera === String(camera) && b.dataset.dal < al && b.dataset.al > dal);
            if (altro) errore = 'Camera occupata';
        }
        return { camera: String(camera), dal, al, errore };
    }

    function disegna(t) {
        const iDal = diff(giorni[0], t.dal), iAl = diff(giorni[0], t.al);
        const tr = rows.find(r => r.dataset.camera === t.camera);
        if (iDal >= giorni.length || iAl < 0 || !tr) { preview.classList.remove('is-visibile'); return; }
        const first = ths[0].getBoundingClientRect(), last = ths[ths.length - 1].getBoundingClientRect();
        const mid = th => { const q = th.getBoundingClientRect(); return (q.left + q.right) / 2; };
        const left  = iDal < 0 ? first.left : mid(ths[iDal]);          // arrivo: metà giornata (pomeriggio)
        const right = iAl >= giorni.length ? last.right : mid(ths[iAl]); // partenza: metà giornata (mattina)
        const r = tr.getBoundingClientRect();
        const notti = diff(t.dal, t.al);
        if (right <= left) { preview.classList.remove('is-visibile'); return; }
        preview.classList.add('is-visibile');
        preview.style.left = (left + 2) + 'px';
        preview.style.width = (right - left - 4) + 'px';
        preview.style.top = (r.top + 4) + 'px';
        preview.style.height = (r.height - 8) + 'px';
        preview.classList.toggle('is-errore', !!t.errore);
        preview.textContent = t.errore ? t.errore : breve(t.dal) + ' → ' + breve(t.al) + ' · ' + notti + (notti === 1 ? ' notte' : ' notti');
    }

    function onMove(e) {
        if (!d) return;
        if (!d.moved && Math.hypot(e.clientX - d.x0, e.clientY - d.y0) < 5) return;
        if (!d.moved) {
            d.moved = true;
            d.el.classList.add('tableau-barra-trascinata');
            document.body.classList.add(d.mode === 'move' ? 'cursore-grabbing' : 'cursore-resize');
        }
        d.target = calcolaTarget(e);
        disegna(d.target);
    }

    function pulisci() {
        if (d) { d.el.classList.remove('tableau-barra-trascinata'); }
        preview.classList.remove('is-visibile');
        document.body.classList.remove('cursore-grabbing', 'cursore-resize');
        window.removeEventListener('pointermove', onMove);
        window.removeEventListener('pointerup', onUp);
        window.removeEventListener('pointercancel', annulla);
        window.removeEventListener('keydown', onKey);
        d = null;
    }
    function annulla() { pulisci(); }
    function onKey(e) { if (e.key === 'Escape') annulla(); }

    function mostraCaricamento(bar) {
        const overlay = document.createElement('div');
        overlay.dataset.overlayAggiornamento = '1';
        overlay.className = 'tableau-overlay';
        overlay.innerHTML = '<span class="inline-block w-3 h-3 rounded-full border-2 border-white/40 border-t-white animate-spin"></span> Aggiornamento…';
        bar.appendChild(overlay);
    }

    function onUp() {
        if (!d) return;
        const { el, moved, target, pren } = d, orig = { camera: d.camera, dal: d.dal, al: d.al };
        pulisci();
        if (!moved) return;
        table.dataset.ultimoDrag = 'si'; // il click che segue il drag non deve aprire la scheda
        if (!target) return;
        if (target.camera === orig.camera && target.dal === orig.dal && target.al === orig.al) return;
        if (target.errore) { toast(target.errore, true); return; }

        mostraCaricamento(el);
        fetch(urlSposta(pren), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                       'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ camera_origine: orig.camera, camera_id: target.camera,
                                   data_arrivo: target.dal, data_partenza: target.al })
        })
        .then(async r => {
            const j = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(j.message || 'Operazione non riuscita');
            location.reload();
        })
        .catch(err => {
            el.querySelector('[data-overlay-aggiornamento]')?.remove();
            toast(err.message, true);
        });
    }
})();
</script>
@endpush
