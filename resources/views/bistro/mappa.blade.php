@php
    app()->setLocale(session('locale', 'it'));
    $colore = config('permessi.moduli.bistro.colore', '#C9822B');
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Bistro')
@section('page_title', 'Bistro · ' . $zone[$zona])
@section('page_color', $colore)

@section('content')
<div class="w-ful">

    @include('frontdesk.partials.messaggi')

    @if ($daRifornire->isNotEmpty() || $inArrivoTot)
        <a href="{{ route('bistro.scorte') }}" class="block bg-[#B5563C]/10 border border-[#B5563C]/30 rounded-2xl px-5 py-3 mb-5 text-sm text-[#8E3F2A]">
            @if ($daRifornire->isNotEmpty())<b>Stiamo finendo:</b> {{ $daRifornire->take(6)->implode(', ') }}{{ $daRifornire->count() > 6 ? '…' : '' }}. @endif
            @if ($inArrivoTot)<b>{{ $inArrivoTot }} {{ $inArrivoTot === 1 ? 'consegna' : 'consegne' }} da confermare.</b>@endif
        </a>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex gap-2">
            @foreach ($zone as $chiave => $nome)
                <a href="{{ route('bistro.mappa', ['zona' => $chiave]) }}"
                   class="px-5 py-2 rounded-full text-sm font-medium border transition {{ $chiave === $zona ? 'bg-[#C9822B] border-[#C9822B] text-white' : 'bg-white border-gray-200 text-slate-600 hover:border-[#C9822B]' }}">{{ $nome }}</a>
            @endforeach
        </div>
        <div class="flex items-center gap-5 text-sm text-slate-500">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-gray-200"></span>Libero</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#C9822B]"></span>Occupato</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-okGreen"></span>Conto</span>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-5 mb-8">
        @forelse ($tavoli as $t)
            @php
                $stato = $t->comanda_stato;
                $classi = $stato === 'aperta' ? 'bg-[#C9822B] border-[#C9822B] text-white'
                        : ($stato === 'conto' ? 'bg-okGreen border-okGreen text-white'
                        : 'bg-white border-gray-200 text-deepNavy hover:border-[#C9822B]');
            @endphp
            <a href="{{ route('bistro.comanda', $t->id) }}" class="block text-center no-underline">
                <span class="flex items-center justify-center h-24 rounded-2xl border-2 text-3xl font-medium transition {{ $classi }}">{{ $t->numero }}</span>
                <span class="block mt-2 text-sm text-slate-500">{{ $stato ? $t->coperti . ' · ' . $t->minuti . "'" : 'Libero' }}</span>
            </a>
        @empty
            <p class="col-span-full text-slate-500">Nessun tavolo in questa zona. @if (auth()->user()->isGestore()) <a href="{{ route('bistro.listino') }}" class="underline text-fdBlue">Aggiungilo dal listino Bistro</a>.@endif</p>
        @endforelse
    </div>

    <div class="bg-white rounded-xl border border-gray-200 px-5 py-3 flex flex-wrap items-center justify-between gap-3 text-[15px]">
        <span>{{ $riepilogo['occupati'] }} occupati · {{ $riepilogo['conti'] }} conti richiesti · {{ $riepilogo['liberi'] }} liberi</span>
        <span class="text-slate-500">Coperti oggi: {{ $copertiOggi }}</span>
    </div>

    <div class="flex gap-3 mt-6">
        <a href="{{ route('bistro.scorte') }}" class="bg-white border border-gray-200 hover:border-[#C9822B] text-slate-700 font-medium px-5 py-2.5 rounded-xl text-sm">Scorte reparto</a>
        <a href="{{ route('bistro.cassa') }}" class="bg-[#C9822B] hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Punto cassa</a>
        <a href="{{ route('bistro.cucina') }}" class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Cucina</a>
        @if (auth()->user()->isGestore())
            <a href="{{ route('bistro.listino') }}" class="bg-white border border-gray-200 hover:border-[#C9822B] text-slate-700 font-medium px-5 py-2.5 rounded-xl text-sm">Listino e tavoli</a>
            <a href="{{ route('bistro.incassi') }}" class="bg-white border border-gray-200 hover:border-[#C9822B] text-slate-700 font-medium px-5 py-2.5 rounded-xl text-sm">Incassi e IVA</a>
        @endif
    </div>
</div>
@endsection
