@php
    app()->setLocale(session('locale', 'it'));
    $colori = ['bozza' => 'bg-amber-50 text-amber-700', 'approvato' => 'bg-sky-50 text-sky-700', 'inviato' => 'bg-indigo-50 text-indigo-700', 'consegnato' => 'bg-okGreen/10 text-okGreen', 'annullato' => 'bg-gray-100 text-slate-500'];
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Ordini ai fornitori')
@section('page_title', 'Magazzino & Fornitori')
@section('page_color', '#4D6C86')

@section('content')
<div class="w-full max-w-5xl">
    @include('magazzino.partials.tabs')

    @if ($sottoSoglia->isNotEmpty())
        <div class="bg-[#B5563C]/10 border border-[#B5563C]/30 rounded-2xl px-5 py-4 mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-[#8E3F2A]"><b>{{ $sottoSoglia->count() }} {{ $sottoSoglia->count() === 1 ? 'articolo è' : 'articoli sono' }} sotto soglia:</b> {{ $sottoSoglia->pluck('nome')->take(6)->implode(', ') }}{{ $sottoSoglia->count() > 6 ? '…' : '' }}</p>
            <form method="POST" action="{{ route('magazzino.ordini.genera') }}">@csrf
                <button class="bg-[#B5563C] hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Prepara le bozze</button>
            </form>
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('magazzino.ordini') }}" class="px-4 py-1.5 rounded-full text-sm border {{ ! $stato ? 'bg-slateBlue border-slateBlue text-white' : 'bg-white border-gray-200 text-slate-600' }}">Aperti</a>
            @foreach (['consegnato', 'annullato'] as $k)
                <a href="{{ route('magazzino.ordini', ['stato' => $k]) }}" class="px-4 py-1.5 rounded-full text-sm border {{ $stato === $k ? 'bg-slateBlue border-slateBlue text-white' : 'bg-white border-gray-200 text-slate-600' }}">{{ $stati[$k] }}</a>
            @endforeach
        </div>
        <form method="POST" action="{{ route('magazzino.ordini.nuovo') }}" class="flex items-center gap-2">@csrf
            <select name="fornitore_id" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
                <option value="">Fornitore…</option>@foreach ($fornitori as $f)<option value="{{ $f->id }}">{{ $f->nome }}</option>@endforeach
            </select>
            <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">+ Nuova bozza</button>
        </form>
    </div>

    <div class="space-y-2">
        @forelse ($ordini as $o)
            <a href="{{ route('magazzino.ordine', $o->id) }}" class="flex flex-wrap items-center justify-between gap-3 bg-white rounded-xl border border-gray-200 hover:border-slateBlue px-5 py-4">
                <span class="flex items-center gap-3">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colori[$o->stato] }}">{{ $stati[$o->stato] }}</span>
                    <span class="font-medium">{{ $o->fornitore }}</span>
                    <span class="text-sm text-slate-400">#{{ $o->id }} · {{ \Carbon\Carbon::parse($o->created_at)->format('d/m/Y') }}</span>
                </span>
                <span class="text-sm text-slate-500">{{ $totali[$o->id]->righe ?? 0 }} righe · € {{ number_format($totali[$o->id]->totale ?? 0, 2, ',', '.') }}</span>
            </a>
        @empty
            <p class="text-slate-500 text-sm">Nessun ordine.</p>
        @endforelse
    </div>
</div>
@endsection
