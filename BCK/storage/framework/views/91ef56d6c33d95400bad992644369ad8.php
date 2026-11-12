<?php $__env->startSection('title', 'Jonathan PMS — Housekeeping'); ?>
<?php $__env->startSection('page_title', 'Housekeeping'); ?>
<?php $__env->startSection('page_color', '#7E5A86'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $badge = [
        'pulita'       => 'bg-okGreen text-white',
        'sporca'       => 'bg-slate-500 text-white',
        'in_pulizia'   => 'bg-fdBlue text-white',
        'manutenzione' => 'bg-[#B5563C] text-white',
    ];
    $ieri = $data->copy()->subDay()->toDateString();
    $domani = $data->copy()->addDay()->toDateString();
?>

<div class="w-full">

    <?php echo $__env->make('frontdesk.partials.messaggi', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('housekeeping.index', ['data' => $ieri])); ?>" class="px-3 py-2 rounded-lg bg-white border border-gray-200 text-sm" title="Giorno prima">&larr;</a>
            <form method="GET" action="<?php echo e(route('housekeeping.index')); ?>" class="flex items-center gap-2">
                <input type="date" name="data" value="<?php echo e($data->toDateString()); ?>" onchange="this.form.submit()"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
            </form>
            <a href="<?php echo e(route('housekeeping.index', ['data' => $domani])); ?>" class="px-3 py-2 rounded-lg bg-white border border-gray-200 text-sm" title="Giorno dopo">&rarr;</a>
            <?php if (! ($data->isToday())): ?>
                <a href="<?php echo e(route('housekeeping.index')); ?>" class="text-sm text-fdBlue hover:underline ml-2">Oggi</a>
            <?php endif; ?>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="px-3 py-1.5 rounded-full bg-slate-500 text-white"><?php echo e($riepilogo['da_pulire']); ?> da pulire</span>
            <span class="px-3 py-1.5 rounded-full bg-fdBlue text-white"><?php echo e($riepilogo['in_corso']); ?> in corso</span>
            <span class="px-3 py-1.5 rounded-full bg-okGreen text-white"><?php echo e($riepilogo['pulite']); ?> pulite</span>
            <span class="px-3 py-1.5 rounded-full bg-white border border-gray-200 text-slate-600"><?php echo e($riepilogo['non_assegnate']); ?> senza assegnazione</span>
        </div>
    </div>

    <div class="flex items-center gap-5 mb-5">
        <?php if($puoAssegnare): ?>
            <form method="POST" action="<?php echo e(route('housekeeping.copia')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="data" value="<?php echo e($data->toDateString()); ?>">
                <button class="text-sm text-fdBlue hover:underline">Copia assegnazioni dal giorno prima</button>
            </form>
        <?php endif; ?>
        <a href="<?php echo e(route('hk.piano')); ?>" class="text-sm text-fdBlue hover:underline">Apri la vista mobile &rarr;</a>
    </div>

    <?php if($puoAssegnare && $personale->isEmpty()): ?>
        <div class="mb-5 bg-[#B8923F]/10 border border-[#B8923F]/30 text-[#8a6d2f] text-sm px-4 py-3 rounded-lg">
            Nessun operatore a cui assegnare le camere. Crea un utente con il profilo <strong>Housekeeping</strong> da <a href="<?php echo e(route('operatori.nuovo')); ?>" class="underline">Utenti e permessi</a>.
        </div>
    <?php endif; ?>

    
    <div class="flex flex-col gap-5">
        <?php $__empty_1 = true; $__currentLoopData = $camere; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $piano => $lista): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-b border-gray-100 bg-slate-50">
                    <h2 class="font-medium">Piano <?php echo e($piano); ?></h2>

                    <?php if($puoAssegnare): ?>
                        <form method="POST" action="<?php echo e(route('housekeeping.assegna.piano')); ?>" class="flex items-center gap-2">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="data" value="<?php echo e($data->toDateString()); ?>">
                            <input type="hidden" name="piano" value="<?php echo e($piano); ?>">
                            <label class="text-sm text-slate-500" for="piano-<?php echo e($piano); ?>">Tutto il piano a</label>
                            <select id="piano-<?php echo e($piano); ?>" name="utente_id" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm bg-white">
                                <option value="">— nessuno —</option>
                                <?php $__currentLoopData = $personale; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($p->id); ?>"><?php echo e($p->nomeCompleto()); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <button class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-1.5 rounded-lg text-sm font-medium">Applica</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="divide-y divide-gray-100">
                    <?php $__currentLoopData = $lista; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $s = $sit[$c->id];
                            $statoVista = $c->stato_pulizia === 'manutenzione' ? 'manutenzione' : $s->stato;
                            $etichetta = $statoVista === 'manutenzione' ? 'Fuori servizio' : $s->stato_etichetta;
                            $assegnata = $assegnazioni->get($c->id);
                        ?>
                        <div class="grid grid-cols-[90px_1.4fr_1fr_130px_1.4fr] items-center gap-3 px-5 h-[58px]">
                            <span class="text-lg font-medium"><?php echo e($c->numero); ?></span>
                            <span class="text-sm text-slate-500 truncate"><?php echo e($c->tipologia->nome ?? ''); ?></span>
                            <span class="text-sm"><?php echo e($s->tipo_etichetta); ?><?php if($s->arrivo_oggi && $s->tipo !== 'arrivo'): ?> <span class="text-slate-400">· arrivo oggi</span><?php endif; ?></span>
                            <span class="inline-flex items-center justify-center h-7 rounded-full text-xs font-medium <?php echo e($badge[$statoVista] ?? 'bg-slate-500 text-white'); ?>"><?php echo e($etichetta); ?></span>

                            <?php if($puoAssegnare): ?>
                                <form method="POST" action="<?php echo e(route('housekeeping.assegna')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="data" value="<?php echo e($data->toDateString()); ?>">
                                    <input type="hidden" name="camera_id" value="<?php echo e($c->id); ?>">
                                    <select name="utente_id" onchange="this.form.submit()" aria-label="Assegna camera <?php echo e($c->numero); ?>"
                                            class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm bg-white">
                                        <option value="">— non assegnata —</option>
                                        <?php $__currentLoopData = $personale; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($p->id); ?>" <?php if($assegnata == $p->id): echo 'selected'; endif; ?>><?php echo e($p->nomeCompleto()); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </form>
                            <?php else: ?>
                                <span class="text-sm text-slate-500">
                                    <?php echo e($assegnata ? ($personale->firstWhere('id', $assegnata)?->nomeCompleto() ?? '—') : '—'); ?>

                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="bg-white rounded-2xl border border-gray-200 p-6 text-sm text-slate-500">Nessuna camera attiva.</div>
        <?php endif; ?>
    </div>

    <p class="mt-4 px-1 text-sm text-slate-400">Il tipo di camera (partenza, fermata, arrivo) si ricava dalle prenotazioni. Le camere con ospite che resta tornano «Da pulire» ogni giorno.</p>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/housekeeping/index.blade.php ENDPATH**/ ?>