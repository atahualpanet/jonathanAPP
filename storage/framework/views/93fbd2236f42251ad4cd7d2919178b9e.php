<?php
    app()->setLocale(session('locale', 'it'));

    // Nome hotel e logo dalla configurazione
    $nomeHotel = 'Hotel';
    $logoHotel = null;
    try {
        $cfg = json_decode(file_get_contents(config_path('hotel_config.json')), true);
        $nomeHotel = $cfg['hotel']['nome_commerciale'] ?? $cfg['hotel']['nome'] ?? 'Hotel';
        $logoHotel = $cfg['hotel']['logo_caricato'] ?? null;
        $minutiInattivita = (int) ($cfg['configurazione']['auto_logout_minuti'] ?? 30);
    } catch (\Throwable $e) {}

    // Data di oggi in italiano per l'intestazione
    $dataIntestazione = ucfirst(now()->locale('it')->translatedFormat('l j F Y'));

    // Dati utente per il menu
    $utenteCorrente = auth()->user();
    $ruoloUtente = $utenteCorrente?->ruolo ?? 'reception';

    // Moduli della suite (esattamente gli stessi della Home)
    $tuttiIModuli = [
        ['nome' => 'FrontDesk',    'url' => route('frontdesk.prenotazioni'), 'attivo' => request()->routeIs('frontdesk.*', 'prenotazioni.*'), 'simbolo' => 'frontdesk-simbolo.png',    'ruoli_permessi' => ['amministratore', 'direttore', 'reception']],
        ['nome' => 'Prenotazioni',       'url' => route('direct.index'),           'attivo' => request()->routeIs('direct.*'), 'simbolo' => 'direct-simbolo.png',       'ruoli_permessi' => ['amministratore', 'direttore', 'reception']],
        ['nome' => 'Housekeeping', 'url' => route('housekeeping.index'),     'attivo' => request()->routeIs('housekeeping.*'), 'simbolo' => 'housekeeping-simbolo.png', 'ruoli_permessi' => ['amministratore', 'direttore', 'housekeeping']],
        ['nome' => 'Bistro',       'url' => route('bistro.mappa'),           'attivo' => request()->routeIs('bistro.*'), 'simbolo' => 'bistro-simbolo.png',       'ruoli_permessi' => ['amministratore', 'direttore', 'barman']],
        ['nome' => 'Magazzino',    'url' => route('magazzino.index'),        'attivo' => request()->routeIs('magazzino.*'), 'simbolo' => 'magazzino-simbolo.png',    'ruoli_permessi' => ['amministratore', 'direttore']],
        ['nome' => 'Performance', 'url' => '#',                             'attivo' => false, 'simbolo' => 'intelligence-simbolo.png', 'ruoli_permessi' => ['amministratore', 'direttore']],
        ['nome' => 'Impostazioni', 'url' => route('impostazioni.index'),     'attivo' => request()->routeIs('impostazioni*', 'operatori*', 'permessi.*', 'profili.*', 'minibar.*', 'configurazioni*'), 'simbolo' => 'impostazioni-simbolo.png', 'ruoli_permessi' => ['amministratore', 'direttore']],
    ];

    // Filtriamo i moduli in base al ruolo
    $moduli = array_filter($tuttiIModuli, function($m) use ($ruoloUtente) {
        return in_array($ruoloUtente, $m['ruoli_permessi']);
    });
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Jonathan PMS'); ?></title>
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo e(asset('img/favicon-16.png')); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('img/favicon-32.png')); ?>">
    <link rel="icon" type="image/png" sizes="64x64" href="<?php echo e(asset('img/favicon-64.png')); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('img/favicon-180.png')); ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo e(asset('css/style.css')); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        deepNavy: '#0A2239', warmGold: '#C9A75D', slateBlue: '#4D6C86',
                        fdBlue: '#4F86B0', okGreen: '#5E8F68', cream: '#FBF6EA', paper: '#F5F6F8',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-paper font-sans antialiased text-deepNavy">

