<?php
    app()->setLocale(session('locale', 'it'));
    $colori = ['bozza' => 'bg-amber-50 text-amber-700', 'approvato' => 'bg-sky-50 text-sky-700', 'inviato' => 'bg-indigo-50 text-indigo-700', 'consegnato' => 'bg-okGreen/10 text-okGreen', 'annullato' => 'bg-gray-100 text-slate-500'];
?>



<?php $__env->startSection('title', 'Jonathan PMS — Ordini ai fornitori'); ?>
<?php $__env->startSection('page_title', 'Magazzino & Fornitori'); ?>
<?php $__env->startSection('page_color', '#4D6C86'); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full max-w-5xl">
    <?php echo $__env->make('magazzino.partials.tabs', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php if($sottoSoglia->isNotEmpty()): ?>
        <div class="bg-[#B5563C]/10 border border-[#B5563C]/30 rounded-2xl px-5 py-4 mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-[#8E3F2A]"><b><?php echo e($sottoSoglia->count()); ?> <?php echo e($sottoSoglia->count() === 1 ? 'articolo è' : 'articoli sono'); ?> sotto soglia:</b> <?php echo e($sottoSoglia->pluck('nome')->take(6)->implode(', ')); ?><?php echo e($sottoSoglia->count() > 6 ? '…' : ''); ?></p>
            <form method="POST" action="<?php echo e(route('magazzino.ordini.genera')); ?>"><?php echo csrf_field(); ?>
                <button class="bg-[#B5563C] hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Prepara le bozze</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex flex-wrap gap-2">
            <a href="<?php echo e(route('magazzino.ordini')); ?>" class="px-4 py-1.5 rounded-full text-sm border <?php echo e(! $stato ? 'bg-slateBlue border-slateBlue text-white' : 'bg-white border-gray-200 text-slate-600'); ?>">Aperti</a>
            <?php $__currentLoopData = ['consegnato', 'annullato']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('magazzino.ordini', ['stato' => $k])); ?>" class="px-4 py-1.5 rounded-full text-sm border <?php echo e($stato === $k ? 'bg-slateBlue border-slateBlue text-white' : 'bg-white border-gray-200 text-slate-600'); ?>"><?php echo e($stati[$k]); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <form method="POST" action="<?php echo e(route('magazzino.ordini.nuovo')); ?>" class="flex items-center gap-2"><?php echo csrf_field(); ?>
            <select name="fornitore_id" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
                <option value="">Fornitore…</option><?php $__currentLoopData = $fornitori; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($f->id); ?>"><?php echo e($f->nome); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">+ Nuova bozza</button>
        </form>
    </div>

    <div class="space-y-2">
        <?php $__empty_1 = true; $__currentLoopData = $ordini; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a href="<?php echo e(route('magazzino.ordine', $o->id)); ?>" class="flex flex-wrap items-center justify-between gap-3 bg-white rounded-xl border border-gray-200 hover:border-slateBlue px-5 py-4">
                <span class="flex items-center gap-3">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo e($colori[$o->stato]); ?>"><?php echo e($stati[$o->stato]); ?></span>
                    <span class="font-medium"><?php echo e($o->fornitore); ?></span>
                    <span class="text-sm text-slate-400">#<?php echo e($o->id); ?> · <?php echo e(\Carbon\Carbon::parse($o->created_at)->format('d/m/Y')); ?></span>
                </span>
                <span class="text-sm text-slate-500"><?php echo e($totali[$o->id]->righe ?? 0); ?> righe · € <?php echo e(number_format($totali[$o->id]->totale ?? 0, 2, ',', '.')); ?></span>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-slate-500 text-sm">Nessun ordine.</p>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/magazzino/ordini.blade.php ENDPATH**/ ?>