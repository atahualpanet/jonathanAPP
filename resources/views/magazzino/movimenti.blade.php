@php
    app()->setLocale(session('locale', 'it'));
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Movimenti di magazzino')
@section('page_title', 'Magazzino & Fornitori')
@section('page_color', '#4D6C86')

@section('content')
<div class="w-full max-w-5xl">
    @include('magazzino.partials.tabs')

    <div class="flex flex-wrap gap-2 mb-5">
        <a href="{{ route('magazzino.movimenti') }}" class="px-4 py-1.5 rounded-full text-sm border {{ ! $sorgente ? 'bg-slateBlue border-slateBlue text-white' : 'bg-white border-gray-200 text-slate-600' }}">Tutti</a>
        @foreach ($sorgenti as $k => $nome)
            <a href="{{ route('magazzino.movimenti', ['sorgente' => $k]) }}" class="px-4 py-1.5 rounded-full text-sm border {{ $sorgente === $k ? 'bg-slateBlue border-slateBlue text-white' : 'bg-white border-gray-200 text-slate-600' }}">{{ $nome }}</a>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-[15px] min-w-[640px]">
            <thead class="text-left text-sm text-slate-500 border-b border-gray-100"><tr><th class="px-5 py-3 font-normal">Quando</th><th class="px-3 py-3 font-normal">Articolo</th><th class="px-3 py-3 font-normal text-right">Quantità</th><th class="px-3 py-3 font-normal">Sorgente</th><th class="px-3 py-3 font-normal">Chi</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($righe as $m)
                    <tr>
                        <td class="px-5 py-2.5 text-slate-500">{{ \Carbon\Carbon::parse($m->created_at)->format('d/m H:i') }}</td>
                        <td class="px-3 py-2.5">{{ $m->nome }}</td>
                        <td class="px-3 py-2.5 text-right {{ $m->tipo === 'carico' ? 'text-okGreen' : 'text-[#B5563C]' }}">{{ $m->tipo === 'carico' ? '+' : '−' }}{{ $n($m->quantita) }} {{ $m->unita_misura }}</td>
                        <td class="px-3 py-2.5 text-slate-600">{{ $m->reparto ? $m->reparto . ' · ' : 'Centrale · ' }}{{ $sorgenti[$m->sorgente] ?? $m->sorgente }}@if ($m->riferimento) <span class="text-slate-400">· {{ $m->riferimento }}</span>@endif</td>
                        <td class="px-3 py-2.5 text-slate-500">{{ $m->utente ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Nessun movimento.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="text-xs text-slate-400 mt-3">Gli ultimi 100 movimenti.</p>
</div>
@endsection
