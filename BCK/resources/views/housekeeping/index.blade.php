@extends('layouts.shell')

@section('title', 'Jonathan PMS — Housekeeping')
@section('page_title', 'Housekeeping')
@section('page_color', '#7E5A86')

@section('content')
@php
    $badge = [
        'pulita'       => 'bg-okGreen text-white',
        'sporca'       => 'bg-slate-500 text-white',
        'in_pulizia'   => 'bg-fdBlue text-white',
        'manutenzione' => 'bg-[#B5563C] text-white',
    ];
    $ieri = $data->copy()->subDay()->toDateString();
    $domani = $data->copy()->addDay()->toDateString();
@endphp

<div class="w-full">

    @include('frontdesk.partials.messaggi')

    {{-- giorno e riepilogo --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-2">
            <a href="{{ route('housekeeping.index', ['data' => $ieri]) }}" class="px-3 py-2 rounded-lg bg-white border border-gray-200 text-sm" title="Giorno prima">&larr;</a>
            <form method="GET" action="{{ route('housekeeping.index') }}" class="flex items-center gap-2">
                <input type="date" name="data" value="{{ $data->toDateString() }}" onchange="this.form.submit()"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
            </form>
            <a href="{{ route('housekeeping.index', ['data' => $domani]) }}" class="px-3 py-2 rounded-lg bg-white border border-gray-200 text-sm" title="Giorno dopo">&rarr;</a>
            @unless ($data->isToday())
                <a href="{{ route('housekeeping.index') }}" class="text-sm text-fdBlue hover:underline ml-2">Oggi</a>
            @endunless
        </div>

        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="px-3 py-1.5 rounded-full bg-slate-500 text-white">{{ $riepilogo['da_pulire'] }} da pulire</span>
            <span class="px-3 py-1.5 rounded-full bg-fdBlue text-white">{{ $riepilogo['in_corso'] }} in corso</span>
            <span class="px-3 py-1.5 rounded-full bg-okGreen text-white">{{ $riepilogo['pulite'] }} pulite</span>
            <span class="px-3 py-1.5 rounded-full bg-white border border-gray-200 text-slate-600">{{ $riepilogo['non_assegnate'] }} senza assegnazione</span>
        </div>
    </div>

    <div class="flex items-center gap-5 mb-5">
        @if ($puoAssegnare)
            <form method="POST" action="{{ route('housekeeping.copia') }}">
                @csrf
                <input type="hidden" name="data" value="{{ $data->toDateString() }}">
                <button class="text-sm text-fdBlue hover:underline">Copia assegnazioni dal giorno prima</button>
            </form>
        @endif
        <a href="{{ route('hk.piano') }}" class="text-sm text-fdBlue hover:underline">Apri la vista mobile &rarr;</a>
    </div>

    @if ($puoAssegnare && $personale->isEmpty())
        <div class="mb-5 bg-[#B8923F]/10 border border-[#B8923F]/30 text-[#8a6d2f] text-sm px-4 py-3 rounded-lg">
            Nessun operatore a cui assegnare le camere. Crea un utente con il profilo <strong>Housekeeping</strong> da <a href="{{ route('operatori.nuovo') }}" class="underline">Utenti e permessi</a>.
        </div>
    @endif

    {{-- un riquadro per piano --}}
    <div class="flex flex-col gap-5">
        @forelse ($camere as $piano => $lista)
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-b border-gray-100 bg-slate-50">
                    <h2 class="font-medium">Piano {{ $piano }}</h2>

                    @if ($puoAssegnare)
                        <form method="POST" action="{{ route('housekeeping.assegna.piano') }}" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="data" value="{{ $data->toDateString() }}">
                            <input type="hidden" name="piano" value="{{ $piano }}">
                            <label class="text-sm text-slate-500" for="piano-{{ $piano }}">Tutto il piano a</label>
                            <select id="piano-{{ $piano }}" name="utente_id" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm bg-white">
                                <option value="">— nessuno —</option>
                                @foreach ($personale as $p)
                                    <option value="{{ $p->id }}">{{ $p->nomeCompleto() }}</option>
                                @endforeach
                            </select>
                            <button class="bg-white border border-gray-200 hover:bg-gray-50 px-3 py-1.5 rounded-lg text-sm font-medium">Applica</button>
                        </form>
                    @endif
                </div>

                <div class="divide-y divide-gray-100">
                    @foreach ($lista as $c)
                        @php
                            $s = $sit[$c->id];
                            $statoVista = $c->stato_pulizia === 'manutenzione' ? 'manutenzione' : $s->stato;
                            $etichetta = $statoVista === 'manutenzione' ? 'Fuori servizio' : $s->stato_etichetta;
                            $assegnata = $assegnazioni->get($c->id);
                        @endphp
                        <div class="grid grid-cols-[90px_1.4fr_1fr_130px_1.4fr] items-center gap-3 px-5 h-[58px]">
                            <span class="text-lg font-medium">{{ $c->numero }}</span>
                            <span class="text-sm text-slate-500 truncate">{{ $c->tipologia->nome ?? '' }}</span>
                            <span class="text-sm">{{ $s->tipo_etichetta }}@if ($s->arrivo_oggi && $s->tipo !== 'arrivo') <span class="text-slate-400">· arrivo oggi</span>@endif</span>
                            <span class="inline-flex items-center justify-center h-7 rounded-full text-xs font-medium {{ $badge[$statoVista] ?? 'bg-slate-500 text-white' }}">{{ $etichetta }}</span>

                            @if ($puoAssegnare)
                                <form method="POST" action="{{ route('housekeeping.assegna') }}">
                                    @csrf
                                    <input type="hidden" name="data" value="{{ $data->toDateString() }}">
                                    <input type="hidden" name="camera_id" value="{{ $c->id }}">
                                    <select name="utente_id" onchange="this.form.submit()" aria-label="Assegna camera {{ $c->numero }}"
                                            class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm bg-white">
                                        <option value="">— non assegnata —</option>
                                        @foreach ($personale as $p)
                                            <option value="{{ $p->id }}" @selected($assegnata == $p->id)>{{ $p->nomeCompleto() }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                <span class="text-sm text-slate-500">
                                    {{ $assegnata ? ($personale->firstWhere('id', $assegnata)?->nomeCompleto() ?? '—') : '—' }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-gray-200 p-6 text-sm text-slate-500">Nessuna camera attiva.</div>
        @endforelse
    </div>

    <p class="mt-4 px-1 text-sm text-slate-400">Il tipo di camera (partenza, fermata, arrivo) si ricava dalle prenotazioni. Le camere con ospite che resta tornano «Da pulire» ogni giorno.</p>
</div>
@endsection
