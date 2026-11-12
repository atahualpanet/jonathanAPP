<?php $__env->startSection('title', 'Jonathan Housekeeping — Il mio piano'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $badge = [
        'pulita'       => 'bg-okGreen text-white',
        'sporca'       => 'bg-slateBlue text-white',
        'in_pulizia'   => 'bg-fdBlue text-white',
        'manutenzione' => 'bg-danger text-white',
    ];
    $percento = $totale > 0 ? round($pulite / $totale * 100) : 0;
    $filtri = ['tutte' => 'Tutte', 'da_pulire' => 'Da pulire', 'pronte' => 'Pronte'];
?>

<header class="px-6 pb-5 bg-white border-b border-gray-200 pt-[max(2rem,env(safe-area-inset-top,0px))]">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-medium"><?php echo e($titolo); ?></h1>
            <p class="text-sm text-slateBlue mt-0.5"><?php echo e(\Illuminate\Support\Str::ucfirst($oggi->locale('it')->isoFormat('ddd D MMM'))); ?> · <?php echo e($utente->nome); ?></p>
        </div>
        <span class="w-11 h-11 rounded-full bg-hkSoft text-hk flex items-center justify-center text-lg font-medium" title="<?php echo e($utente->nomeCompleto()); ?>">
            <?php echo e(mb_strtoupper(mb_substr($utente->nome, 0, 1))); ?>

        </span>
    </div>

    <div class="mt-5">
        <p class="text-[15px] font-medium"><?php echo e($pulite); ?> di <?php echo e($totale); ?> <?php echo e($totale === 1 ? 'camera pulita' : 'camere pulite'); ?></p>
        <div class="mt-2 h-2 rounded-full bg-gray-200 overflow-hidden" role="progressbar" aria-valuenow="<?php echo e($percento); ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full rounded-full bg-hk w-[<?php echo e((int) $percento); ?>%]"></div>
        </div>
    </div>
</header>

<main class="flex-1 px-6 py-5">
    <?php if($haCamere): ?>
        <nav class="flex gap-2 mb-5" aria-label="Filtro camere">
            <?php $__currentLoopData = $filtri; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chiave => $etichetta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('hk.piano', $chiave === 'tutte' ? [] : ['filtro' => $chiave])); ?>"
                   class="px-4 py-2 rounded-full text-sm border <?php echo e($filtro === $chiave ? 'bg-hk border-hk text-white' : 'bg-white border-gray-200 text-deepNavy'); ?>"><?php echo e($etichetta); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </nav>

        <div class="flex flex-col gap-3">
            <?php $__empty_1 = true; $__currentLoopData = $elenco; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $s = $sit[$c->id];
                    $statoVista = $c->stato_pulizia === 'manutenzione' ? 'manutenzione' : $s->stato;
                    $etichetta = $statoVista === 'manutenzione' ? 'Fuori servizio' : $s->stato_etichetta;
                    $eProssima = $prossima && $prossima->id === $c->id;
                ?>
                <a href="<?php echo e(route('hk.camera', $c)); ?>" class="bg-white rounded-xl px-4 py-3 flex items-center justify-between border-2 <?php echo e($eProssima ? 'border-hk' : 'border-gray-100'); ?>">
                    <div>
                        <div class="flex items-baseline gap-3">
                            <span class="text-xl font-medium"><?php echo e($c->numero); ?></span>
                            <?php if($eProssima): ?><span class="text-xs text-hk font-medium">Prossima</span><?php endif; ?>
                        </div>
                        <p class="text-sm text-slateBlue">
                            <?php echo e($s->tipo_etichetta); ?><?php if($s->arrivo_oggi && $s->tipo !== 'arrivo'): ?> · arrivo oggi <?php endif; ?>
                        </p>
                    </div>
                    <span class="px-4 h-8 inline-flex items-center rounded-full text-sm font-medium <?php echo e($badge[$statoVista] ?? 'bg-slateBlue text-white'); ?>"><?php echo e($etichetta); ?></span>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-center text-sm text-slateBlue py-10">Nessuna camera in questo elenco.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl border border-gray-200 p-6 text-center mt-4">
            <p class="font-medium mb-1">Nessuna camera assegnata per oggi</p>
            <p class="text-sm text-slateBlue">La reception può assegnarti un piano o delle camere.</p>
            <?php if($puoAssegnare): ?>
                <a href="<?php echo e(route('housekeeping.index')); ?>" class="inline-block mt-4 text-sm text-hk font-medium underline">Vai alle assegnazioni</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<footer class="px-6 py-6 text-center">
    <form method="POST" action="<?php echo e(route('logout')); ?>">
        <?php echo csrf_field(); ?>
        <button class="text-sm text-slateBlue underline">Esci</button>
    </form>
</footer>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.mobile', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/housekeeping/piano.blade.php ENDPATH**/ ?>