<?php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.');
    $okMinibar = (bool) $minibarOggi;
?>



<?php $__env->startSection('title', 'Jonathan PMS — Check-out'); ?>
<?php $__env->startSection('page_title', 'Check-out · Camera ' . ($camera->numero ?? '—')); ?>
<?php $__env->startSection('page_color', '#4F86B0'); ?>

<?php $__env->startSection('content'); ?>

<a href="<?php echo e(route('prenotazioni.scheda', $prenotazione)); ?>" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Torna alla prenotazione</a>

<?php echo $__env->make('frontdesk.partials.messaggi', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<form method="POST" action="<?php echo e(route('prenotazioni.checkout.conferma', $prenotazione)); ?>">
    <?php echo csrf_field(); ?>

    
    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-medium"><?php echo e($prenotazione->ospite->nome); ?> <?php echo e($prenotazione->ospite->cognome); ?></h2>
                <p class="text-sm text-slate-500">
                    Camera <?php echo e($prenotazione->camere->pluck('numero')->implode(', ')); ?>

                    · arrivo <?php echo e($prenotazione->data_arrivo->translatedFormat('D j M')); ?>

                    · partenza prevista <?php echo e($prenotazione->data_partenza->translatedFormat('D j M')); ?>

                </p>
            </div>
            <?php if($anticipata): ?>
                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-warmGold text-white">Partenza anticipata</span>
            <?php elseif($inRitardo): ?>
                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-red-600 text-white">Oltre la partenza prevista</span>
            <?php else: ?>
                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-okGreen text-white">Partenza prevista oggi</span>
            <?php endif; ?>
        </div>
        <?php if($inRitardo): ?>
            <p class="text-sm text-red-700 mt-3">L'ospite è rimasto oltre la data di partenza: le notti in più non sono nel conto. Aggiungile dal folio prima di chiudere.</p>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-lg font-medium mb-4">Conto</h2>

            <?php if($conto): ?>
                <div class="space-y-1.5 text-[15px]">
                    <?php $__currentLoopData = $riepilogo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex justify-between"><span class="text-slate-500">Folio <?php echo e($f->chiave); ?> · <?php echo e($f->nome); ?></span><span>€ <?php echo e($eur($f->saldo)); ?></span></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-slate-500">Il conto non è ancora aperto: il saldo si calcola dalle camere e dalla caparra.</p>
            <?php endif; ?>

            <div class="bg-[#EAF0F8] rounded-xl px-4 py-3 flex justify-between items-center text-lg font-medium mt-4">
                <span>Saldo da incassare</span><span>€ <?php echo e($eur($saldo)); ?></span>
            </div>

            <div class="flex flex-wrap gap-2 mt-4">
                <a href="<?php echo e(route('prenotazioni.folio', $prenotazione)); ?>" class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Apri il folio per incassare</a>
            </div>

            <?php if($daFatturare->isNotEmpty()): ?>
                <p class="text-sm text-slate-500 mt-4">Fattura non ancora emessa per il folio <?php echo e($daFatturare->implode(' e ')); ?>: puoi farlo dal folio, anche dopo la partenza.</p>
            <?php endif; ?>

            <?php if(abs($saldo) > 0.005): ?>
                <label class="flex items-start gap-2 mt-5 text-sm text-slate-700">
                    <input type="checkbox" name="saldo_aperto" value="1" class="rounded mt-0.5" <?php if(old('saldo_aperto')): echo 'checked'; endif; ?>>
                    <span>Lascia il conto aperto con saldo di € <?php echo e($eur($saldo)); ?> e fai partire l'ospite comunque (da incassare dopo).</span>
                </label>
            <?php endif; ?>
        </div>

        
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-lg font-medium mb-4">Minibar</h2>

            <?php if($okMinibar): ?>
                <p class="text-[15px] text-okGreen font-medium">Controllato oggi alle <?php echo e(\Carbon\Carbon::parse($minibarOggi->created_at)->format('H:i')); ?><?php if($minibarOggi->controllato_da): ?> da <?php echo e($minibarOggi->controllato_da); ?><?php endif; ?>
                    · <?php echo e($minibarOggi->totale > 0 ? '€ ' . $eur($minibarOggi->totale) . ' addebitati' : 'completo, niente da addebitare'); ?></p>
            <?php else: ?>
                <p class="text-[15px] text-warmGold font-medium">Non ancora controllato oggi.</p>
                <p class="text-sm text-slate-500 mt-2">Se la governante non fa in tempo (partenza urgente), puoi procedere e controllare dopo: finché il conto è aperto l'addebito entra comunque.</p>
                <div class="flex flex-wrap gap-2 mt-4">
                    <?php $__currentLoopData = $prenotazione->camere; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('camere.minibar', $c->id)); ?>" class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Controlla camera <?php echo e($c->numero); ?></a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <label class="flex items-start gap-2 mt-5 text-sm text-slate-700">
                    <input type="checkbox" name="senza_minibar" value="1" class="rounded mt-0.5" <?php if(old('senza_minibar')): echo 'checked'; endif; ?>>
                    <span>Procedi senza controllo minibar.</span>
                </label>
            <?php endif; ?>
        </div>
    </div>

    
    <?php if($anticipata): ?>
        <div class="bg-white rounded-2xl border border-gray-200 p-6 mt-6">
            <h2 class="text-lg font-medium mb-1">Partenza anticipata</h2>
            <p class="text-sm text-slate-500 mb-5">L'ospite lascia la camera prima del <?php echo e($prenotazione->data_partenza->translatedFormat('j F')); ?>. La camera risulta libera da oggi; scegli cosa fare delle notti non usate.</p>

            <div class="space-y-3 mb-5">
                <label class="flex items-start gap-3 text-[15px]">
                    <input type="radio" name="notti" value="tieni_tutte" class="mt-1" <?php if(old('notti', 'tieni_tutte') === 'tieni_tutte'): echo 'checked'; endif; ?>>
                    <span><span class="font-medium">Addebita tutte le notti prenotate</span>
                        <span class="block text-sm text-slate-500">Il conto resta com'è (€ <?php echo e($eur($prenotato)); ?> di soggiorno e tassa): penale per partenza anticipata.</span></span>
                </label>
                <label class="flex items-start gap-3 text-[15px]">
                    <input type="radio" name="notti" value="solo_usate" class="mt-1" <?php if(old('notti') === 'solo_usate'): echo 'checked'; endif; ?>>
                    <span><span class="font-medium">Addebita solo le notti usate</span>
                        <span class="block text-sm text-slate-500">Il soggiorno si accorcia a <?php echo e($ridotto['notti']); ?> <?php echo e($ridotto['notti'] === 1 ? 'notte' : 'notti'); ?>: € <?php echo e($eur($ridotto['soggiorno'] + $ridotto['tassa'])); ?> invece di € <?php echo e($eur($prenotato)); ?>

                            (− € <?php echo e($eur($prenotato - $ridotto['soggiorno'] - $ridotto['tassa'])); ?>). Camera, tassa e prenotazione si aggiornano.</span></span>
                </label>
            </div>

            <label class="block text-sm font-medium mb-1.5" for="motivo">Motivo della partenza anticipata *</label>
            <input id="motivo" name="motivo" maxlength="255" value="<?php echo e(old('motivo')); ?>" placeholder="Es. emergenza familiare, problema in camera, cambio programma"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
        </div>
    <?php endif; ?>

    
    <div class="flex flex-wrap items-center gap-3 mt-6">
        <button class="bg-fdBlue hover:opacity-90 text-white font-medium px-6 py-3 rounded-lg text-sm">Conferma check-out</button>
        <a href="<?php echo e(route('prenotazioni.scheda', $prenotazione)); ?>" class="bg-white border border-gray-200 hover:bg-gray-50 px-5 py-3 rounded-lg text-sm font-medium">Annulla</a>
        <p class="text-sm text-slate-500">La camera passa a "sporca" e l'ospite risulta partito.</p>
    </div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/frontdesk/checkout.blade.php ENDPATH**/ ?>