@php
    app()->setLocale(session('locale', 'it'));
    $colore = config('permessi.moduli.bistro.colore', '#C9822B');
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Cucina')
@section('page_title', 'Cucina')
@section('page_color', $colore)

@section('content')
<div class="w-full">

    <a href="{{ route('bistro.mappa') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Torna ai tavoli</a>

    @include('frontdesk.partials.messaggi')

    <span class="inline-block bg-[#C9822B] text-white text-sm font-medium px-4 py-1.5 rounded-full mb-5">
        {{ $comande->count() }} {{ $comande->count() === 1 ? 'comanda in corso' : 'comande in corso' }}
    </span>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach ($comande as $comandaId => $righe)
            @php
                $minuti = (int) \Carbon\Carbon::parse($righe->min('inviata_il'))->diffInMinutes(now(), true);
                $primo = $righe->first();
            @endphp
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden flex flex-col">
                <div class="flex items-center justify-between px-5 py-3 text-white font-medium {{ $minuti >= $soglia ? 'bg-[#B5563C]' : 'bg-okGreen' }}">
                    <span>Tavolo {{ $primo->numero }}@if ($primo->zona !== 'sala') · {{ $zone[$primo->zona] }}@endif</span>
                    <span>{{ $minuti }}'</span>
                </div>
                <ul class="px-5 py-4 space-y-2 flex-1 text-[15px]">
                    @foreach ($righe as $r)
                        <li><span class="text-[#C9822B] font-medium">{{ $r->quantita }} ×</span> {{ $r->nome }}</li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('bistro.pronto', $comandaId) }}" class="px-5 pb-5">@csrf
                    <button class="w-full bg-white border border-gray-200 hover:border-okGreen text-slate-700 font-medium py-2.5 rounded-lg text-sm">Pronto</button>
                </form>
            </div>
        @endforeach
    </div>

    @if ($comande->isEmpty())
        <p class="text-slate-500 text-sm">Nessuna comanda in attesa.</p>
    @endif
</div>
@endsection

@push('scripts')
<script>setTimeout(() => location.reload(), 20000);</script>
@endpush
