@php
    app()->setLocale(session('locale', 'it'));
    $meseSucc = $mese->copy()->addMonthNoOverflow();
    $mesePrec = $mese->copy()->subMonthNoOverflow();
    $giorniSettimana = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];

    // colore della cella in base alla percentuale occupata, coerente con la palette del tableau
    $coloreCella = function (?int $pct) {
        if ($pct === null) return 'bg-transparent';
        if ($pct >= 90) return 'bg-okGreen text-white';
        if ($pct >= 60) return 'bg-fdBlue text-white';
        if ($pct >= 30) return 'bg-warmGold/70 text-white';
        if ($pct > 0)   return 'bg-warmGold/25';
        return 'bg-gray-100';
    };
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Occupazione')
@section('page_title', 'Occupazione · ' . ucfirst($mese->translatedFormat('F Y')))
@section('page_color', '#4F86B0')

@section('content')

<a href="{{ route('frontdesk.prenotazioni') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">
    &larr; Torna al FrontDesk
</a>

<div class="flex items-center justify-between mb-6">
    <div class="flex gap-4">
        <div class="w-40 bg-white rounded-2xl border border-gray-200 px-5 py-3.5">
            <p class="text-sm text-slate-500">Camere attive</p>
            <p class="text-2xl font-medium">{{ $totaleCamere }}</p>
        </div>
        <div class="w-40 bg-white rounded-2xl border border-gray-200 px-5 py-3.5">
            <p class="text-sm text-slate-500">Occupazione media</p>
            <p class="text-2xl font-medium">{{ $mediaOccupazione }}%</p>
        </div>
    </div>

    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('occupazione.mensile', ['mese' => $mesePrec->format('Y-m')]) }}"
           class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg font-medium">&larr;</a>
        <span class="text-slate-600 px-2 font-medium">{{ ucfirst($mese->translatedFormat('F Y')) }}</span>
        <a href="{{ route('occupazione.mensile', ['mese' => $meseSucc->format('Y-m')]) }}"
           class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg font-medium">&rarr;</a>
        <a href="{{ route('occupazione.mensile') }}"
           class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg font-medium text-slate-600">Oggi</a>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-200 p-5">
    <div class="grid grid-cols-7 gap-1.5 mb-2 text-xs font-medium text-slate-400 text-center">
        @foreach ($giorniSettimana as $g)<div>{{ $g }}</div>@endforeach
    </div>

    @foreach ($settimane as $settimana)
                <div class="grid grid-cols-7 gap-1.5 mb-1">
            @foreach ($settimana as $giorno)
                <a href="{{ route('frontdesk.prenotazioni', ['data' => $giorno->data->format('Y-m-d')]) }}"
                   title="{{ $giorno->percentuale !== null ? $giorno->occupate . '/' . $totaleCamere . ' camere occupate' : '' }}"
                                      @class([
                       'h-16 rounded-lg flex flex-col items-center justify-center text-sm', // <-- Modificato qui
                       $coloreCella($giorno->percentuale),
                       'opacity-30' => $giorno->fuoriMese,
                       'ring-2 ring-deepNavy' => $giorno->data->isToday(),
                   ])>
                    <span class="font-medium">{{ $giorno->data->day }}</span>
                    @if ($giorno->percentuale !== null && ! $giorno->fuoriMese)
                        <span class="text-[10px] opacity-80">{{ $giorno->percentuale }}%</span>
                    @endif
                </a>
            @endforeach
        </div>
    @endforeach
</div>

<div class="flex items-center gap-5 mt-4 px-1 text-sm text-slate-400">
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-gray-100"></span> 0%</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-warmGold/25"></span> 1–29%</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-warmGold/70"></span> 30–59%</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-fdBlue"></span> 60–89%</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-okGreen"></span> 90–100%</span>
</div>
@endsection
