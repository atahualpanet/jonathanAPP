<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jonathan PMS — Scegli una nuova password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
            colors: { deepNavy: '#0A2239', paper: '#F5F6F8' }
        } } }
    </script>
</head>
<body class="bg-paper font-sans antialiased text-deepNavy min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl border border-gray-200 p-7">
        <h1 class="text-xl font-medium mb-1">Scegli una nuova password</h1>
        <p class="text-sm text-slate-500 mb-5">Stai usando una password temporanea: prima di continuare scegline una tua (almeno 8 caratteri).</p>

        @if ($errors->any())
            <div class="mb-4 bg-[#B5563C]/10 border border-[#B5563C]/30 text-[#B5563C] text-sm font-medium px-4 py-2.5 rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.cambia.salva') }}" class="space-y-4">
            @csrf
            <label class="block text-sm text-slate-600">Password temporanea (quella ricevuta per email)
                <input type="password" name="current_password" required autocomplete="current-password"
                       class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy">
            </label>
            <label class="block text-sm text-slate-600">Nuova password
                <input type="password" name="password" required minlength="8" autocomplete="new-password"
                       class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy">
            </label>
            <label class="block text-sm text-slate-600">Ripeti nuova password
                <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"
                       class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy">
            </label>
            <button class="w-full bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Salva e continua</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
            @csrf
            <button class="text-sm text-slate-500 hover:underline">Esci</button>
        </form>
    </div>
</body>
</html>
