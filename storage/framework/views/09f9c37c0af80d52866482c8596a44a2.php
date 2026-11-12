<?php if(session('ok')): ?>
    <div class="mb-5 bg-okGreen/10 border border-okGreen/30 text-okGreen text-sm font-medium px-4 py-2.5 rounded-lg">
        <?php echo e(session('ok')); ?>

    </div>
<?php endif; ?>
<?php if($errors->any()): ?>
    <div class="mb-5 bg-[#B5563C]/10 border border-[#B5563C]/30 text-[#B5563C] text-sm font-medium px-4 py-2.5 rounded-lg">
        <?php echo e($errors->first()); ?>

    </div>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\jonathan\resources\views/frontdesk/partials/messaggi.blade.php ENDPATH**/ ?>