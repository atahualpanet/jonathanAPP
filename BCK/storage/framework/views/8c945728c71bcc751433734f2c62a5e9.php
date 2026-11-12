<?php
    app()->setLocale(session('locale', 'it'));
    $ultimoGiorno = $periodoGiorni[count($periodoGiorni) - 1];
    $tutteLeCamere = $camerePerPiano->collapse();

    // Camere da pulire: se in DB esistono altri stati (es. 'in_pulizia') basta aggiungerli qui
    $statiDaPulire = ['sporca', 'in_pulizia'];
?>



<?php $__env->startSection('title', 'Jonathan PMS — FrontDesk'); ?>
<?php $__env->startSection('page_title', 'Frontdesk'); ?>
<?php $__env->startSection('page_color', '#4F86B0'); ?>

<?php $__env->startSection('content'); ?>

<?php echo $__env->make('frontdesk.partials.messaggi', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


<div class="flex items-center justify-between gap-4 mb-6 overflow-x-auto whitespace-nowrap pb-1">

    <div class="shrink-0 flex items-center gap-4">
        <?php $__currentLoopData = [['Arrivi oggi', $arriviOggi], ['Partenze', $partenzeOggi], ['In casa', $inCasaCount]]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$etichetta, $valore]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bg-white rounded-2xl border border-gray-200 px-5 py-3">
                <p class="text-xs text-slate-500"><?php echo e($etichetta); ?></p>
                <p class="text-xl font-medium leading-tight"><?php echo e($valore); ?></p>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="shrink-0 flex items-center gap-2 text-sm">
        <a href="<?php echo e(route('frontdesk.prenotazioni', ['data' => $dataInizio->copy()->subDays($settimane * 7)->format('Y-m-d'), 'settimane' => $settimane])); ?>"
           class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg font-medium">&larr;</a>
        <span class="text-slate-500 px-1"><?php echo e($dataInizio->format('d/m')); ?> – <?php echo e($ultimoGiorno->format('d/m/Y')); ?></span>
        <a href="<?php echo e(route('frontdesk.prenotazioni', ['data' => $dataInizio->copy()->addDays($settimane * 7)->format('Y-m-d'), 'settimane' => $settimane])); ?>"
           class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg font-medium">&rarr;</a>
        <a href="<?php echo e(route('frontdesk.prenotazioni')); ?>"
           class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg font-medium text-slate-600">Oggi</a>

        <div class="flex bg-white border border-gray-200 rounded-lg overflow-hidden ml-1">
            <?php $__currentLoopData = [1 => '1 sett.', 2 => '2 sett.', 3 => '3 sett.']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n => $etichetta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('frontdesk.prenotazioni', ['data' => $dataInizio->format('Y-m-d'), 'settimane' => $n])); ?>"
                   class="<?php echo \Illuminate\Support\Arr::toCssClasses(['px-3 py-2 font-medium', 'bg-deepNavy text-white' => $settimane === $n, 'text-slate-600 hover:bg-gray-50' => $settimane !== $n]); ?>"><?php echo e($etichetta); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <a href="<?php echo e(route('occupazione.mensile')); ?>"
           class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg font-medium text-slate-600 ml-1">Vista mensile</a>
        <a href="<?php echo e(route('adempimenti.mensile')); ?>"
           class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg font-medium text-slate-600 ml-1">Adempimenti</a>

        <span class="w-px h-9 bg-gray-200 ml-1"></span>

        <a href="<?php echo e(route('prenotazioni.nuova', ['data' => $dataInizio->format('Y-m-d')])); ?>"
           class="bg-fdBlue hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg ml-1">+ Nuova prenotazione</a>
    </div>
</div>


