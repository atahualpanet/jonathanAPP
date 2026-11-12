<?php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.');
    $isCredito = fn ($v) => in_array($v->tipo, ['pagamento', 'caparra'], true);
?>



<?php $__env->startSection('title', 'Jonathan PMS — Folio'); ?>
<?php $__env->startSection('page_title', 'Folio · ' . ($prenotazione->camere->first()->numero ?? '—')); ?>
<?php $__env->startSection('page_color', '#4F86B0'); ?>

<?php $__env->startSection('content'); ?>

<a href="<?php echo e(route('prenotazioni.scheda', $prenotazione)); ?>" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">
    &larr; Torna alla prenotazione
</a>

<?php echo $__env->make('frontdesk.partials.messaggi', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php if (! ($conto)): ?>
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <p class="text-slate-600 mb-4">Nessun conto aperto per questa prenotazione.</p>
        <form method="POST" action="<?php echo e(route('prenotazioni.folio.apri', $prenotazione)); ?>">
            <?php echo csrf_field(); ?>
            <button class="bg-fdBlue hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Apri conto</button>
        </form>
    </div>
<?php else: ?>
    <?php $aperto = $conto->stato === 'aperto'; ?>

    <div class="flex items-center justify-between gap-4 mb-4">
        <p class="text-sm text-slate-500">
            <?php if($aperto): ?>
                Trascina le voci da un folio all'altro per dividere il conto (oppure usa la freccia accanto alla voce).
            <?php else: ?>
                Conto chiuso: le voci non si possono più modificare.
            <?php endif; ?>
        </p>
        <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-xs font-medium px-2.5 py-1 rounded-full capitalize shrink-0', 'bg-okGreen/15 text-okGreen' => $aperto, 'bg-slate-200 text-slate-600' => ! $aperto]); ?>">
            Conto <?php echo e($conto->codice); ?> · <?php echo e($conto->stato); ?>

        </span>
    </div>

    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <?php $__currentLoopData = $riepilogo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $fattura = $fatture->get($f->chiave);
                $bloccato = $fattura && $fattura->stato === 'emessa';
                $trascinabile = $aperto && ! $bloccato;
                $addebiti = $f->voci->reject($isCredito);
                $crediti = $f->voci->filter($isCredito);
                $altro = $f->chiave === 'A' ? 'B' : 'A';
            ?>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 flex flex-col">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-lg font-medium">Folio <?php echo e($f->chiave); ?> · <?php echo e($f->nome); ?></h2>
                    <?php if($bloccato): ?>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-okGreen text-white">Fattura <?php echo e($fattura->numero); ?></span>
                    <?php elseif($fattura): ?>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-warmGold text-white">Fattura differita</span>
                    <?php endif; ?>
                </div>

                <div class="folio-zona space-y-2" data-folio-zona="<?php echo e($f->chiave); ?>" <?php if(! $trascinabile): ?> data-bloccato="1" <?php endif; ?>>
                    <?php $__currentLoopData = $addebiti; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['flex items-center justify-between gap-3 bg-slate-50 rounded-lg px-4 py-3 text-[15px]', 'cursor-grab' => $trascinabile]); ?>"
                             <?php if($trascinabile): ?>
                                 draggable="true" data-voce="<?php echo e($v->id); ?>" data-folio="<?php echo e($f->chiave); ?>"
                                 data-url="<?php echo e(route('prenotazioni.folio.voce.sposta', [$prenotazione, $v->id])); ?>"
                             <?php endif; ?>>
                            <span><?php echo e($v->descrizione); ?></span>
                            <span class="flex items-center gap-3 shrink-0">
                                <span>€ <?php echo e($eur($v->importo)); ?></span>
                                <?php if($trascinabile): ?>
                                    <?php if($v->tipo === 'servizio' && ! $f->voci->pluck('riferimento')->contains(fn ($r) => str_starts_with((string) $r, 'storno:' . $v->id))): ?>
                                        <form method="POST" action="<?php echo e(route('prenotazioni.folio.voce.storna', [$prenotazione, $v->id])); ?>">
                                            <?php echo csrf_field(); ?>
                                            <button class="text-xs font-medium text-slate-400 hover:text-red-600" title="Storna questa spesa" aria-label="Storna">storna</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="<?php echo e(route('prenotazioni.folio.voce.sposta', [$prenotazione, $v->id])); ?>">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="folio" value="<?php echo e($altro); ?>">
                                        <button class="text-xs font-medium text-slate-400 hover:text-fdBlue" title="Sposta nel folio <?php echo e($altro); ?>" aria-label="Sposta nel folio <?php echo e($altro); ?>">&rarr; <?php echo e($altro); ?></button>
                                    </form>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <?php if($trascinabile): ?>
                        <div class="folio-rilascia">Trascina qui una voce dall'altro folio</div>
                    <?php elseif($addebiti->isEmpty()): ?>
                        <p class="text-sm text-slate-400 py-2">Nessuna voce.</p>
                    <?php endif; ?>
                </div>

                <div class="mt-auto pt-5">
                    <div class="border-t border-gray-100 pt-4 space-y-1.5 text-[15px]">
                        <div class="flex justify-between"><span class="text-slate-500">Totale</span><span>€ <?php echo e($eur($f->addebiti)); ?></span></div>
                        <?php $__currentLoopData = $crediti; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex justify-between text-okGreen"><span><?php echo e($v->descrizione); ?></span><span>- € <?php echo e($eur($v->importo)); ?></span></div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex justify-between text-lg font-medium pt-2"><span>Saldo</span><span>€ <?php echo e($eur($f->saldo)); ?></span></div>
                    </div>

                    
                    <?php if($bloccato): ?>
                        <a href="<?php echo e(route('prenotazioni.folio.fattura', [$prenotazione, $f->chiave])); ?>"
                           class="inline-block mt-4 bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">
                            Apri fattura <?php echo e($fattura->numero); ?>

                        </a>
                    <?php elseif($f->addebiti > 0): ?>
                        <details class="mt-4 border border-gray-200 rounded-xl p-4">
                            <summary class="cursor-pointer text-sm font-medium">Fatturazione folio <?php echo e($f->chiave); ?></summary>
                            <form method="POST" action="<?php echo e(route('prenotazioni.folio.fattura.emetti', [$prenotazione, $f->chiave])); ?>"
                                  class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <?php echo csrf_field(); ?>
                                <input name="intestatario" placeholder="Intestatario" maxlength="150"
                                       value="<?php echo e($fattura->intestatario ?? trim($prenotazione->ospite->nome . ' ' . $prenotazione->ospite->cognome)); ?>"
                                       class="sm:col-span-2 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                <input name="partita_iva" placeholder="Partita IVA" maxlength="20" value="<?php echo e($fattura->partita_iva ?? ''); ?>"
                                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                <input name="codice_fiscale" placeholder="Codice fiscale" maxlength="20" value="<?php echo e($fattura->codice_fiscale ?? ''); ?>"
                                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                <input name="codice_destinatario" placeholder="Codice destinatario SDI (7 caratteri)" maxlength="7" value="<?php echo e($fattura->codice_destinatario ?? ''); ?>"
                                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                <input name="pec" type="email" placeholder="PEC" maxlength="150" value="<?php echo e($fattura->pec ?? ''); ?>"
                                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                <input name="indirizzo" placeholder="Indirizzo di fatturazione" maxlength="255" value="<?php echo e($fattura->indirizzo ?? ''); ?>"
                                       class="sm:col-span-2 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                <div class="sm:col-span-2 flex flex-wrap gap-2 pt-1">
                                    <button name="azione" value="differisci"
                                            class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Fattura differita</button>
                                    <button name="azione" value="emetti"
                                            class="bg-fdBlue hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Emetti fattura</button>
                                </div>
                            </form>
                        </details>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <?php if($aperto): ?>
        <div class="flex flex-wrap items-center gap-2 mt-6">
            <form method="POST" action="<?php echo e(route('prenotazioni.folio.unisci', $prenotazione)); ?>">
                <?php echo csrf_field(); ?>
                <button class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Unisci conti</button>
            </form>
            <form method="POST" action="<?php echo e(route('prenotazioni.folio.chiudi', $prenotazione)); ?>">
                <?php echo csrf_field(); ?>
                <button class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Chiudi conto</button>
            </form>
            <span class="ml-auto text-lg font-medium">Saldo totale € <?php echo e($eur($saldo)); ?></span>
        </div>

        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="text-base font-medium mb-3">Aggiungi una spesa al conto</h2>
                <div class="flex flex-wrap gap-2 mb-3">
                    <?php $__empty_1 = true; $__currentLoopData = $listino; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <button type="button" data-preset="<?php echo e($a->nome); ?>" data-prezzo="<?php echo e($a->prezzo); ?>"
                                class="text-xs font-medium px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600"><?php echo e($a->nome); ?> · € <?php echo e($eur($a->prezzo)); ?></button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <?php $__currentLoopData = ['Cena Bistro', 'Colazione', 'Lavanderia', 'Parcheggio']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $preset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <button type="button" data-preset="<?php echo e($preset); ?>"
                                    class="text-xs font-medium px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600"><?php echo e($preset); ?></button>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('minibar.listino')); ?>" class="text-xs font-medium px-3 py-1.5 text-fdBlue underline">Crea il listino minibar</a>
                    <?php endif; ?>
                </div>
                <form method="POST" action="<?php echo e(route('prenotazioni.folio.voce', $prenotazione)); ?>" class="flex flex-wrap gap-2">
                    <?php echo csrf_field(); ?>
                    <input name="descrizione" id="voce-descrizione" placeholder="Descrizione" required maxlength="255"
                           class="flex-1 min-w-[160px] border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <input name="importo" id="voce-importo" type="number" step="0.01" min="0.01" placeholder="€" required
                           class="w-28 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <select name="folio" class="border border-gray-200 rounded-lg px-3 py-2 text-sm" aria-label="Folio">
                        <option value="B">Folio B · Extra</option>
                        <option value="A">Folio A · Ospite</option>
                    </select>
                    <button class="bg-fdBlue hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Aggiungi</button>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="text-base font-medium mb-4">Registra pagamento</h2>
                <form method="POST" action="<?php echo e(route('prenotazioni.folio.pagamento', $prenotazione)); ?>" class="flex flex-wrap gap-2">
                    <?php echo csrf_field(); ?>
                    <select name="folio" class="border border-gray-200 rounded-lg px-3 py-2 text-sm" aria-label="Folio">
                        <option value="A">Folio A · Ospite</option>
                        <option value="B">Folio B · Extra</option>
                    </select>
                    <select name="metodo" class="border border-gray-200 rounded-lg px-3 py-2 text-sm" aria-label="Metodo">
                        <option value="carta">Carta</option>
                        <option value="contanti">Contanti</option>
                        <option value="bonifico">Bonifico</option>
                    </select>
                    <input name="importo" type="number" step="0.01" min="0.01" placeholder="€" required
                           class="w-28 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <button class="bg-okGreen hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Registra</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    document.querySelectorAll('[data-preset]').forEach(b => b.addEventListener('click', () => {
        const d = document.getElementById('voce-descrizione');
        if (!d) return;
        d.value = b.dataset.preset;
        const imp = document.getElementById('voce-importo');
        if (b.dataset.prezzo) imp.value = b.dataset.prezzo;
        imp.focus();
    }));
})();
(function () {
    const zone = [...document.querySelectorAll('[data-folio-zona]')];
    if (!zone.length) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let trascinata = null;

    function toast(msg) {
        const t = document.createElement('div');
        t.textContent = msg;
        t.className = 'toast is-errore';
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 4000);
    }
    const pulisci = () => zone.forEach(z => z.classList.remove('folio-drop-attiva'));

    document.querySelectorAll('[data-voce]').forEach(el => {
        el.addEventListener('dragstart', e => {
            trascinata = el;
            el.classList.add('voce-trascinata');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', el.dataset.voce);
        });
        el.addEventListener('dragend', () => {
            el.classList.remove('voce-trascinata');
            pulisci();
            trascinata = null;
        });
    });

    zone.forEach(z => {
        const valida = () => trascinata && !z.dataset.bloccato && trascinata.dataset.folio !== z.dataset.folioZona;

        z.addEventListener('dragover', e => {
            if (!valida()) return;
            e.preventDefault();
            z.classList.add('folio-drop-attiva');
        });
        z.addEventListener('dragleave', e => {
            if (!z.contains(e.relatedTarget)) z.classList.remove('folio-drop-attiva');
        });
        z.addEventListener('drop', e => {
            if (!valida()) return;
            e.preventDefault();
            const voce = trascinata;
            pulisci();
            fetch(voce.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                           'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ folio: z.dataset.folioZona })
            })
            .then(async r => {
                const j = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(j.message || 'Operazione non riuscita');
                location.reload();
            })
            .catch(err => toast(err.message));
        });
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/frontdesk/folio.blade.php ENDPATH**/ ?>