@extends('layouts.shell')

@section('title', 'Jonathan PMS — Modifica operatore')
@section('page_title', 'Modifica operatore')
@section('page_color', '#7A3B4A')

@section('content')
<div class="w-full max-w-xl">

    @include('frontdesk.partials.messaggi')

    <div class="mb-5">
        <a href="{{ route('operatori.index') }}" class="text-sm text-fdBlue hover:underline">&larr; Utenti e permessi</a>
    </div>

    <form method="POST" action="{{ route('operatori.aggiorna', $u->id) }}" autocomplete="off"
          class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-3">
            <label class="block text-sm text-slate-600">Nome
                <input name="nome" value="{{ old('nome', $u->nome) }}" required maxlength="255"
                       class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy">
            </label>
            <label class="block text-sm text-slate-600">Cognome
                <input name="cognome" value="{{ old('cognome', $u->cognome) }}" required maxlength="255"
                       class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy">
            </label>
        </div>

        <label class="block text-sm text-slate-600">Email (è il nome utente per accedere)
            <input type="email" name="email" value="{{ old('email', $u->email) }}" required maxlength="255" autocomplete="off"
                   class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy">
        </label>

        <label class="block text-sm text-slate-600">Profilo
            <select name="ruolo" required class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy bg-white">
                @unless ($ruoli->has(\Illuminate\Support\Str::lower((string) $u->ruolo)))
                    <option value="" disabled selected>{{ $u->ruolo }} (non valido: scegline uno)</option>
                @endunless
                @foreach ($ruoli as $r)
                    <option value="{{ $r->chiave }}" @selected(old('ruolo', \Illuminate\Support\Str::lower((string) $u->ruolo)) === $r->chiave)>{{ $r->nome }}</option>
                @endforeach
            </select>
        </label>

        <div class="pt-1 border-t border-gray-100">
            <p class="text-sm text-slate-500 mt-3 mb-2">Cambia password (lascia vuoto per non cambiarla)</p>
            <div class="grid grid-cols-2 gap-3">
                <label class="block text-sm text-slate-600">Nuova password
                    <input type="password" name="password" minlength="8" autocomplete="new-password"
                           class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy">
                </label>
                <label class="block text-sm text-slate-600">Ripeti password
                    <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password"
                           class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy">
                </label>
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="attivo" value="1" @checked(old('attivo', $u->attivo ? '1' : null)) class="rounded"> Attivo (può accedere)
        </label>

        <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Salva modifiche</button>
    </form>

    @if ($u->id !== auth()->id())
        <form method="POST" action="{{ route('operatori.reset', $u->id) }}" class="mt-4 bg-white rounded-2xl border border-gray-200 p-5"
              onsubmit="return confirm('Inviare una password temporanea a {{ e($u->email) }}? La password attuale smetterà di funzionare.')">
            @csrf
            <h2 class="text-base font-medium mb-1">Reimposta password</h2>
            <p class="text-sm text-slate-500 mb-3">Genera una password temporanea e la invia per email a <strong>{{ $u->email }}</strong>. Al primo accesso l'operatore dovrà sceglierne una nuova. Le sue sessioni aperte vengono chiuse.</p>
            <button class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Invia password temporanea</button>
        </form>

        <form method="POST" action="{{ route('operatori.elimina', $u->id) }}" class="mt-4"
              onsubmit="return confirm('Eliminare {{ e($u->nomeCompleto()) }}? Non potrà più accedere.')">
            @csrf
            <button class="text-sm text-[#B5563C] hover:underline">Elimina operatore</button>
        </form>
    @endif
</div>
@endsection
