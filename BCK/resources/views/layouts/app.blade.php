
@php
    app()->setLocale(session('locale', 'it'));
@endphp

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Jonathan PMS')</title>
    
    <!-- FAVICON UFFICIALE -->
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        deepNavy: '#0A2239',
                        warmGold: '#C9A75D',
                        slateBlue: '#4D6C86',
                        lightGray: '#E6E8EA',
                        fdBlue: '#4F86B0',     // FrontDesk / in arrivo
                        okGreen: '#5E8F68',    // in casa
                        cream: '#FBF6EA',      // oggi nel tableau
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-lightGray font-sans antialiased min-h-screen flex flex-col">

    <!-- Top Navbar brandizzata Deep Navy -->
    <nav class="bg-deepNavy text-white shadow-lg">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
<div class="flex items-center space-x-3">
    <!-- Logo grafico personalizzato -->
    <img src="{{ asset('img/logo.png') }}" alt="Jonathan PMS Logo" class="w-10 h-10 object-contain rounded-lg">
    <div>
        <span class="text-xl font-bold tracking-wide">Jonathan</span>
        <span class="text-xs text-warmGold font-semibold uppercase tracking-widest block">Sync PMS</span>
    </div>
</div>
            
            <div class="flex items-center space-x-6 text-sm font-medium">
                <a href="/" class="hover:text-warmGold transition-colors">Front Desk</a>
                <a href="/camere" class="hover:text-warmGold transition-colors">Camere</a>
                <a href="#" class="text-gray-400 hover:text-warmGold transition-colors">Prenotazioni</a>
                <a href="#" class="text-gray-400 hover:text-warmGold transition-colors">Ospiti</a>
                <span class="bg-slateBlue/40 text-warmGold px-3 py-1 rounded-full text-xs font-semibold border border-warmGold/30">
                    Reception Active
                </span>
            </div>
        </div>
    </nav>

    <!-- Contenuto Dinamico delle Pagine -->
    <main class="flex-grow container mx-auto px-6 py-8">
        @yield('content')
    </main>

    <!-- Footer minimale -->
    <footer class="bg-deepNavy text-gray-400 text-xs py-4 text-center border-t border-slateBlue/20">
        Jonathan Suite Gestione Hotel &copy; 2026 — Tutti i diritti riservati
    </footer>

</body>
</html>
