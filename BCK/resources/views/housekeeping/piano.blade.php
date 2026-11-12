@extends('layouts.mobile')

@section('title', 'Jonathan Housekeeping — Il mio piano')

@section('content')
@php
    $badge = [
        'pulita'       => 'bg-okGreen text-white',
        'sporca'       => 'bg-slateBlue text-white',
        'in_pulizia'   => 'bg-fdBlue text-white',
        'manutenzione' => 'bg-danger text-white',
    ];
    $percento = $totale > 0 ? round($pulite / $totale * 100) : 0;
    $filtri = ['tutte' => 'Tutte', 'da_pulire' => 'Da pulire', 'pronte' => 'Pronte'];
@endphp

<header class="px-6 pb-5 bg-white border-b border-gray-200 pt-[max(2rem,env(safe-area-inset-top,0px))]">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-medium">{{ $titolo }}</h1>
            <p class="text-sm text-slateBlue mt-0.5">{{ \Illuminate\Support\Str::ucfirst($oggi->locale('it')->isoFormat('ddd D MMM')) }} · {{ $utente->nome }}</p>
        </div>
        <span class="w-11 h-11 rounded-full bg-hkSoft text-hk flex items-center justify-center text-lg font-medium" title="{{ $utente->nomeCompleto() }}">
            {{ mb_strtoupper(mb_substr($utente->nome, 0, 1)) }}
        </span>
    </div>

    <div class="mt-5">
        <p class="text-[15px] font-medium">{{ $pulite }} di {{ $totale }} {{ $totale === 1 ? 'camera pulita' : 'camere pulite' }}</p>
        <div class="mt-2 h-2 rounded-full bg-gray-200 overflow-hidden" role="progressbar" aria-valuenow="{{ $percento }}" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full rounded-full bg-hk w-[{{ (int) $percento }}%]"></div>
        </div>
    </div>
</header>

<main class="flex-1 px-6 py-5">
    @if ($haCamere)
        <nav class="flex gap-2 mb-5" aria-label="Filtro camere">
            @foreach ($filtri as $chiave => $etichetta)
                <a href="{{ route('hk.piano', $chiave === 'tutte' ? [] : ['filtro' => $chiave]) }}"
                   class="px-4 py-2 rounded-full text-sm border {{ $filtro === $chiave ? 'bg-hk border-hk text-white' : 'bg-white border-gray-200 text-deepNavy' }}">{{ $etichetta }}</a>
            @endforeach
        </nav>

        <div class="flex flex-col gap-3">
            @forelse ($elenco as $c)
                @php
                    $s = $sit[$c->id];
                    $statoVista = $c->stato_pulizia === 'manutenzione' ? 'manutenzione' : $s->stato;
                    $etichetta = $statoVista === 'manutenzione' ? 'Fuori servizio' : $s->stato_etichetta;
                    $eProssima = $prossima && $prossima->id === $c->id;
                @endphp
                <a href="{{ route('hk.camera', $c) }}" class="bg-white rounded-xl px-4 py-3 flex items-center justify-between border-2 {{ $eProssima ? 'border-hk' : 'border-gray-100' }}">
                    <div>
                        <div class="flex items-baseline gap-3">
                            <span class="text-xl font-medium">{{ $c->numero }}</span>
                            @if ($eProssima)<span class="text-xs text-hk font-medium">Prossima</span>@endif
                        </div>
                        <p class="text-sm text-slateBlue">
                            {{ $s->tipo_etichetta }}@if ($s->arrivo_oggi && $s->tipo !== 'arrivo') · arrivo oggi @endif
                        </p>
                    </div>
                    <span class="px-4 h-8 inline-flex items-center rounded-full text-sm font-medium {{ $badge[$statoVista] ?? 'bg-slateBlue text-white' }}">{{ $etichetta }}</span>
                </a>
            @empty
                <p class="text-center text-sm text-slateBlue py-10">Nessuna camera in questo elenco.</p>
            @endforelse
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 p-6 text-center mt-4">
            <p class="font-medium mb-1">Nessuna camera assegnata per oggi</p>
            <p class="text-sm text-slateBlue">La reception può assegnarti un piano o delle camere.</p>
            @if ($puoAssegnare)
                <a href="{{ route('housekeeping.index') }}" class="inline-block mt-4 text-sm text-hk font-medium underline">Vai alle assegnazioni</a>
            @endif
        </div>
    @endif
</main>

<footer class="px-6 py-6 text-center">
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="text-sm text-slateBlue underline">Esci</button>
    </form>
</footer>
@endsection
