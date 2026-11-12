<?php
    app()->setLocale(session('locale', 'it'));
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $colore = config('permessi.moduli.bistro.colore', '#C9822B');
    $campo = 'border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#C9822B]';
    
    // Filtra gli articoli sotto soglia per l'avviso in alto (solo quelli collegati al magazzino)
    $sotto = $articoli->filter(fn ($a) => !empty($a->magazzino_id) && $a->soglia !== null && (float) ($a->scorta ?? 0) <= (float) $a->soglia);
?>



<?php $__env->startSection('title', 'Jonathan PMS — Scorte Magazzino'); ?>
<?php $__env->startSection('page_title', 'Scorte Magazzino Centrale'); ?>
<?php $__env->startSection('page_color', $colore); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full max-w-5xl">

    <a href="<?php echo e(route('bistro.mappa')); ?>" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Torna ai tavoli</a>

    <?php echo $__env->make('frontdesk.partials.messaggi', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <!-- Selettore Sezione (Bar / Ristorante) -->
    <div class="flex flex-wrap gap-2 mb-3">
        <?php $__currentLoopData = $sezioni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nomeSezione => $cats): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('bistro.scorte', ['sezione' => $nomeSezione])); ?>" class="px-5 py-2 rounded-full text-sm font-medium border <?php echo e($sezione === $nomeSezione ? 'bg-[#C9822B] border-[#C9822B] text-white' : 'bg-white border-gray-200 text-slate-600 hover:border-[#C9822B]'); ?>">
                <?php echo e($nomeSezione); ?>

            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <!-- Filtro Categorie della Sezione -->
    <div class="flex flex-wrap gap-2 mb-5">
        <a href="<?php echo e(route('bistro.scorte', ['sezione' => $sezione, 'categoria' => 'tutte'])); ?>" class="px-3.5 py-1.5 rounded-lg text-xs font-medium border <?php echo e($categoriaAttiva === 'tutte' ? 'bg-deepNavy text-white border-deepNavy' : 'bg-white border-gray-200 text-slate-600 hover:border-deepNavy'); ?>">
            Tutte le categorie
        </a>
        <?php $__currentLoopData = $categorie; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chiaveCat => $nomeCat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('bistro.scorte', ['sezione' => $sezione, 'categoria' => $chiaveCat])); ?>" class="px-3.5 py-1.5 rounded-lg text-xs font-medium border <?php echo e($categoriaAttiva === $chiaveCat ? 'bg-[#C9822B] text-white border-[#C9822B]' : 'bg-white border-gray-200 text-slate-600 hover:border-[#C9822B]'); ?>">
                <?php echo e($nomeCat); ?>

            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <!-- Avviso Articoli Sotto Soglia -->
    <?php if($sotto->isNotEmpty()): ?>
        <div class="bg-[#B5563C]/10 border border-[#B5563C]/30 rounded-2xl px-5 py-4 mb-5 text-sm text-[#8E3F2A]">
            <b>⚠️ Attenzione: articoli sotto soglia nel magazzino centrale:</b> <?php echo e($sotto->map(fn ($a) => $a->nome . ' (' . $n($a->scorta) . ' ' . $a->unita_misura . ')')->implode(', ')); ?>

        </div>
    <?php endif; ?>

    <!-- Tabella Scorte -->
    <div class="bg-white rounded-2xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-[15px] min-w-[760px]">
            <thead class="text-left text-sm text-slate-500 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 font-normal">Articolo Listino</th>
                    <th class="px-3 py-3 font-normal">Collegamento Magazzino</th>
                    <th class="px-3 py-3 font-normal text-right">Scorta Centrale</th>
                    <th class="px-3 py-3 font-normal text-right">Soglia</th>
                    <th class="px-3 py-3 font-normal">Stato</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php $__empty_1 = true; $__currentLoopData = $articoli; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php 
                        $collegato = !empty($a->magazzino_id);
                        $basso = $collegato && $a->soglia !== null && (float) ($a->scorta ?? 0) <= (float) $a->soglia;
                        $esaurito = $collegato && (float) ($a->scorta ?? 0) <= 0;
                    ?>
                    <tr>
                        <td class="px-5 py-3 font-medium">
                            <?php echo e($a->nome); ?> 
                            <span class="text-xs text-slate-400 block"><?php echo e($a->categoria); ?></span>
                        </td>
                        <td class="px-3 py-3 text-sm">
                            <?php if($a->magazzino_id): ?>
                                <span class="text-green-600 font-medium">✓ <?php echo e($a->magazzino_nome); ?></span>
                            <?php else: ?>
                                <span class="text-[#B5563C] font-medium">✗ Non collegato</span>
                                <span class="block text-xs text-slate-400">Non scalerà le scorte</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-3 text-right <?php echo e($basso || $esaurito ? 'text-[#B5563C] font-bold' : ''); ?>">
                            <?php echo e($a->magazzino_id ? $n($a->scorta ?? 0) . ' ' . $a->unita_misura : '—'); ?>

                        </td>
                        <td class="px-3 py-3 text-right text-slate-500">
                            <?php echo e($a->soglia !== null ? $n($a->soglia) : '—'); ?>

                        </td>
                        <td class="px-3 py-3">
                            <?php if(!$a->magazzino_id): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">No Scorta</span>
                            <?php elseif($esaurito): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Esaurito</span>
                            <?php elseif($basso): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Sotto Soglia</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                            Nessun articolo in questa categoria. Aggiungi articoli dal listino.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p class="text-xs text-slate-400 mt-4">
        ℹ️ <b>Nota operativa:</b> Bar e Ristorante attingono direttamente dal <b>Magazzino Centrale</b>. 
        Assicurati che ogni articolo del listino sia collegato a un articolo di magazzino (da "Listino e tavoli"). 
        La scorta si aggiorna e si controlla automaticamente a ogni incasso.
    </p>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/bistro/scorte.blade.php ENDPATH**/ ?>