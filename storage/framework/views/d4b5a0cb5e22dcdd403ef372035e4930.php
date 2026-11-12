<?php
    app()->setLocale(session('locale', 'it'));
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $campo = 'border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slateBlue';
?>



<?php $__env->startSection('title', 'Jonathan PMS — Magazzini di reparto'); ?>
<?php $__env->startSection('page_title', 'Magazzino & Fornitori'); ?>
<?php $__env->startSection('page_color', '#4D6C86'); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full max-w-6xl">
    <?php echo $__env->make('magazzino.partials.tabs', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <p class="text-sm text-slate-500 mb-4 max-w-3xl">Ogni reparto ha il suo magazzino. Il magazzino centrale <b>invia</b> la merce al reparto (esce subito dal centrale) e il reparto la <b>riceve e conferma</b> la quantità arrivata; le vendite del Bistro scaricano dal reparto dell'articolo, non dal centrale. La soglia del reparto segnala quando serve un nuovo trasferimento; gli ordini ai fornitori guardano solo il centrale.</p>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex flex-wrap gap-2">
            <?php $__currentLoopData = $reparti; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('magazzino.reparti', ['reparto' => $r->id])); ?>" class="px-5 py-2 rounded-full text-sm font-medium border <?php echo e($rep && $rep->id === $r->id ? 'bg-slateBlue border-slateBlue text-white' : 'bg-white border-gray-200 text-slate-600'); ?>"><?php echo e($r->nome); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <form method="POST" action="<?php echo e(route('magazzino.reparti.crea')); ?>" class="flex items-center gap-2"><?php echo csrf_field(); ?>
            <input name="nome" required maxlength="60" placeholder="Nuovo reparto" class="<?php echo e($campo); ?>">
            <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Aggiungi</button>
        </form>
    </div>

    <?php if($richieste->isNotEmpty()): ?>
        <div class="bg-white rounded-2xl border border-[#C9822B]/50 mb-5">
            <p class="px-5 py-3 text-sm font-medium border-b border-gray-100">Richieste dai reparti (<?php echo e($richieste->count()); ?>)</p>
            <?php $__currentLoopData = $richieste; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-t border-gray-50 first:border-t-0">
                    <span class="text-[15px]"><b><?php echo e($q->reparto); ?></b> chiede <?php echo e($n($q->quantita)); ?> <?php echo e($q->unita_misura); ?> di <?php echo e($q->nome); ?><?php if($q->automatica): ?> <span class="text-xs text-slate-400">(automatica: scorta alla soglia)</span><?php endif; ?>
                        <span class="block text-xs text-slate-400">nel centrale: <?php echo e($n($q->centrale)); ?> <?php echo e($q->unita_misura); ?></span></span>
                    <span class="flex items-center gap-2">
                        <form method="POST" action="<?php echo e(route('magazzino.richiesta.evadi', $q->id)); ?>" class="flex items-center gap-2"><?php echo csrf_field(); ?>
                            <input name="quantita" type="number" step="0.001" min="0.001" required value="<?php echo e((float) $q->quantita); ?>" class="w-24 <?php echo e($campo); ?>" aria-label="Quantità da inviare">
                            <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Invia</button>
                        </form>
                        <form method="POST" action="<?php echo e(route('magazzino.richiesta.annulla', $q->id)); ?>"><?php echo csrf_field(); ?><button class="text-sm text-slate-400 hover:text-[#B5563C] px-1">Rifiuta</button></form>
                    </span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <?php if($rep): ?>
        <?php if($inArrivo->isNotEmpty()): ?>
            <div class="bg-white rounded-2xl border border-gray-200 mb-5">
                <p class="px-5 py-3 text-sm font-medium border-b border-gray-100">Inviato a <?php echo e($rep->nome); ?>, non ancora ricevuto</p>
                <?php $__currentLoopData = $inArrivo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <form method="POST" action="<?php echo e(route('magazzino.trasferimento.annulla', $t->id)); ?>" class="flex items-center justify-between gap-3 px-5 py-2.5 text-[15px]" onsubmit="return confirm('Annullare il trasferimento? La merce torna nel centrale.')"><?php echo csrf_field(); ?>
                        <span><?php echo e($n($t->quantita_inviata)); ?> <?php echo e($t->unita_misura); ?> · <?php echo e($t->nome); ?> <span class="text-xs text-slate-400"><?php echo e(\Carbon\Carbon::parse($t->inviato_il)->format('d/m H:i')); ?></span></span>
                        <button class="text-sm text-slate-400 hover:text-[#B5563C]">Annulla</button>
                    </form>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
        <?php $conDiff = $recenti->filter(fn ($t) => (float) $t->quantita_ricevuta !== (float) $t->quantita_inviata); ?>
        <?php if($conDiff->isNotEmpty()): ?>
            <div class="bg-[#B5563C]/10 border border-[#B5563C]/30 rounded-2xl px-5 py-3 mb-5 text-sm text-[#8E3F2A]">
                <b>Consegne ricevute con differenza:</b> <?php echo e($conDiff->map(fn ($t) => $t->nome . ' (inviati ' . $n($t->quantita_inviata) . ', ricevuti ' . $n($t->quantita_ricevuta) . ')')->implode('; ')); ?>

            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl border border-gray-200 overflow-x-auto">
            <table class="w-full text-[15px] min-w-[860px]">
                <thead class="text-left text-sm text-slate-500 border-b border-gray-100">
                    <tr><th class="px-5 py-3 font-normal">Articolo</th><th class="px-3 py-3 font-normal text-right">Centrale</th><th class="px-3 py-3 font-normal text-right"><?php echo e($rep->nome); ?></th><th class="px-3 py-3 font-normal">Soglia · quantità abituale</th><th class="px-3 py-3 font-normal">Invia dal centrale</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $righe; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php $sotto = $a->soglia !== null && (float) $a->scorta <= (float) $a->soglia; ?>
                        <tr>
                            <td class="px-5 py-3 font-medium"><?php echo e($a->nome); ?> <span class="text-xs text-slate-400"><?php echo e($a->unita_misura); ?></span></td>
                            <td class="px-3 py-3 text-right text-slate-500"><?php echo e($n($a->centrale)); ?></td>
                            <td class="px-3 py-3 text-right <?php echo e($sotto ? 'text-[#B5563C] font-medium' : ''); ?>"><?php echo e($n($a->scorta ?? 0)); ?></td>
                            <td class="px-3 py-3">
                                <form method="POST" action="<?php echo e(route('magazzino.giacenza', [$rep->id, $a->articolo_id])); ?>" class="flex items-center gap-2"><?php echo csrf_field(); ?>
                                    <input name="soglia" type="number" step="0.001" min="0" value="<?php echo e($a->soglia !== null ? (float) $a->soglia : ''); ?>" placeholder="soglia" class="w-24 <?php echo e($campo); ?>" aria-label="Soglia del reparto">
                                    <input name="quantita_rifornimento" type="number" step="0.001" min="0" value="<?php echo e($a->quantita_rifornimento !== null ? (float) $a->quantita_rifornimento : ''); ?>" placeholder="q.tà" class="w-24 <?php echo e($campo); ?>" aria-label="Quantità abituale">
                                    <button class="text-sm font-medium text-slateBlue hover:underline">Salva</button>
                                </form>
                            </td>
                            <td class="px-3 py-3">
                                <form method="POST" action="<?php echo e(route('magazzino.trasferisci', [$rep->id, $a->articolo_id])); ?>" class="flex items-center gap-2"><?php echo csrf_field(); ?>
                                    <input name="quantita" type="number" step="0.001" min="0.001" required value="<?php echo e($a->quantita_rifornimento !== null ? (float) $a->quantita_rifornimento : ''); ?>" class="w-24 <?php echo e($campo); ?>" aria-label="Quantità da trasferire">
                                    <button class="bg-white border border-gray-200 hover:border-slateBlue text-slate-700 font-medium px-3 py-2 rounded-lg text-sm">Invia</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Nessun articolo di magazzino: creali da Scorte.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/magazzino/reparti.blade.php ENDPATH**/ ?>