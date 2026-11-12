<?php $__env->startSection('title', 'Camera ' . $camera->numero); ?>

<?php $__env->startSection('content'); ?>
<?php
    $totale = count($checklist);
    $fatte = count(array_intersect(array_keys($checklist), $voci));
    $percento = $totale > 0 ? round($fatte / $totale * 100) : 0;
    $pulita = ! $aperta && $sit->stato === 'pulita';

    if ($fuoriServizio) { $etichetta = 'Fuori servizio'; $classe = 'bg-danger text-white'; }
    elseif ($aperta)    { $etichetta = 'In corso';       $classe = 'bg-fdBlue text-white'; }
    elseif ($pulita)    { $etichetta = 'Pulita';         $classe = 'bg-okGreen text-white'; }
    else                { $etichetta = 'Da pulire';      $classe = 'bg-slateBlue text-white'; }

    $attiva = $aperta;                       // checklist modificabile solo a pulizia iniziata
    $tuttoFatto = $fatte === $totale;
    $puoCompletare = $aperta && (! $obbligatoria || $tuttoFatto);
?>

<header class="px-6 pb-4 bg-white border-b border-gray-200 pt-[max(1.5rem,env(safe-area-inset-top,0px))]">
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <a href="<?php echo e(route('hk.piano')); ?>" aria-label="Torna al piano" class="mt-1 text-deepNavy">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-medium leading-tight">Camera <?php echo e($camera->numero); ?></h1>
                <p class="text-sm text-slateBlue"><?php echo e($camera->tipologia->nome ?? ''); ?><?php if($camera->tipologia): ?> · <?php endif; ?><?php echo e($sit->tipo_etichetta); ?></p>
            </div>
        </div>
        <span id="badge-stato" class="px-3 h-7 inline-flex items-center rounded-full text-xs font-medium <?php echo e($classe); ?>"><?php echo e($etichetta); ?></span>
    </div>
</header>

