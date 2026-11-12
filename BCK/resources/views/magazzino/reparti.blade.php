@php
    app()->setLocale(session('locale', 'it'));
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $campo = 'border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slateBlue';
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Magazzini di reparto')
@section('page_title', 'Magazzino & Fornitori')
@section('page_color', '#4D6C86')

@section('content')
<div class="w-full max-w-6xl">
    @include('magazzino.partials.tabs')

    <p class="text-sm text-slate-500 mb-4 max-w-3xl">Ogni reparto ha il suo magazzino. Il magazzino centrale <b>invia</b> la merce al reparto (esce subito dal centrale) e il reparto la <b>riceve e conferma</b> la quantità arrivata; le vendite del Bistro scaricano dal reparto dell'articolo, non dal centrale. La soglia del reparto segnala quando serve un nuovo trasferimento; gli ordini ai fornitori guardano solo il centrale.</p>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex flex-wrap gap-2">
            @foreach ($reparti as $r)
                <a href="{{ route('magazzino.reparti', ['reparto' => $r->id]) }}" class="px-5 py-2 rounded-full text-sm font-medium border {{ $rep && $rep->id === $r->id ? 'bg-slateBlue border-slateBlue text-white' : 'bg-white border-gray-200 text-slate-600' }}">{{ $r->nome }}</a>
            @endforeach
        </div>
        <form method="POST" action="{{ route('magazzino.reparti.crea') }}" class="flex items-center gap-2">@csrf
            <input name="nome" required maxlength="60" placeholder="Nuovo reparto" class="{{ $campo }}">
            <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Aggiungi</button>
        </form>
    </div>

    @if ($richieste->isNotEmpty())
        <div class="bg-white rounded-2xl border border-[#C9822B]/50 mb-5">
            <p class="px-5 py-3 text-sm font-medium border-b border-gray-100">Richieste dai reparti ({{ $richieste->count() }})</p>
            @foreach ($richieste as $q)
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-t border-gray-50 first:border-t-0">
                    <span class="text-[15px]"><b>{{ $q->reparto }}</b> chiede {{ $n($q->quantita) }} {{ $q->unita_misura }} di {{ $q->nome }}@if ($q->automatica) <span class="text-xs text-slate-400">(automatica: scorta alla soglia)</span>@endif
                        <span class="block text-xs text-slate-400">nel centrale: {{ $n($q->centrale) }} {{ $q->unita_misura }}</span></span>
                    <span class="flex items-center gap-2">
                        <form method="POST" action="{{ route('magazzino.richiesta.evadi', $q->id) }}" class="flex items-center gap-2">@csrf
                            <input name="quantita" type="number" step="0.001" min="0.001" required value="{{ (float) $q->quantita }}" class="w-24 {{ $campo }}" aria-label="Quantità da inviare">
                            <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Invia</button>
                        </form>
                        <form method="POST" action="{{ route('magazzino.richiesta.annulla', $q->id) }}">@csrf<button class="text-sm text-slate-400 hover:text-[#B5563C] px-1">Rifiuta</button></form>
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    @if ($rep)
        @if ($inArrivo->isNotEmpty())
            <div class="bg-white rounded-2xl border border-gray-200 mb-5">
                <p class="px-5 py-3 text-sm font-medium border-b border-gray-100">Inviato a {{ $rep->nome }}, non ancora ricevuto</p>
                @foreach ($inArrivo as $t)
                    <form method="POST" action="{{ route('magazzino.trasferimento.annulla', $t->id) }}" class="flex items-center justify-between gap-3 px-5 py-2.5 text-[15px]" onsubmit="return confirm('Annullare il trasferimento? La merce torna nel centrale.')">@csrf
                        <span>{{ $n($t->quantita_inviata) }} {{ $t->unita_misura }} · {{ $t->nome }} <span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($t->inviato_il)->format('d/m H:i') }}</span></span>
                        <button class="text-sm text-slate-400 hover:text-[#B5563C]">Annulla</button>
                    </form>
                @endforeach
            </div>
        @endif
        @php $conDiff = $recenti->filter(fn ($t) => (float) $t->quantita_ricevuta !== (float) $t->quantita_inviata); @endphp
        @if ($conDiff->isNotEmpty())
            <div class="bg-[#B5563C]/10 border border-[#B5563C]/30 rounded-2xl px-5 py-3 mb-5 text-sm text-[#8E3F2A]">
                <b>Consegne ricevute con differenza:</b> {{ $conDiff->map(fn ($t) => $t->nome . ' (inviati ' . $n($t->quantita_inviata) . ', ricevuti ' . $n($t->quantita_ricevuta) . ')')->implode('; ') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-gray-200 overflow-x-auto">
            <table class="w-full text-[15px] min-w-[860px]">
                <thead class="text-left text-sm text-slate-500 border-b border-gray-100">
                    <tr><th class="px-5 py-3 font-normal">Articolo</th><th class="px-3 py-3 font-normal text-right">Centrale</th><th class="px-3 py-3 font-normal text-right">{{ $rep->nome }}</th><th class="px-3 py-3 font-normal">Soglia · quantità abituale</th><th class="px-3 py-3 font-normal">Invia dal centrale</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($righe as $a)
                        @php $sotto = $a->soglia !== null && (float) $a->scorta <= (float) $a->soglia; @endphp
                        <tr>
                            <td class="px-5 py-3 font-medium">{{ $a->nome }} <span class="text-xs text-slate-400">{{ $a->unita_misura }}</span></td>
                            <td class="px-3 py-3 text-right text-slate-500">{{ $n($a->centrale) }}</td>
                            <td class="px-3 py-3 text-right {{ $sotto ? 'text-[#B5563C] font-medium' : '' }}">{{ $n($a->scorta ?? 0) }}</td>
                            <td class="px-3 py-3">
                                <form method="POST" action="{{ route('magazzino.giacenza', [$rep->id, $a->articolo_id]) }}" class="flex items-center gap-2">@csrf
                                    <input name="soglia" type="number" step="0.001" min="0" value="{{ $a->soglia !== null ? (float) $a->soglia : '' }}" placeholder="soglia" class="w-24 {{ $campo }}" aria-label="Soglia del reparto">
                                    <input name="quantita_rifornimento" type="number" step="0.001" min="0" value="{{ $a->quantita_rifornimento !== null ? (float) $a->quantita_rifornimento : '' }}" placeholder="q.tà" class="w-24 {{ $campo }}" aria-label="Quantità abituale">
                                    <button class="text-sm font-medium text-slateBlue hover:underline">Salva</button>
                                </form>
                            </td>
                            <td class="px-3 py-3">
                                <form method="POST" action="{{ route('magazzino.trasferisci', [$rep->id, $a->articolo_id]) }}" class="flex items-center gap-2">@csrf
                                    <input name="quantita" type="number" step="0.001" min="0.001" required value="{{ $a->quantita_rifornimento !== null ? (float) $a->quantita_rifornimento : '' }}" class="w-24 {{ $campo }}" aria-label="Quantità da trasferire">
                                    <button class="bg-white border border-gray-200 hover:border-slateBlue text-slate-700 font-medium px-3 py-2 rounded-lg text-sm">Invia</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Nessun articolo di magazzino: creali da Scorte.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
