@php
    app()->setLocale(session('locale', 'it'));
    $pctIstat = $giorniTrascorsiMese > 0 ? min(100, round($giorniIstatInviati / $giorniTrascorsiMese * 100)) : 0;
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Adempimenti')
@section('page_title', 'Adempimenti')
@section('page_color', '#4F86B0')

@section('content')

<a href="{{ route('frontdesk.prenotazioni') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">
    &larr; Torna al FrontDesk
</a>

@include('frontdesk.partials.messaggi')

<div class="grid grid-cols-1 gap-6">

    {{-- ===== ALLOGGIATI WEB — SOLO OGGI ===== --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-1">
            <h2 class="text-lg font-medium">Alloggiati Web · Polizia di Stato</h2>
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400">{{ \Carbon\Carbon::today()->format('d/m/Y') }}</span>
                <a href="{{ route('adempimenti.storico') }}"
                   class="text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-300 text-deepNavy hover:bg-gray-50">
                    Storico invii
                </a>
            </div>
        </div>
        <p class="text-xs text-slate-400 mb-4">
            {{ $prenotazioni->whereNotNull('alloggiati_inviato_il')->count() }} inviate,
            {{ $prenotazioni->whereNull('alloggiati_inviato_il')->count() }} da inviare
            · Solo le schedine di oggi. Per consultare il passato usa «Storico invii».
        </p>

        @forelse ($prenotazioni as $p)
            @php
                $o = $p->ospite;
                $camera = $p->camere->first();
                $completo = $p->alloggiatiCompleto();
                $nPersone = $p->personePreviste();
                $inviato = (bool) $p->alloggiati_inviato_il;
            @endphp
            <div id="camera-{{ $camera->numero ?? '' }}" class="flex items-center justify-between py-2.5 border-b border-gray-100 last:border-b-0 scroll-mt-20">
                <div class="flex items-center gap-2">
                    <span @class(['w-2.5 h-2.5 rounded-full', 'bg-okGreen' => $inviato, 'bg-warmGold' => ! $inviato && $completo, 'bg-[#B5563C]' => ! $completo && ! $inviato])></span>
                    <span>{{ $o->cognome }} {{ \Illuminate\Support\Str::substr($o->nome, 0, 1) }}.@if ($nPersone > 1) <span class="text-xs text-slate-400">+{{ $nPersone - 1 }}</span>@endif · {{ $camera->numero ?? '—' }}</span>
                </div>

                @if ($inviato)
                    <span class="text-xs text-slate-400 flex items-center gap-3">
                        Inviato {{ $p->alloggiati_inviato_il->format('d/m H:i') }}
                        @if ($p->invio_alloggiati_id)
                            <a href="{{ route('adempimenti.alloggiati.pdf', $p->invio_alloggiati_id) }}" target="_blank" rel="noopener" class="font-medium text-fdBlue hover:underline">PDF</a>
                            <a href="{{ route('adempimenti.alloggiati.txt', $p->invio_alloggiati_id) }}" class="font-medium text-fdBlue hover:underline">File TXT</a>
                        @endif
                    </span>
                @elseif (! $completo)
                    <a href="{{ route('prenotazioni.checkin', $p) }}" class="text-xs font-medium text-[#B5563C] hover:underline">Dati incompleti</a>
                @elseif ($invioMassivoOggi)
                    <form method="POST" action="{{ route('prenotazioni.adempimenti.invia', $p) }}">
                        @csrf
                        <button class="text-xs font-medium px-2.5 py-1 rounded-full bg-fdBlue text-white hover:opacity-90">Invia singolarmente</button>
                    </form>
                @else
                    <span class="text-xs text-slate-400">Pronta per l'invio massivo</span>
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-400 py-4">Nessun ospite in casa o in arrivo oggi.</p>
        @endforelse

        {{-- Invio massivo --}}
        <div class="mt-5 pt-4 border-t border-gray-100 flex flex-wrap items-center gap-3">
            @if ($invioMassivoOggi)
                <p class="text-sm text-okGreen font-medium">
                    Invio massivo di oggi fatto alle {{ $invioMassivoOggi->inviato_il->format('H:i') }}
                    ({{ count($invioMassivoOggi->schedine) }} {{ count($invioMassivoOggi->schedine) === 1 ? 'schedina' : 'schedine' }}).
                </p>
                <a href="{{ route('adempimenti.alloggiati.pdf', $invioMassivoOggi) }}" target="_blank" rel="noopener"
                   class="text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 text-deepNavy hover:bg-gray-50">Vedi PDF</a>
                <a href="{{ route('adempimenti.alloggiati.txt', $invioMassivoOggi) }}"
                   class="text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 text-deepNavy hover:bg-gray-50">Scarica file TXT</a>
            @else
                <form method="POST" action="{{ route('adempimenti.alloggiati.massivo') }}">
                    @csrf
                    <button @disabled($nCompletiDaInviare === 0)
                            class="bg-fdBlue hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed text-white font-medium px-4 py-2 rounded-lg text-sm">
                        Invia tutte @if ($nCompletiDaInviare > 0) ({{ $nCompletiDaInviare }}) @endif
                    </button>
                </form>
                @if ($nCompletiDaInviare === 0)
                    <p class="text-xs text-slate-400">Nessuna schedina completa da inviare.</p>
                @endif
            @endif
        </div>

        {{-- Invii di oggi (massivo + singoli) --}}
        @if (isset($inviiOggi) && $inviiOggi->isNotEmpty())
            <div class="mt-5 pt-4 border-t border-gray-100">
                <h3 class="text-sm font-medium mb-2">Invii di oggi</h3>
                @foreach ($inviiOggi as $i)
                    <div class="flex items-center justify-between py-1.5 text-sm gap-2">
                        <span class="flex items-center gap-2 flex-wrap">
                            <span class="text-slate-500">{{ $i->inviato_il->format('H:i') }}</span>
                            <span @class(['text-xs font-medium px-2 py-0.5 rounded-full', 'bg-okGreen text-white' => $i->massivo(), 'bg-gray-100 text-slate-600' => ! $i->massivo()])>
                                {{ $i->massivo() ? 'Massivo' : 'Singolo' }}
                            </span>
                            <span class="text-xs text-slate-400">{{ count($i->schedine) }} {{ count($i->schedine) === 1 ? 'schedina' : 'schedine' }}</span>
                        </span>
                        <span class="flex items-center gap-3 shrink-0">
                            <a href="{{ route('adempimenti.alloggiati.pdf', $i) }}" target="_blank" rel="noopener" class="text-xs font-medium text-fdBlue hover:underline">PDF</a>
                            <a href="{{ route('adempimenti.alloggiati.txt', $i) }}" class="text-xs font-medium text-fdBlue hover:underline">File TXT</a>
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ===== ISTAT ===== --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 h-fit">
        <div class="flex items-center justify-between mb-1">
            <h2 class="text-lg font-medium">ISTAT · Movimento mensile</h2>
            <span class="text-sm text-slate-400">{{ ucfirst(\Carbon\Carbon::today()->translatedFormat('F')) }}</span>
        </div>
        <p class="text-sm text-slate-500 mb-3">{{ $giorniIstatInviati }} giorni inviati su {{ $giorniTrascorsiMese }} trascorsi</p>

        <progress class="barra-progresso mb-4" max="100" value="{{ $pctIstat }}" aria-label="Giorni ISTAT inviati">{{ $pctIstat }}%</progress>

        @if ($istatInviatoOggi)
            <p class="text-sm text-okGreen font-medium">Movimento di oggi già registrato.</p>
        @else
            <form method="POST" action="{{ route('adempimenti.istat.invia') }}">
                @csrf
                <button class="bg-fdBlue hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Invia movimento di oggi</button>
            </form>
        @endif
    </div>
</div>

<p class="text-xs text-slate-400 mt-5">
    Il PDF è un riepilogo interno. Il file TXT segue il tracciato ufficiale (168 caratteri/riga) da caricare sul portale Alloggiati Web.
    Per i codici comuni/stati scarica le tabelle dal portale e importale con
    <code class="text-[11px] bg-gray-100 px-1 rounded">php artisan alloggiati:import-tabelle</code>.
</p>
@endsection
