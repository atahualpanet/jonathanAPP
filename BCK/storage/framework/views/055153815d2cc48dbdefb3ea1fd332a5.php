<?php
    app()->setLocale(session('locale', 'it'));
    $colore = config('permessi.moduli.bistro.colore', '#C9822B');
?>



<?php $__env->startSection('title', 'Jonathan PMS — Bistro'); ?>
<?php $__env->startSection('page_title', 'Bistro · ' . $zone[$zona]); ?>
<?php $__env->startSection('page_color', $colore); ?>

<?php $__env->startSection('content'); ?>
<div class="w-ful">

    <?php echo $__env->make('frontdesk.partials.messaggi', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php if($daRifornire->isNotEmpty() || $inArrivoTot): ?>
        <a href="<?php echo e(route('bistro.scorte')); ?>" class="block bg-[#B5563C]/10 border border-[#B5563C]/30 rounded-2xl px-5 py-3 mb-5 text-sm text-[#8E3F2A]">
            <?php if($daRifornire->isNotEmpty()): ?><b>Stiamo finendo:</b> <?php echo e($daRifornire->take(6)->implode(', ')); ?><?php echo e($daRifornire->count() > 6 ? '…' : ''); ?>. <?php endif; ?>
            <?php if($inArrivoTot): ?><b><?php echo e($inArrivoTot); ?> <?php echo e($inArrivoTot === 1 ? 'consegna' : 'consegne'); ?> da confermare.</b><?php endif; ?>
        </a>
    <?php endif; ?>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex gap-2">
            <?php $__currentLoopData = $zone; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chiave => $nome): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('bistro.mappa', ['zona' => $chiave])); ?>"
                   class="px-5 py-2 rounded-full text-sm font-medium border transition <?php echo e($chiave === $zona ? 'bg-[#C9822B] border-[#C9822B] text-white' : 'bg-white border-gray-200 text-slate-600 hover:border-[#C9822B]'); ?>"><?php echo e($nome); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="flex items-center gap-5 text-sm text-slate-500">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-gray-200"></span>Libero</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#C9822B]"></span>Occupato</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-okGreen"></span>Conto</span>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-5 mb-8">
        <?php $__empty_1 = true; $__currentLoopData = $tavoli; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $stato = $t->comanda_stato;
                $classi = $stato === 'aperta' ? 'bg-[#C9822B] border-[#C9822B] text-white'
                        : ($stato === 'conto' ? 'bg-okGreen border-okGreen text-white'
                        : 'bg-white border-gray-200 text-deepNavy hover:border-[#C9822B]');
            ?>
            <a href="<?php echo e(route('bistro.comanda', $t->id)); ?>" class="block text-center no-underline">
                <span class="flex items-center justify-center h-24 rounded-2xl border-2 text-3xl font-medium transition <?php echo e($classi); ?>"><?php echo e($t->numero); ?></span>
                <span class="block mt-2 text-sm text-slate-500"><?php echo e($stato ? $t->coperti . ' · ' . $t->minuti . "'" : 'Libero'); ?></span>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="col-span-full text-slate-500">Nessun tavolo in questa zona. <?php if(auth()->user()->isGestore()): ?> <a href="<?php echo e(route('bistro.listino')); ?>" class="underline text-fdBlue">Aggiungilo dal listino Bistro</a>.<?php endif; ?></p>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 px-5 py-3 flex flex-wrap items-center justify-between gap-3 text-[15px]">
        <span><?php echo e($riepilogo['occupati']); ?> occupati · <?php echo e($riepilogo['conti']); ?> conti richiesti · <?php echo e($riepilogo['liberi']); ?> liberi</span>
        <span class="text-slate-500">Coperti oggi: <?php echo e($copertiOggi); ?></span>
    </div>

    <div class="flex gap-3 mt-6">
        <a href="<?php echo e(route('bistro.scorte')); ?>" class="bg-white border border-gray-200 hover:border-[#C9822B] text-slate-700 font-medium px-5 py-2.5 rounded-xl text-sm">Scorte reparto</a>
        <a href="<?php echo e(route('bistro.cassa')); ?>" class="bg-[#C9822B] hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Punto cassa</a>
        <a href="<?php echo e(route('bistro.cucina')); ?>" class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Cucina</a>
        <?php if(auth()->user()->isGestore()): ?>
            <a href="<?php echo e(route('bistro.listino')); ?>" class="bg-white border border-gray-200 hover:border-[#C9822B] text-slate-700 font-medium px-5 py-2.5 rounded-xl text-sm">Listino e tavoli</a>
            <a href="<?php echo e(route('bistro.incassi')); ?>" class="bg-white border border-gray-200 hover:border-[#C9822B] text-slate-700 font-medium px-5 py-2.5 rounded-xl text-sm">Incassi e IVA</a>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/bistro/mappa.blade.php ENDPATH**/ ?>