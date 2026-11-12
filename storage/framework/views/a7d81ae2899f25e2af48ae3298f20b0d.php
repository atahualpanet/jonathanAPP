<?php
    app()->setLocale(session('locale', 'it'));
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
?>



<?php $__env->startSection('title', 'Jonathan PMS — Movimenti di magazzino'); ?>
<?php $__env->startSection('page_title', 'Magazzino & Fornitori'); ?>
<?php $__env->startSection('page_color', '#4D6C86'); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full max-w-5xl">
    <?php echo $__env->make('magazzino.partials.tabs', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="flex flex-wrap gap-2 mb-5">
        <a href="<?php echo e(route('magazzino.movimenti')); ?>" class="px-4 py-1.5 rounded-full text-sm border <?php echo e(! $sorgente ? 'bg-slateBlue border-slateBlue text-white' : 'bg-white border-gray-200 text-slate-600'); ?>">Tutti</a>
        <?php $__currentLoopData = $sorgenti; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $nome): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('magazzino.movimenti', ['sorgente' => $k])); ?>" class="px-4 py-1.5 rounded-full text-sm border <?php echo e($sorgente === $k ? 'bg-slateBlue border-slateBlue text-white' : 'bg-white border-gray-200 text-slate-600'); ?>"><?php echo e($nome); ?></a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-[15px] min-w-[640px]">
            <thead class="text-left text-sm text-slate-500 border-b border-gray-100"><tr><th class="px-5 py-3 font-normal">Quando</th><th class="px-3 py-3 font-normal">Articolo</th><th class="px-3 py-3 font-normal text-right">Quantità</th><th class="px-3 py-3 font-normal">Sorgente</th><th class="px-3 py-3 font-normal">Chi</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                <?php $__empty_1 = true; $__currentLoopData = $righe; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="px-5 py-2.5 text-slate-500"><?php echo e(\Carbon\Carbon::parse($m->created_at)->format('d/m H:i')); ?></td>
                        <td class="px-3 py-2.5"><?php echo e($m->nome); ?></td>
                        <td class="px-3 py-2.5 text-right <?php echo e($m->tipo === 'carico' ? 'text-okGreen' : 'text-[#B5563C]'); ?>"><?php echo e($m->tipo === 'carico' ? '+' : '−'); ?><?php echo e($n($m->quantita)); ?> <?php echo e($m->unita_misura); ?></td>
                        <td class="px-3 py-2.5 text-slate-600"><?php echo e($m->reparto ? $m->reparto . ' · ' : 'Centrale · '); ?><?php echo e($sorgenti[$m->sorgente] ?? $m->sorgente); ?><?php if($m->riferimento): ?> <span class="text-slate-400">· <?php echo e($m->riferimento); ?></span><?php endif; ?></td>
                        <td class="px-3 py-2.5 text-slate-500"><?php echo e($m->utente ?? '—'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Nessun movimento.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="text-xs text-slate-400 mt-3">Gli ultimi 100 movimenti.</p>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/magazzino/movimenti.blade.php ENDPATH**/ ?>