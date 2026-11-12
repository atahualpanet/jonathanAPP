@php
    app()->setLocale(session('locale', 'it'));
    $campo = 'border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slateBlue';
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Fornitori')
@section('page_title', 'Magazzino & Fornitori')
@section('page_color', '#4D6C86')

@section('content')
<div class="w-full max-w-5xl">
    @include('magazzino.partials.tabs')

    <form method="POST" action="{{ route('magazzino.fornitori.crea') }}" class="bg-white rounded-2xl border border-gray-200 p-5 mb-5 flex flex-wrap items-end gap-3">
        @csrf
        <label class="flex-1 min-w-[200px]"><span class="block text-xs text-slate-500 mb-1">Nuovo fornitore</span><input name="nome" required maxlength="150" value="{{ old('nome') }}" class="w-full {{ $campo }}"></label>
        <label><span class="block text-xs text-slate-500 mb-1">Email</span><input name="email" type="email" maxlength="150" value="{{ old('email') }}" class="{{ $campo }}"></label>
        <label><span class="block text-xs text-slate-500 mb-1">Telefono</span><input name="telefono" maxlength="40" value="{{ old('telefono') }}" class="w-36 {{ $campo }}"></label>
        <label><span class="block text-xs text-slate-500 mb-1">Consegna (giorni)</span><input name="lead_time_giorni" type="number" min="0" max="365" value="{{ old('lead_time_giorni') }}" class="w-28 {{ $campo }}"></label>
        <button class="bg-slateBlue hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">Aggiungi</button>
    </form>

    <div class="space-y-2">
        @forelse ($fornitori as $f)
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('magazzino.fornitori.aggiorna', $f->id) }}" class="flex flex-wrap items-center gap-3 flex-1">
                    @csrf
                    <input name="nome" required maxlength="150" value="{{ $f->nome }}" class="flex-1 min-w-[180px] {{ $campo }}">
                    <input name="email" type="email" maxlength="150" value="{{ $f->email }}" placeholder="Email" class="{{ $campo }}">
                    <input name="telefono" maxlength="40" value="{{ $f->telefono }}" placeholder="Telefono" class="w-36 {{ $campo }}">
                    <input name="lead_time_giorni" type="number" min="0" max="365" value="{{ $f->lead_time_giorni }}" placeholder="Giorni" class="w-24 {{ $campo }}" title="Giorni di consegna">
                    <button class="text-sm font-medium text-slateBlue hover:underline">Salva</button>
                </form>
                @unless (in_array($f->id, $conOrdini))
                    <form method="POST" action="{{ route('magazzino.fornitori.elimina', $f->id) }}" onsubmit="return confirm('Eliminare {{ addslashes($f->nome) }}?')">@csrf
                        <button class="text-sm text-slate-400 hover:text-[#B5563C]">Elimina</button>
                    </form>
                @endunless
            </div>
        @empty
            <p class="text-slate-500 text-sm">Nessun fornitore: aggiungi il primo qui sopra.</p>
        @endforelse
    </div>
</div>
@endsection
