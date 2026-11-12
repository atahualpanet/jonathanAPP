@php
    $utente = auth()->user();
@endphp
@extends('layouts.shell')

@section('title', 'Jonathan PMS — Profilo personale')
@section('page_title', 'Profilo personale')
@section('page_color', '#C9A75D')

@section('content')

<div class="w-full">

    @if(session('success'))
        <div class="mb-6 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-lg shadow-sm text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-800 p-4 rounded-lg shadow-sm text-sm">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-200 divide-y divide-gray-200">

        {{-- DATI PROFILO --}}
        <div class="p-6">
            <h2 class="text-lg font-bold text-deepNavy mb-2">Dati personali</h2>
            <p class="text-xs text-slateBlue mb-4">Nome, cognome e indirizzo email usati per accedere al PMS.</p>

            <form action="{{ route('profilo.salva') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Nome</label>
                        <input type="text" name="nome" value="{{ old('nome', $utente->nome) }}"
                               class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Cognome</label>
                        <input type="text" name="cognome" value="{{ old('cognome', $utente->cognome) }}"
                               class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $utente->email) }}"
                               class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none" required>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="bg-fdBlue hover:opacity-90 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">
                        Salva dati
                    </button>
                </div>
            </form>
        </div>

        {{-- CAMBIO PASSWORD --}}
        <div class="p-6 bg-gray-50/50">
            <h2 class="text-lg font-bold text-deepNavy mb-2">Cambio password</h2>
            <p class="text-xs text-slateBlue mb-4">Scegli una password sicura di almeno 8 caratteri.</p>

            <form action="{{ route('profilo.password') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-gray-700 mb-1">Password attuale</label>
                        <input type="password" name="current_password"
                               class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Nuova password</label>
                        <input type="password" name="password"
                               class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Conferma nuova password</label>
                        <input type="password" name="password_confirmation"
                               class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none" required>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="bg-warmGold hover:bg-[#b8954e] text-deepNavy font-bold px-5 py-2 rounded-lg shadow transition text-sm">
                        Aggiorna password
                    </button>
                </div>
            </form>
        </div>

        {{-- INFO ACCOUNT --}}
        <div class="p-6">
            <h2 class="text-lg font-bold text-deepNavy mb-2">Informazioni account</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Ruolo</label>
                    <input type="text" value="{{ $utente->ruolo }}" disabled
                           class="w-full bg-gray-100 border border-gray-300 rounded-lg p-2.5 text-gray-500 cursor-not-allowed">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Stato</label>
                    <input type="text" value="{{ $utente->attivo ? 'Attivo' : 'Disattivato' }}" disabled
                           class="w-full bg-gray-100 border border-gray-300 rounded-lg p-2.5 text-gray-500 cursor-not-allowed">
                </div>
            </div>
        </div>

    </div>
</div>

@endsection