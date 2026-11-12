<?php
    app()->setLocale(session('locale', 'it'));
?>



<?php $__env->startSection('title', 'Jonathan PMS — Direct'); ?>
<?php $__env->startSection('page_title', 'Direct'); ?>
<?php $__env->startSection('page_color', '#B8923F'); ?> 

<?php $__env->startSection('content'); ?>

<div class="max-w-4xl mx-auto">

    
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-deepNavy">Prenotazioni Direct</h1>
            <p class="text-slateBlue text-sm mt-1">Gestisci le richieste di prenotazione dirette dal sito o via email.</p>
        </div>
        <div class="bg-[#B8923F]/10 text-[#B8923F] px-3 py-1.5 rounded-lg flex items-center gap-2 text-sm font-semibold border border-[#B8923F]/20">
            <span class="w-2 h-2 rounded-full bg-[#B8923F] animate-pulse"></span>
            Canale Attivo
        </div>
    </div>

    
    <div class="bg-white shadow-sm rounded-2xl border border-gray-200 overflow-hidden">
        
        
        <div class="p-8 border-b border-gray-100 flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-[#B8923F]/10 flex items-center justify-center shrink-0 text-[#B8923F]">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Stato del canale Direct</h2>
                <p class="text-sm text-slate-500 mt-2">
                    Il modulo è pronto per ricevere nuove richieste dal sito web dell'hotel. 
                    Le prenotazioni confermate verranno inviate automaticamente al FrontDesk.
                </p>
                <div class="mt-4 flex items-center gap-4 text-xs text-slate-500 font-medium">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Ultimo controllo: Oggi alle 09:00
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        In attesa di nuove richieste
                    </span>
                </div>
            </div>
        </div>

        
        <div class="p-8 bg-gray-50/50">
            <h3 class="text-sm font-bold text-slate-700 mb-4">Richieste Recenti</h3>
            
            <div class="space-y-3">
                
                <div class="flex flex-col items-center justify-center bg-white border border-dashed border-gray-300 rounded-xl p-8 text-center">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-600">Nessuna richiesta recente</p>
                    <p class="text-xs text-slate-400 mt-1">Le nuove prenotazioni dal sito appariranno qui.</p>
                </div>
            </div>
        </div>

        
        <div class="p-6 border-t border-gray-200 flex justify-end bg-white">
            <button type="button" class="flex items-center gap-2 bg-[#B8923F] hover:opacity-90 text-white font-semibold px-5 py-2.5 rounded-lg transition shadow-sm text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuova Prenotazione Manuale
            </button>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/direct/index.blade.php ENDPATH**/ ?>