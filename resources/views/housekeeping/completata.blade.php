@extends('layouts.mobile')

@section('title', 'Camera ' . $camera->numero . ' pulita')

@section('content')
<main class="flex-1 px-6 flex flex-col pt-[max(3rem,env(safe-area-inset-top,0px))]">

    <div class="flex flex-col items-center text-center mt-6">
        <span class="w-28 h-28 rounded-full bg-hk flex items-center justify-center">
            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
        </span>
        <h1 class="mt-6 text-2xl font-medium">Camera {{ $camera->numero }} pulita</h1>
        <p class="text-sm text-slateBlue mt-1">Stato aggiornato in tempo reale</p>
    </div>

    <div class="mt-8 bg-white rounded-xl border border-gray-200 px-5 py-4 grid grid-cols-2 gap-4">
        <div>
            <p class="text-xs text-slateBlue">Tempo</p>
            <p class="text-xl font-medium">{{ $durata }} min</p>
        </div>
        <div>
            <p class="text-xs text-slateBlue">Attività</p>
            <p class="text-xl font-medium">{{ $attivita }}</p>
        </div>
    </div>

    @if ($prossima)
        @php $sitProssima = \App\Support\Pulizie::situazione(\Carbon\Carbon::today(), collect([$prossima]))[$prossima->id]; @endphp
        <p class="mt-8 text-sm text-slateBlue">Prossima camera</p>
        <div class="mt-2 bg-white rounded-xl border border-gray-200 px-4 py-3 flex items-center justify-between">
            <div>
                <p class="text-xl font-medium">{{ $prossima->numero }}</p>
                <p class="text-sm text-slateBlue">{{ $sitProssima->tipo_etichetta }}@if ($prossima->tipologia) · {{ $prossima->tipologia->nome }}@endif</p>
            </div>
            <span class="px-4 h-8 inline-flex items-center rounded-full text-sm font-medium {{ $sitProssima->stato === 'in_pulizia' ? 'bg-fdBlue' : 'bg-slateBlue' }} text-white">{{ $sitProssima->stato_etichetta }}</span>
        </div>

        <form method="POST" action="{{ route('hk.inizia', $prossima) }}" class="mt-5">
            @csrf
            <button class="w-full h-14 rounded-xl bg-hk text-white text-[17px] font-medium">Inizia camera {{ $prossima->numero }}</button>
        </form>
    @else
        <div class="mt-8 bg-hkSoft text-hk text-sm font-medium rounded-xl px-4 py-3.5 text-center">Non ci sono altre camere da pulire.</div>
    @endif

    <a href="{{ route('hk.piano') }}" class="mt-3 mb-8 block w-full h-14 leading-[3.5rem] text-center rounded-xl bg-white border border-gray-200 text-[17px] font-medium">Torna al piano</a>
</main>
@endsection
