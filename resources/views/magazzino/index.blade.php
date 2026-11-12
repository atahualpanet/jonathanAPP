@php
    app()->setLocale(session('locale', 'it'));
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $campo = 'border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slateBlue';
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Magazzino')
@section('page_title', 'Magazzino & Fornitori')
@section('page_color', '#4D6C86')

@section('content')
<div class="w-full max-w-6xl">
    @include('magazzino.partials.tabs')

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <a href="{{ route('magazzino.index', ['sotto' => 1]) }}" class="bg-white rounded-2xl border p-5 {{ $nSotto ? 'border-[#B5563C]/40' : 'border-gray-200' }}">
            <p class="text-sm text-slate-500">Sotto soglia</p>
            <p class="text-3xl font-medium {{ $nSotto ? 'text-[#B5563C]' : '' }}">{{ $nSotto }}</p>
        </a>
        <a href="{{ route('magazzino.ordini') }}" class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-sm text-slate-500">Bozze d'ordine da approvare</p>
            <p class="text-3xl font-medium">{{ $nBozze }}</p>
        </a>
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-sm text-slate-500">Valore di magazzino</p>
            <p class="text-3xl font-medium">€ {{ number_format($valore, 2, ',', '.') }}</p>
        </div>
    </div>

    @if ($daRifornire->isNotEmpty() || $avvisi['richieste'])
        <a href="{{ route('magazzino.reparti') }}" class="block bg-[#B5563C]/10 border border-[#B5563C]/30 rounded-2xl px-5 py-3 mb-5 text-sm text-[#8E3F2A]">
            @if ($daRifornire->isNotEmpty())<b>Reparti da rifornire:</b> {{ $daRifornire->take(6)->implode(', ') }}{{ $daRifornire->count() > 6 ? '…' : '' }}. @endif
            @if ($avvisi['richieste'])<b>{{ $avvisi['richieste'] }} {{ $avvisi['richieste'] === 1 ? 'richiesta' : 'richieste' }} dai reparti da evadere.</b>@endif
        </a>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <form method="GET" class="flex items-center gap-3">
            <input name="q" value="{{ $q }}" placeholder="Cerca articolo" class="{{ $campo }}">
            <label class="flex items-center gap-1.5 text-sm text-slate-600"><input type="checkbox" name="sotto" value="1" @checked($soloSotto) onchange="this.form.submit()"> Solo sotto soglia</label>
        </form>
        <a href="{{ route('magazzino.articolo') }}" class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">+ Nuovo articolo</a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-[15px] min-w-[720px]">
            <thead class="text-left text-sm text-slate-500 border-b border-gray-100">
                <tr><th class="px-5 py-3 font-normal">Articolo</th><th class="px-3 py-3 font-normal text-right">Centrale</th><th class="px-3 py-3 font-normal text-right">Nei reparti</th><th class="px-3 py-3 font-normal text-right">Soglia</th><th class="px-3 py-3 font-normal">Fornitore</th><th class="px-3 py-3 font-normal text-right">Acquisto €</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($articoli as $a)
                    @php $sotto = $a->attivo && $a->soglia_riordino !== null && $a->scorta_attuale <= $a->soglia_riordino; @endphp
                    <tr class="{{ $a->attivo ? '' : 'opacity-50' }}">
                        <td class="px-5 py-3 font-medium">{{ $a->nome }}</td>
                        <td class="px-3 py-3 text-right {{ $sotto ? 'text-[#B5563C] font-medium' : '' }}">{{ $n($a->scorta_attuale) }} {{ $a->unita_misura }}</td>
                        <td class="px-3 py-3 text-right text-slate-500">{{ $n($a->nei_reparti) }}</td>
                        <td class="px-3 py-3 text-right text-slate-500">{{ $a->soglia_riordino !== null ? $n($a->soglia_riordino) : '—' }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $a->fornitore ?? '—' }}</td>
                        <td class="px-3 py-3 text-right text-slate-500">{{ $a->prezzo_acquisto !== null ? number_format($a->prezzo_acquisto, 2, ',', '.') : '—' }}</td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('magazzino.articolo.modifica', $a->id) }}" class="text-sm font-medium text-slateBlue hover:underline">Apri</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Nessun articolo. Crea il primo con "Nuovo articolo".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
