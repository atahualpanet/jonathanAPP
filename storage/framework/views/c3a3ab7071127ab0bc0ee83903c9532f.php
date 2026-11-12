<?php
    app()->setLocale(session('locale', 'it'));
    $campo = 'border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slateBlue';
?>



<?php $__env->startSection('title', 'Jonathan PMS — Fornitori'); ?>
<?php $__env->startSection('page_title', 'Magazzino & Fornitori'); ?>
<?php $__env->startSection('page_color', '#4D6C86'); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full max-w-5xl">
    <?php echo $__env->make('magazzino.partials.tabs', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <form method="POST" action="<?php echo e(route('magazzino.fornitori.crea')); ?>" class="bg-white rounded-2xl border border-gray-200 p-5 mb-5 flex flex-wrap items-end gap-3">
        <?php echo csrf_field(); ?>
        <label class="flex-1 min-w-[200px]"><span class="block text-xs text-slate-500 mb-1">Nuovo fornitore</span><input name="nome" required maxlength="150" value="<?php echo e(old('nome')); ?>" class="w-full <?php echo e($campo); ?>"></label>
        <label><span class="block text-xs text-slate-500 mb-1">Email</span><input name="email" type="email" maxlength="150" value="<?php echo e(old('email')); ?>" class="<?php echo e($campo); ?>"></label>
        <label><span class="block text-xs text-slate-500 mb-1">Telefono</span><input name="telefono" maxlength="40" value="<?php echo e(old('telefono')); ?>" class="w-36 <?php echo e($campo); ?>"></label>
        <label><span class="block text-xs text-slate-500 mb-1">Consegna (giorni)</span><input name="lead_time_giorni" type="number" min="0" max="365" value="<?php echo e(old('lead_time_giorni')); ?>" class="w-28 <?php echo e($campo); ?>"></label>
        <button class="bg-slateBlue hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">Aggiungi</button>
    </form>

    <div class="space-y-2">
        <?php $__empty_1 = true; $__currentLoopData = $fornitori; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap items-center gap-3">
                <form method="POST" action="<?php echo e(route('magazzino.fornitori.aggiorna', $f->id)); ?>" class="flex flex-wrap items-center gap-3 flex-1">
                    <?php echo csrf_field(); ?>
                    <input name="nome" required maxlength="150" value="<?php echo e($f->nome); ?>" class="flex-1 min-w-[180px] <?php echo e($campo); ?>">
                    <input name="email" type="email" maxlength="150" value="<?php echo e($f->email); ?>" placeholder="Email" class="<?php echo e($campo); ?>">
                    <input name="telefono" maxlength="40" value="<?php echo e($f->telefono); ?>" placeholder="Telefono" class="w-36 <?php echo e($campo); ?>">
                    <input name="lead_time_giorni" type="number" min="0" max="365" value="<?php echo e($f->lead_time_giorni); ?>" placeholder="Giorni" class="w-24 <?php echo e($campo); ?>" title="Giorni di consegna">
                    <button class="text-sm font-medium text-slateBlue hover:underline">Salva</button>
                </form>
                <?php if (! (in_array($f->id, $conOrdini))): ?>
                    <form method="POST" action="<?php echo e(route('magazzino.fornitori.elimina', $f->id)); ?>" onsubmit="return confirm('Eliminare <?php echo e(addslashes($f->nome)); ?>?')"><?php echo csrf_field(); ?>
                        <button class="text-sm text-slate-400 hover:text-[#B5563C]">Elimina</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-slate-500 text-sm">Nessun fornitore: aggiungi il primo qui sopra.</p>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/magazzino/fornitori.blade.php ENDPATH**/ ?>