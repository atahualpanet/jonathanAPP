<?php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.');
    $colore = config('permessi.moduli.bistro.colore', '#C9822B');
    $configJs = ['url' => route('bistro.camera.verifica'), 'metodo' => old('metodo', 'camera'), 'camera' => old('camera', ''), 'totale' => (float) $totale, 'righe' => []];
?>



<?php $__env->startSection('title', 'Jonathan PMS — Punto cassa'); ?>
<?php $__env->startSection('page_title', 'Punto cassa'); ?>
<?php $__env->startSection('page_color', $colore); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full ">

    <a href="<?php echo e(route('bistro.mappa')); ?>" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Torna ai tavoli</a>

    <?php echo $__env->make('frontdesk.partials.messaggi', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6">

        <section>
            <div class="flex flex-wrap gap-2 mb-5">
                <?php $__currentLoopData = $categorie; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chiave => $nome): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route('bistro.cassa', ['cat' => $chiave])); ?>"
                       class="px-5 py-2 rounded-full text-sm font-medium border transition <?php echo e($chiave === $cat ? 'bg-[#C9822B] border-[#C9822B] text-white' : 'bg-white border-gray-200 text-slate-600 hover:border-[#C9822B]'); ?>"><?php echo e($nome); ?></a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <?php $__empty_1 = true; $__currentLoopData = $articoli; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <form method="POST" action="<?php echo e(route('bistro.cassa.aggiungi')); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="articolo_id" value="<?php echo e($a->id); ?>">
                        <button class="w-full h-24 bg-white rounded-xl border border-gray-200 hover:border-[#C9822B] transition text-center px-2">
                            <span class="block text-[15px] font-medium"><?php echo e($a->nome); ?></span>
                            <span class="block text-sm text-slate-500 mt-1">€ <?php echo e($eur($a->prezzo)); ?></span>
                        </button>
                    </form>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="col-span-full text-slate-500 text-sm">Nessun articolo in questa categoria. <?php if(auth()->user()->isGestore()): ?> <a href="<?php echo e(route('bistro.listino')); ?>" class="underline text-fdBlue">Aggiungili dal listino</a>.<?php endif; ?></p>
                <?php endif; ?>
            </div>
        </section>

        <aside class="self-start">
            <div class="bg-white rounded-2xl border border-gray-200 mb-4">
                <h2 class="px-5 py-4 border-b border-gray-100 text-lg font-medium">Ordine</h2>
                <div class="px-5 py-3 min-h-[100px] text-[15px]">
                    <?php $__empty_1 = true; $__currentLoopData = $righe; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="flex items-center justify-between gap-3 py-1.5">
                            <span><?php echo e($r->nome); ?><span class="block text-xs text-slate-400">× <?php echo e($r->quantita); ?></span></span>
                            <span class="flex items-center gap-2 shrink-0">
                                € <?php echo e($eur($r->prezzo * $r->quantita)); ?>

                                <form method="POST" action="<?php echo e(route('bistro.cassa.meno', ['riga' => $r->id, 'cat' => $cat])); ?>"><?php echo csrf_field(); ?>
                                    <button class="w-7 h-7 rounded-md border border-gray-200 text-slate-500 hover:bg-slate-50 leading-none" aria-label="Uno in meno">&minus;</button>
                                </form>
                            </span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-sm text-slate-400 pt-2">Nessun articolo.</p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center justify-between px-5 py-4 border-t border-gray-100">
                    <span class="text-slate-500">Totale</span>
                    <span class="text-xl font-medium">€ <?php echo e($eur($totale)); ?></span>
                </div>
            </div>

            <?php if($righe->isNotEmpty()): ?>
                <form method="POST" action="<?php echo e(route('bistro.cassa.paga')); ?>" x-data="bistroPagamento(<?php echo \Illuminate\Support\Js::from($configJs)->toHtml() ?>)">
                    <?php echo csrf_field(); ?>
                    <?php echo $__env->make('bistro.partials.pagamento', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </form>
                <form method="POST" action="<?php echo e(route('bistro.cassa.annulla')); ?>" class="mt-3" onsubmit="return confirm('Svuotare l\'ordine?')"><?php echo csrf_field(); ?>
                    <button class="w-full text-sm text-slate-500 hover:text-[#B5563C] py-2">Svuota ordine</button>
                </form>
            <?php endif; ?>
        </aside>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php echo $__env->make('bistro.partials.script-pagamento', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/bistro/cassa.blade.php ENDPATH**/ ?>