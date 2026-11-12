<?php
    $voci = ['magazzino.index' => 'Scorte', 'magazzino.reparti' => 'Reparti', 'magazzino.ordini' => 'Ordini', 'magazzino.fornitori' => 'Fornitori', 'magazzino.movimenti' => 'Movimenti'];
    $attiva = request()->routeIs('magazzino.articolo*') ? 'magazzino.index' : (request()->routeIs('magazzino.ordine*') ? 'magazzino.ordini' : request()->route()->getName());
    try { $av = \App\Support\MagazzinoService::avvisi(); } catch (\Throwable $e) { $av = []; }
    $badge = ['magazzino.reparti' => ($av['richieste'] ?? 0) + ($av['reparti_da_rifornire'] ?? 0), 'magazzino.ordini' => $av['bozze'] ?? 0, 'magazzino.index' => $av['centrale_sotto_soglia'] ?? 0];
?>
<div class="border-b border-gray-200 mb-6 flex overflow-x-auto whitespace-nowrap">
    <?php $__currentLoopData = $voci; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rotta => $nome): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route($rotta)); ?>" class="py-3 px-6 border-b-2 font-medium text-sm transition-colors <?php echo e($rotta === $attiva ? 'border-slateBlue text-slateBlue' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300'); ?>"><?php echo e($nome); ?><?php if(($badge[$rotta] ?? 0) > 0): ?> <span class="ml-1.5 inline-block min-w-[1.25rem] text-center rounded-full bg-[#B5563C] text-white text-xs px-1.5"><?php echo e($badge[$rotta]); ?></span><?php endif; ?></a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php echo $__env->make('frontdesk.partials.messaggi', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH C:\xampp\htdocs\jonathan\resources\views/magazzino/partials/tabs.blade.php ENDPATH**/ ?>