<div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table data-tableau data-url-sposta="<?php echo e(route('prenotazioni.sposta', ['prenotazione' => '__ID__'])); ?>"
               data-url-scheda="<?php echo e(route('prenotazioni.scheda', ['prenotazione' => '__ID__'])); ?>"
               class="w-full table-fixed border-collapse min-w-[820px]">
            <colgroup>
                <col class="col-camera">
                <?php $__currentLoopData = $periodoGiorni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $giorno): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <col class="<?php echo \Illuminate\Support\Arr::toCssClasses(['bg-cream' => $giorno->isToday()]); ?>">
                    <col class="<?php echo \Illuminate\Support\Arr::toCssClasses(['bg-cream' => $giorno->isToday()]); ?>">
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </colgroup>

            <thead>
                <tr class="border-b border-gray-200 text-slate-500">
                    <th class="h-11"></th>
                    <?php $__currentLoopData = $periodoGiorni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $giorno): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <th colspan="2" data-giorno="<?php echo e($giorno->format('Y-m-d')); ?>" class="h-11 text-center font-normal text-[15px]">
                            <?php echo e(ucfirst($giorno->translatedFormat('D'))); ?> <?php echo e($giorno->format('j')); ?>

                        </th>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tr>
            </thead>

            <tbody>
                <?php $__currentLoopData = $tutteLeCamere; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $camera): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $sporca = in_array($camera->stato_pulizia, $statiDaPulire, true);
                    ?>
                    <tr data-camera="<?php echo e($camera->id); ?>" <?php if($camera->stato_pulizia === 'manutenzione'): ?> data-lavori="1" <?php endif; ?>
                        class="<?php echo \Illuminate\Support\Arr::toCssClasses(['border-b border-gray-200 last:border-b-0 h-[60px]', 'riga-sporca' => $sporca]); ?>">
                        <?php
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
                        ?>
                        <td class="px-4 text-[15px]" title="<?php echo e($titleCella); ?>">
                            <div class="flex items-center gap-2">
                                <span class="font-medium tabular-nums"><?php echo e($camera->numero); ?></span>
                                <span class="inline-flex items-center justify-center min-w-[1.6rem] h-[1.15rem] px-1.5 rounded text-[10px] font-semibold tracking-wide
                                             bg-slate-100 text-slate-500 border border-slate-200/80 leading-none select-none"
                                      title="<?php echo e($tipNome); ?>"><?php echo e($badge); ?></span>
                            </div>
                        </td>

                        <?php $__currentLoopData = $righe[$camera->id]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($seg['tipo'] === 'prenotazione'): ?>
                                <?php
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
                                ?>
                                <td colspan="<?php echo e($seg['span']); ?>" class="p-1 align-middle border-l border-gray-100">
                                    <?php
                                        // chi può fare cosa: all = sposta + allunga/accorcia, end = solo partenza, none = bloccata
                                        $drag = match ($p->stato) { 'partita' => 'none', 'in_casa' => 'move', default => 'all' };
                                    ?>
                                    <div title="<?php echo e($tooltip); ?>"
                                         data-pren="<?php echo e($p->id); ?>" data-camera="<?php echo e($seg['camera']); ?>"
                                         data-dal="<?php echo e($seg['dal']); ?>" data-al="<?php echo e($seg['al']); ?>" data-drag="<?php echo e($drag); ?>"
                                         class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                             'relative select-none touch-none h-10 leading-10 px-3 text-[15px] truncate hover:opacity-90 transition-opacity',
                                             $colore,
                                             'cursor-grab' => in_array($drag, ['all', 'move'], true),
                                             'rounded-lg' => ! $seg['da_sinistra'] && ! $seg['a_destra'],
                                             'rounded-l-none rounded-r-lg' => $seg['da_sinistra'] && ! $seg['a_destra'],
                                             'rounded-l-lg rounded-r-none' => ! $seg['da_sinistra'] && $seg['a_destra'],
                                             'rounded-none' => $seg['da_sinistra'] && $seg['a_destra'],
                                         ]); ?>">
                                        <?php if($drag === 'all' && ! $seg['da_sinistra']): ?>
                                            <span data-handle="start" class="absolute left-0 top-0 h-full w-2.5 cursor-ew-resize"></span>
                                        <?php endif; ?>
                                        <span class="pointer-events-none"><?php echo e($p->ospite->cognome ?? 'Ospite'); ?></span>
                                        <?php if($drag !== 'none' && ! $seg['a_destra']): ?>
                                            <span data-handle="end" class="absolute right-0 top-0 h-full w-2.5 cursor-ew-resize"></span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                            <?php elseif($seg['tipo'] === 'lavori'): ?>
                                <td colspan="<?php echo e($seg['span']); ?>" class="p-1 align-middle border-l border-gray-100">
                                    <div class="h-10 leading-10 px-3 rounded-lg text-[15px] bg-[#B3B8C2] text-white truncate">Lavori</div>
                                </td>

                            <?php else: ?>
                                <td <?php if($seg['span'] === 2): ?> colspan="2" <?php endif; ?> class="<?php echo \Illuminate\Support\Arr::toCssClasses(['p-1 align-middle', 'border-l border-gray-100' => $seg['meta'] !== 'pm']); ?>">
                                    <?php if($seg['meta'] === 'am'): ?>
                                        
                                    <?php elseif($seg['giorno']->lt(\Carbon\Carbon::today())): ?>
                                        
                                        <span class="cella-passata" title="Giorno passato"></span>
                                    <?php else: ?>
                                        <a href="<?php echo e(route('prenotazioni.nuova', ['data' => $seg['giorno']->format('Y-m-d'), 'camera' => $camera->id])); ?>"
                                           class="cella-vuota"
                                           aria-label="Nuova prenotazione, camera <?php echo e($camera->numero); ?>, <?php echo e($seg['giorno']->format('d/m')); ?>"
                                           title="Nuova prenotazione · camera <?php echo e($camera->numero); ?> · <?php echo e($seg['giorno']->format('d/m')); ?>"></a>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>


<div class="flex items-center gap-12 mt-8 px-1 text-[15px] text-slate-500">
    <span class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded-full bg-okGreen"></span> In casa</span>
    <span class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded-full bg-fdBlue"></span> In arrivo</span>
    <span class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded-full bg-warmGold"></span> Opzione</span>
    <span class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded-full bg-slate-300"></span> Partita</span>
    <span class="flex items-center gap-2"><span class="hk-legenda"></span> Da pulire</span>
</div>

<p class="mt-4 px-1 text-sm text-slate-400">In ogni giorno la metà sinistra è la mattina (partenze), la metà destra il pomeriggio (arrivi): partenza e arrivo dello stesso giorno compaiono uno dopo l'altro. Clicca un giorno libero per creare una prenotazione. Trascina una barra per spostarla (anche su un'altra camera); trascina i bordi per allungare o accorciare il soggiorno. Esc annulla. Le camere da pulire hanno il numero su sfondo marrone chiaro.</p>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/frontdesk/prenotazioni.blade.php ENDPATH**/ ?>