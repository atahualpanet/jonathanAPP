@extends('layouts.shell')

@section('title', 'Jonathan PMS — Nuovo operatore')
@section('page_title', 'Nuovo operatore')
@section('page_color', '#7A3B4A')

@section('content')
<div class="w-full ">

    @include('frontdesk.partials.messaggi')

    <div class="mb-5">
        <a href="{{ route('operatori.index') }}" class="text-sm text-fdBlue hover:underline">&larr; Utenti e permessi</a>
    </div>

    <form method="POST" action="{{ route('operatori.crea') }}" autocomplete="off"
          class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-3">
            <label class="block text-sm text-slate-600">Nome
                <input name="nome" value="{{ old('nome') }}" required maxlength="255"
                       class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy">
            </label>
            <label class="block text-sm text-slate-600">Cognome
                <input name="cognome" value="{{ old('cognome') }}" required maxlength="255"
                       class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy">
            </label>
        </div>

        <label class="block text-sm text-slate-600">Email (è il nome utente per accedere)
            <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="off"
                   class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy">
        </label>

        <label class="block text-sm text-slate-600">Profilo
            <select name="ruolo" required class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy bg-white">
                @foreach ($ruoli as $r)
                    <option value="{{ $r->chiave }}" @selected(old('ruolo', 'reception') === $r->chiave)>{{ $r->nome }}</option>
                @endforeach
            </select>
        </label>

        <div class="grid grid-cols-2 gap-3">
            <label class="block text-sm text-slate-600">Password (min. 8 caratteri)
                <input type="password" name="password" required minlength="8" autocomplete="new-password"
                       class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy">
            </label>
            <label class="block text-sm text-slate-600">Ripeti password
                <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"
                       class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-[15px] text-deepNavy">
            </label>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="attivo" value="1" checked class="rounded"> Attivo (può accedere subito)
        </label>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="cambia_password" value="1" checked class="rounded"> Chiedi di cambiare la password al primo accesso
        </label>

        <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Crea operatore</button>
    </form>
</div>
@endsection