<div class="flex min-h-screen">
    
    <aside class="w-[72px] shrink-0 bg-deepNavy flex flex-col items-center py-5">
        <a href="<?php echo e(route('dashboard.home')); ?>" title="Home della suite" class="mb-6">
            <svg width="40" height="32" viewBox="0 0 40 32" fill="none" aria-hidden="true">
                <path d="M3 6 C14 6 19 12 22 26 C25 12 30 6 38 6" transform="translate(-1 0)" stroke="#C9A75D" stroke-width="5" stroke-linecap="butt" stroke-linejoin="round" fill="none"/>
            </svg>
        </a>

        <nav class="flex flex-col gap-3">
            <?php $__currentLoopData = $moduli; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e($m['url']); ?>" title="<?php echo e($m['nome']); ?>"
                   class="block w-11 h-11 rounded-xl overflow-hidden transition-transform <?php echo e($m['attivo'] ? 'ring-2 ring-white/70' : 'opacity-60 hover:opacity-100 hover:scale-105'); ?>">
                    <img src="<?php echo e(asset('img/moduli/' . $m['simbolo'])); ?>" alt="<?php echo e($m['nome']); ?>" class="w-full h-full object-cover">
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </nav>
    </aside>

    
    <div class="flex-1 min-w-0 flex flex-col">
        
        <header class="h-16 bg-white border-b border-gray-200 px-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-medium"><?php echo $__env->yieldContent('page_title', 'Jonathan PMS'); ?></h1>
                <span class="w-2.5 h-2.5 rounded-full bg-[<?php echo $__env->yieldContent('page_color', '#4F86B0'); ?>]"></span>
            </div>
            
            <div class="flex items-center gap-5">
                <span id="inattivita-badge" data-minuti="<?php echo e($minutiInattivita); ?>"></span>
            <time datetime="<?php echo e(now()->toDateString()); ?>" class="hidden md:block text-sm text-slate-500 pr-5 border-r border-gray-200"><?php echo e($dataIntestazione); ?></time>

            
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open" class="flex items-center gap-3 rounded-xl px-2 py-1.5 hover:bg-slate-50 transition cursor-pointer focus:outline-none">
                    <span class="text-sm font-medium text-slate-700"><?php echo e($nomeHotel); ?></span>
                    
                    <?php if($logoHotel): ?>
                        <img src="<?php echo e(asset($logoHotel)); ?>" alt="<?php echo e($nomeHotel); ?>" class="h-8 w-auto max-w-[120px] object-contain">
                    <?php else: ?>
                        <span class="w-8 h-8 rounded-full bg-slate-200 block"></span>
                    <?php endif; ?>
                    
                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>

                <?php if(auth()->guard()->check()): ?>
                <div x-show="open" x-transition.opacity.duration.200ms
                     class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden z-50"
                     x-cloak>
                    
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-xs uppercase tracking-wide text-slate-400 font-semibold mb-1"><?php echo e($utenteCorrente->profilo()?->nome ?? $utenteCorrente->ruolo); ?></p>
                        <p class="text-sm font-semibold text-slate-800"><?php echo e($utenteCorrente->nomeCompleto()); ?></p>
                    </div>
                    
                    <a href="<?php echo e(route('profilo.index')); ?>" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                        Profilo personale
                    </a>
                    
                    <form method="POST" action="<?php echo e(route('logout')); ?>" class="border-t border-gray-100 mt-1 pt-1">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition text-left">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Esci
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            </div>
        </header>

        <main class="flex-1 p-8">
            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>
</div>

<?php echo $__env->yieldPushContent('scripts'); ?>
<script src="<?php echo e(asset('js/inattivita.js')); ?>" defer></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html><?php /**PATH C:\xampp\htdocs\jonathan\resources\views/layouts/shell.blade.php ENDPATH**/ ?>