<main class="flex-1 px-6 py-5">

    <?php if($errors->any()): ?>
        <div class="mb-4 bg-danger/10 border border-danger/30 text-danger text-sm font-medium px-4 py-2.5 rounded-lg"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <?php if($fuoriServizio): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-5 text-sm text-slateBlue">Questa camera è fuori servizio: non c'è nulla da pulire. Per eventuali dubbi chiedi alla reception.</div>
    <?php else: ?>
        <p class="text-[15px] font-medium"><span id="n-fatte"><?php echo e($fatte); ?></span> di <?php echo e($totale); ?> attività</p>
        <div class="mt-2 h-2 rounded-full bg-gray-200 overflow-hidden">
            <div id="barra" class="h-full rounded-full bg-hk transition-all w-[<?php echo e((int) $percento); ?>%]"></div>
        </div>

        <div class="mt-4 bg-white rounded-xl border border-gray-200 divide-y divide-gray-100 <?php echo e($attiva || $pulita ? '' : 'opacity-60'); ?>">
            <?php $__currentLoopData = $checklist; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chiave => $testo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <label class="flex items-center gap-3 px-4 py-3.5 <?php echo e($attiva ? 'cursor-pointer' : ''); ?>">
                    <input type="checkbox" data-voce="<?php echo e($chiave); ?>" <?php if(in_array($chiave, $voci, true)): echo 'checked'; endif; ?> <?php if(! $attiva): echo 'disabled'; endif; ?>
                           class="w-6 h-6 rounded-md accent-[#7E5A86]">
                    <span class="text-[15px]"><?php echo e($testo); ?></span>
                </label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <?php if($sit->arrivo_oggi): ?>
            <div class="mt-4 bg-hkSoft text-hk text-sm font-medium rounded-xl px-4 py-3.5">Arrivo ospite oggi</div>
        <?php endif; ?>

        
        <div class="mt-4 grid grid-cols-3 gap-3">
            <div class="bg-white rounded-xl border border-gray-200 py-3 flex flex-col items-center gap-1 opacity-40" aria-disabled="true" title="In arrivo">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#7E5A86" stroke-width="1.8"><path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg>
                <span class="text-xs">Segnala danno</span>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 py-3 flex flex-col items-center gap-1 opacity-40" aria-disabled="true" title="In arrivo">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#7E5A86" stroke-width="1.8"><rect x="8" y="7" width="8" height="14" rx="2"/><path d="M10 7V4h4v3"/></svg>
                <span class="text-xs">Minibar</span>
            </div>
            <button type="button" id="btn-nota" <?php if(! $attiva): echo 'disabled'; endif; ?>
                    class="bg-white rounded-xl border border-gray-200 py-3 flex flex-col items-center gap-1 <?php echo e($attiva ? '' : 'opacity-40'); ?>">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#7E5A86" stroke-width="1.8"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>
                <span class="text-xs">Nota</span>
            </button>
        </div>

        <div id="box-nota" class="mt-3 <?php echo e($aperta && $pulizia->nota ? '' : 'hidden'); ?>">
            <textarea id="nota" rows="3" maxlength="500" placeholder="Scrivi una nota sulla camera (la legge la reception)"
                      class="w-full border border-gray-300 rounded-xl px-3 py-2 text-[15px]"><?php echo e($aperta ? $pulizia->nota : ''); ?></textarea>
            <p id="nota-stato" class="text-xs text-slateBlue mt-1"></p>
        </div>

        <div class="mt-6">
            <?php if($aperta): ?>
                <form method="POST" action="<?php echo e(route('hk.completa', $camera)); ?>">
                    <?php echo csrf_field(); ?>
                    <button id="btn-completa" <?php if(! $puoCompletare): echo 'disabled'; endif; ?>
                            class="w-full h-14 rounded-xl bg-hk text-white text-[17px] font-medium disabled:opacity-40">Segna come pulita</button>
                </form>
                <?php if($obbligatoria): ?>
                    <p id="avviso-completa" class="mt-2 text-center text-xs text-slateBlue <?php echo e($tuttoFatto ? 'hidden' : ''); ?>">Spunta tutte le attività per continuare</p>
                <?php endif; ?>
            <?php elseif($pulita): ?>
                <a href="<?php echo e(route('hk.piano')); ?>" class="block w-full h-14 leading-[3.5rem] text-center rounded-xl bg-white border border-gray-200 text-[17px] font-medium">Torna al piano</a>
            <?php else: ?>
                <form method="POST" action="<?php echo e(route('hk.inizia', $camera)); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="w-full h-14 rounded-xl bg-hk text-white text-[17px] font-medium">Inizia pulizia</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php if($aperta): ?>
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const intestazioni = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf };
    const obbligatoria = <?php echo json_encode($obbligatoria, 15, 512) ?>;

    function aggiorna(fatte, totale) {
        document.getElementById('n-fatte').textContent = fatte;
        document.getElementById('barra').style.width = (totale ? Math.round(fatte / totale * 100) : 0) + '%';
        const completa = document.getElementById('btn-completa');
        const avviso = document.getElementById('avviso-completa');
        const ok = !obbligatoria || fatte === totale;
        if (completa) completa.disabled = !ok;
        if (avviso) avviso.classList.toggle('hidden', ok);
    }

    document.querySelectorAll('[data-voce]').forEach(function (cb) {
        cb.addEventListener('change', async function () {
            try {
                const r = await fetch(<?php echo json_encode(route('hk.voce', $camera), 512) ?>, {
                    method: 'POST', headers: intestazioni,
                    body: JSON.stringify({ voce: cb.dataset.voce, spuntata: cb.checked })
                });
                if (!r.ok) throw new Error('http ' + r.status);
                const d = await r.json();
                aggiorna(d.fatte, d.totale);
            } catch (e) {
                cb.checked = !cb.checked;
                alert('Non salvato: controlla la connessione e riprova.');
            }
        });
    });

    const btnNota = document.getElementById('btn-nota');
    const boxNota = document.getElementById('box-nota');
    const nota = document.getElementById('nota');
    const statoNota = document.getElementById('nota-stato');
    btnNota.addEventListener('click', function () { boxNota.classList.toggle('hidden'); if (!boxNota.classList.contains('hidden')) nota.focus(); });
    nota.addEventListener('blur', async function () {
        try {
            const r = await fetch(<?php echo json_encode(route('hk.nota', $camera), 512) ?>, { method: 'POST', headers: intestazioni, body: JSON.stringify({ nota: nota.value }) });
            statoNota.textContent = r.ok ? 'Nota salvata' : 'Nota non salvata, riprova';
        } catch (e) { statoNota.textContent = 'Nota non salvata, riprova'; }
    });
})();
</script>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.mobile', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/housekeeping/camera.blade.php ENDPATH**/ ?>