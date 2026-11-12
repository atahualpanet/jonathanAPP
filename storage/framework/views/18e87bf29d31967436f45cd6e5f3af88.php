<?php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.');
    $colore = config('permessi.moduli.bistro.colore', '#C9822B');
    $ristorante = array_keys(\App\Http\Controllers\BistroController::RISTORANTE);
    $cassa = array_keys(\App\Http\Controllers\BistroController::CASSA);
    $baseUtile = $articoli->whereIn('categoria', $ristorante)->isEmpty() || $articoli->whereIn('categoria', $cassa)->isEmpty();
    $campo = 'border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#C9822B]';
?>



<?php $__env->startSection('title', 'Jonathan PMS — Listino Bistro'); ?>
<?php $__env->startSection('page_title', 'Listino e tavoli Bistro'); ?>
<?php $__env->startSection('page_color', $colore); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full">

    <a href="<?php echo e(route('bistro.mappa')); ?>" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Torna ai tavoli</a>

    <?php echo $__env->make('frontdesk.partials.messaggi', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <h2 class="text-lg font-medium">Listino articoli</h2>
        <?php if($baseUtile): ?>
            <form method="POST" action="<?php echo e(route('bistro.base')); ?>"><?php echo csrf_field(); ?>
                <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Carica listino base (dove manca)</button>
            </form>
        <?php endif; ?>
    </div>
    <p class="text-sm text-slate-500 mb-5 max-w-3xl">Il prezzo viene copiato sulla comanda quando l'articolo è ordinato: se lo cambi qui, le comande già prese restano com'erano. Gli articoli disattivati non si ordinano più.<br><b>Scorta:</b> ogni articolo scarica un articolo di magazzino a ogni vendita (dal Bar o dal Ristorante, secondo dove si vende), oppure va segnato "Non tracciare la scorta". I piatti non tengono scorta.</p>

    <form method="POST" action="<?php echo e(route('bistro.articoli.crea')); ?>" class="bg-white rounded-2xl border border-gray-200 p-5 mb-5">
        <?php echo csrf_field(); ?>
        <div class="flex flex-wrap items-end gap-3">
            <label class="flex-1 min-w-[200px]"><span class="block text-xs text-slate-500 mb-1">Nuovo articolo</span>
                <input name="nome" required maxlength="100" value="<?php echo e(old('nome')); ?>" class="w-full <?php echo e($campo); ?>"></label>
            <label><span class="block text-xs text-slate-500 mb-1">Dove si vende</span>
                <select name="sezione" required class="<?php echo e($campo); ?>"><option value="">Dove si vende…</option><?php $__currentLoopData = $sezioni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nomeRep => $cats): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><optgroup label="<?php echo e($nomeRep); ?>"><?php $__currentLoopData = $cats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $nomeCat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($nomeRep); ?>|<?php echo e($k); ?>" <?php if(old('sezione') === $nomeRep . '|' . $k): echo 'selected'; endif; ?>><?php echo e($nomeCat); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></optgroup><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
            <label><span class="block text-xs text-slate-500 mb-1">Prezzo €</span>
                <input name="prezzo" type="number" step="0.01" min="0" required value="<?php echo e(old('prezzo')); ?>" class="w-28 <?php echo e($campo); ?>"></label>
            <label><span class="block text-xs text-slate-500 mb-1">IVA % (vuoto = <?php echo e(rtrim(rtrim(number_format($ivaPredefinita, 2, ',', ''), '0'), ',')); ?>)</span>
                <input name="aliquota_iva" type="number" step="0.01" min="0" max="100" value="<?php echo e(old('aliquota_iva')); ?>" class="w-24 <?php echo e($campo); ?>"></label>
            <label><span class="block text-xs text-slate-500 mb-1">Scorta: scarica dal magazzino</span>
                <select name="scarica" class="<?php echo e($campo); ?>"><option value="">— scegli —</option><option value="no" <?php if(old('scarica') === 'no'): echo 'selected'; endif; ?>>Non tracciare la scorta</option><?php $__currentLoopData = $magazzino; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($m->id); ?>" <?php if(old('scarica') === (string) $m->id): echo 'selected'; endif; ?>><?php echo e($m->nome); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
            <label><span class="block text-xs text-slate-500 mb-1">Quantità a vendita</span>
                <input name="quantita_per_vendita" type="number" step="0.001" min="0.001" value="1" class="w-24 <?php echo e($campo); ?>"></label>
            <button class="bg-[#C9822B] hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">Aggiungi</button>
        </div>
    </form>

    <div class="space-y-2 mb-10">
        <?php $__currentLoopData = $articoli; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div>
            <form method="POST" action="<?php echo e(route('bistro.articoli.aggiorna', $a->id)); ?>"
                  class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap items-center gap-3 <?php echo e($a->attivo ? '' : 'opacity-60'); ?>">
                <?php echo csrf_field(); ?>
                <input name="nome" required maxlength="100" value="<?php echo e($a->nome); ?>" class="flex-1 min-w-[180px] <?php echo e($campo); ?>">
                <?php $sezA = (array_key_exists($a->categoria, $sezioni['Bar']) ? 'Bar' : 'Ristorante') . '|' . $a->categoria; ?>
                <select name="sezione" required class="<?php echo e($campo); ?>"><option value="">Dove si vende…</option><?php $__currentLoopData = $sezioni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nomeRep => $cats): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><optgroup label="<?php echo e($nomeRep); ?>"><?php $__currentLoopData = $cats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $nomeCat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($nomeRep); ?>|<?php echo e($k); ?>" <?php if($sezA === $nomeRep . '|' . $k): echo 'selected'; endif; ?>><?php echo e($nomeCat); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></optgroup><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
                <input name="prezzo" type="number" step="0.01" min="0" required value="<?php echo e($a->prezzo); ?>" class="w-28 <?php echo e($campo); ?>">
                <input name="aliquota_iva" type="number" step="0.01" min="0" max="100" value="<?php echo e($a->aliquota_iva !== null ? (float) $a->aliquota_iva : ''); ?>" placeholder="IVA %" title="IVA %: vuoto = quella predefinita" class="w-24 <?php echo e($campo); ?>">
                <?php
                    $col = $collegamenti[$a->id] ?? null;
                    $valScarica = $col ? (string) $col->magazzino_articolo_id : ($a->senza_scorta ? 'no' : '');
                    $daDefinire = $valScarica === '' && ! in_array($a->categoria, ['antipasti', 'primi', 'secondi', 'dessert'], true);
                ?>
                <select name="scarica" class="<?php echo e($campo); ?><?php echo e($daDefinire ? ' border-[#B5563C] ring-1 ring-[#B5563C]' : ''); ?>"><option value="">— scegli —</option><option value="no" <?php if($valScarica === 'no'): echo 'selected'; endif; ?>>Non tracciare la scorta</option><?php $__currentLoopData = $magazzino; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($m->id); ?>" <?php if($valScarica === (string) $m->id): echo 'selected'; endif; ?>><?php echo e($m->nome); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
                <input name="quantita_per_vendita" type="number" step="0.001" min="0.001" value="<?php echo e($col ? (float) $col->quantita : 1); ?>" class="w-20 <?php echo e($campo); ?>" title="Quantità scaricata a ogni vendita" aria-label="Quantità a vendita">
                <label class="flex items-center gap-1.5 text-sm text-slate-600"><input type="checkbox" name="attivo" value="1" <?php if($a->attivo): echo 'checked'; endif; ?> class="accent-[#C9822B]"> Attivo</label>
                <button class="bg-white border border-gray-200 hover:border-[#C9822B] text-slate-700 font-medium px-4 py-2 rounded-lg text-sm">Salva</button>
            </form>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <h2 class="text-lg font-medium mb-3">Tavoli</h2>

    <form method="POST" action="<?php echo e(route('bistro.tavoli.crea')); ?>" class="bg-white rounded-2xl border border-gray-200 p-5 mb-5 flex flex-wrap items-end gap-3">
        <?php echo csrf_field(); ?>
        <label><span class="block text-xs text-slate-500 mb-1">Zona</span>
            <select name="zona" class="<?php echo e($campo); ?>"><?php $__currentLoopData = $zone; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>"><?php echo e($n); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
        <label><span class="block text-xs text-slate-500 mb-1">Numero</span>
            <input name="numero" type="number" min="1" max="999" required class="w-24 <?php echo e($campo); ?>"></label>
        <button class="bg-[#C9822B] hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">Aggiungi tavolo</button>
    </form>

    <div class="space-y-4">
        <?php $__currentLoopData = $zone; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $nomeZona): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <p class="text-sm font-medium mb-3"><?php echo e($nomeZona); ?></p>
                <div class="flex flex-wrap gap-2">
                    <?php $__empty_1 = true; $__currentLoopData = ($tavoli[$k] ?? collect())->where('attivo', true); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <form method="POST" action="<?php echo e(route('bistro.tavoli.disattiva', $t->id)); ?>" onsubmit="return confirm('Disattivare il tavolo <?php echo e($t->numero); ?>?')"><?php echo csrf_field(); ?>
                            <button class="px-4 py-2 rounded-lg border border-gray-200 text-sm hover:border-[#B5563C] hover:text-[#B5563C]" title="Clicca per disattivare"><?php echo e($t->numero); ?> &times;</button>
                        </form>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <span class="text-sm text-slate-400">Nessun tavolo.</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/bistro/listino.blade.php ENDPATH**/ ?>