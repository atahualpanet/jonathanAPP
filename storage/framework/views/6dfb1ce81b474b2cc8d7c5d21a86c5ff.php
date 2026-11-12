
<?php
    app()->setLocale(session('locale', 'it'));
    
    $nomeHotel = 'Hotel Bellavista';
    $logoHotel = null;
    try {
        $cfg = json_decode(file_get_contents(config_path('hotel_config.json')), true);
        $nomeHotel = $cfg['hotel']['nome_commerciale'] ?? $cfg['hotel']['nome'] ?? 'Hotel Bellavista';
        $logoHotel = $cfg['hotel']['logo_caricato'] ?? null;
    } catch (\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Accedi — Jonathan PMS</title>

    
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo e(asset('img/favicon-16.png')); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('img/favicon-32.png')); ?>">
    <link rel="icon" type="image/png" sizes="64x64" href="<?php echo e(asset('img/favicon-64.png')); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('img/favicon-180.png')); ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        deepNavy: '#0A2239',
                        warmGold: '#C9A75D',
                        slateBlue: '#4D6C86',
                        fdBlue: '#4F86B0',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-[#F4F5F7] font-sans antialiased text-slate-800 min-h-screen flex flex-col items-center justify-center p-4 md:p-8">

    
    <div class="w-full max-w-5xl bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden flex flex-col md:flex-row">

        
        <div class="md:w-1/2 bg-[#0B192C] text-white flex flex-col items-center justify-between py-16 px-8 border-r border-white/10">
            
            
            <div class="flex flex-col items-center gap-6 text-center my-auto w-full">

                <?php if($logoHotel): ?>
                    
                    <div class="w-full max-w-[300px] h-[115px] bg-white rounded-[18px] flex items-center justify-center p-4 shadow-sm">
                        <img src="<?php echo e(asset($logoHotel)); ?>"
                             alt="<?php echo e($nomeHotel); ?>"
                             class="max-w-[260px] max-h-[80px] w-auto h-auto object-contain">
                    </div>
                <?php endif; ?>

                <div class="w-full">
                    <h2 class="text-3xl font-bold tracking-tight"><?php echo e($nomeHotel); ?></h2>
                </div>
            </div>

            
            <div class="flex flex-col items-center gap-2 mt-auto">
                <svg width="45" height="35" viewBox="0 0 40 32" fill="none" aria-hidden="true">
                    <path d="M3 6 C14 6 19 12 22 26 C25 12 30 6 38 6" transform="translate(-1 0)" stroke="#C9A75D" stroke-width="5" stroke-linecap="butt" stroke-linejoin="round" fill="none"/>
                </svg>

                <h2 class="text-xl font-bold tracking-tight">Jonathan</h2>
                <p class="text-[10px] text-slate-400 tracking-[0.2em] uppercase">Suite Gestione Ospitalità</p>
            </div>
        </div>

        
        <div class="md:w-1/2 p-10 md:p-14 flex flex-col justify-center">

            <div class="mb-8">
                <h2 class="text-3xl font-bold text-deepNavy">Accedi</h2>
                <p class="text-sm text-slate-500 mt-2">Usa le credenziali della tua struttura</p>
            </div>

            <?php if($errors->any()): ?>
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-800 p-3 rounded-lg text-sm">
                    <?php echo e($errors->first()); ?>

                </div>
            <?php endif; ?>

            <?php if(request()->query('inattivita')): ?>
                <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-2 text-sm mb-4">
                    Sessione terminata per inattività. Effettua di nuovo l'accesso.
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('login.post')); ?>" class="space-y-4">
                <?php echo csrf_field(); ?>

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" id="email"
                           value="<?php echo e(old('email')); ?>"
                           placeholder="nome@dominio.it"
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-fdBlue focus:outline-none"
                           required autofocus>
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                    <input type="password" name="password" id="password"
                           placeholder="••••••••••"
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-fdBlue focus:outline-none"
                           required>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <a href="<?php echo e(route('password.dimenticata')); ?>" class="text-sm text-fdBlue hover:underline">Password dimenticata?</a>

                    <label class="flex items-center gap-2 text-xs text-slate-500 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-fdBlue focus:ring-fdBlue">
                        Ricordami
                    </label>
                </div>

                <button type="submit"
                        class="w-full bg-[#0B192C] hover:bg-[#132743] text-white font-semibold py-3.5 rounded-xl transition shadow-sm">
                    Accedi
                </button>
            </form>

            <div class="mt-6 flex items-center justify-between text-xs text-slate-400">
                <span></span>
                <span class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    Accesso protetto
                </span>
            </div>

        </div>
    </div>

</body>
</html>
<?php /**PATH C:\xampp\htdocs\jonathan\resources\views/auth/login.blade.php ENDPATH**/ ?>