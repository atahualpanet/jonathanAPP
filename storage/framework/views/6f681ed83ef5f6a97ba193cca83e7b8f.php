<?php
    app()->setLocale(session('locale', 'it'));
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $campo = 'border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slateBlue';
?>



<?php $__env->startSection('title', 'Jonathan PMS — Magazzino'); ?>
<?php $__env->startSection('page_title', 'Magazzino & Fornitori'); ?>
<?php $__env->startSection('page_color', '#4D6C86'); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full max-w-6xl">
    <?php echo $__env->make('magazzino.partials.tabs', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <a href="<?php echo e(route('magazzino.index', ['sotto' => 1])); ?>" class="bg-white rounded-2xl border p-5 <?php echo e($nSotto ? 'border-[#B5563C]/40' : 'border-gray-200'); ?>">
            <p class="text-sm text-slate-500">Sotto soglia</p>
            <p class="text-3xl font-medium <?php echo e($nSotto ? 'text-[#B5563C]' : ''); ?>"><?php echo e($nSotto); ?></p>
        </a>
        <a href="<?php echo e(route('magazzino.ordini')); ?>" class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-sm text-slate-500">Bozze d'ordine da approvare</p>
            <p class="text-3xl font-medium"><?php echo e($nBozze); ?></p>
        </a>
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-sm text-slate-500">Valore di magazzino</p>
            <p class="text-3xl font-medium">€ <?php echo e(number_format($valore, 2, ',', '.')); ?></p>
        </div>
    </div>

    <?php if($daRifornire->isNotEmpty() || $avvisi['richieste']): ?>
        <a href="<?php echo e(route('magazzino.reparti')); ?>" class="block bg-[#B5563C]/10 border border-[#B5563C]/30 rounded-2xl px-5 py-3 mb-5 text-sm text-[#8E3F2A]">
            <?php if($daRifornire->isNotEmpty()): ?><b>Reparti da rifornire:</b> <?php echo e($daRifornire->take(6)->implode(', ')); ?><?php echo e($daRifornire->count() > 6 ? '…' : ''); ?>. <?php endif; ?>
            <?php if($avvisi['richieste']): ?><b><?php echo e($avvisi['richieste']); ?> <?php echo e($avvisi['richieste'] === 1 ? 'richiesta' : 'richieste'); ?> dai reparti da evadere.</b><?php endif; ?>
        </a>
    <?php endif; ?>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <form method="GET" class="flex items-center gap-3">
            <input name="q" value="<?php echo e($q); ?>" placeholder="Cerca articolo" class="<?php echo e($campo); ?>">
            <label class="flex items-center gap-1.5 text-sm text-slate-600"><input type="checkbox" name="sotto" value="1" <?php if($soloSotto): echo 'checked'; endif; ?> onchange="this.form.submit()"> Solo sotto soglia</label>
        </form>
        <a href="<?php echo e(route('magazzino.articolo')); ?>" class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">+ Nuovo articolo</a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-[15px] min-w-[720px]">
            <thead class="text-left text-sm text-slate-500 border-b border-gray-100">
                <tr><th class="px-5 py-3 font-normal">Articolo</th><th class="px-3 py-3 font-normal text-right">Centrale</th><th class="px-3 py-3 font-normal text-right">Nei reparti</th><th class="px-3 py-3 font-normal text-right">Soglia</th><th class="px-3 py-3 font-normal">Fornitore</th><th class="px-3 py-3 font-normal text-right">Acquisto €</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php $__empty_1 = true; $__currentLoopData = $articoli; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $sotto = $a->attivo && $a->soglia_riordino !== null && $a->scorta_attuale <= $a->soglia_riordino; ?>
                    <tr class="<?php echo e($a->attivo ? '' : 'opacity-50'); ?>">
                        <td class="px-5 py-3 font-medium"><?php echo e($a->nome); ?></td>
                        <td class="px-3 py-3 text-right <?php echo e($sotto ? 'text-[#B5563C] font-medium' : ''); ?>"><?php echo e($n($a->scorta_attuale)); ?> <?php echo e($a->unita_misura); ?></td>
                        <td class="px-3 py-3 text-right text-slate-500"><?php echo e($n($a->nei_reparti)); ?></td>
                        <td class="px-3 py-3 text-right text-slate-500"><?php echo e($a->soglia_riordino !== null ? $n($a->soglia_riordino) : '—'); ?></td>
                        <td class="px-3 py-3 text-slate-600"><?php echo e($a->fornitore ?? '—'); ?></td>
                        <td class="px-3 py-3 text-right text-slate-500"><?php echo e($a->prezzo_acquisto !== null ? number_format($a->prezzo_acquisto, 2, ',', '.') : '—'); ?></td>
                        <td class="px-5 py-3 text-right"><a href="<?php echo e(route('magazzino.articolo.modifica', $a->id)); ?>" class="text-sm font-medium text-slateBlue hover:underline">Apri</a></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Nessun articolo. Crea il primo con "Nuovo articolo".</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/magazzino/index.blade.php ENDPATH**/ ?>