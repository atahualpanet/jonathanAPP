@php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.');
    $colore = config('permessi.moduli.bistro.colore', '#C9822B');
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Comanda')
@section('page_title', 'Comanda')
@section('page_color', $colore)

@section('content')
<div class="w-full ">

    <a href="{{ route('bistro.mappa', ['zona' => $tavolo->zona]) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Torna ai tavoli</a>

    @include('frontdesk.partials.messaggi')

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6">

        {{-- ===== LISTINO ===== --}}
        <section>
            @if (! $comanda)
                <form method="POST" action="{{ route('bistro.apri', $tavolo->id) }}" class="bg-white rounded-2xl border border-gray-200 p-6 max-w-md">
                    @csrf
                    <h2 class="text-lg font-medium mb-1">Apri il tavolo {{ $tavolo->numero }}</h2>
                    <p class="text-sm text-slate-500 mb-4">Quanti coperti?</p>
                    <div class="flex items-center gap-3">
                        <input type="number" name="coperti" min="1" max="99" required inputmode="numeric" value="{{ old('coperti') }}"
                               class="w-24 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <button class="bg-[#C9822B] hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">Apri tavolo</button>
                    </div>
                </form>
            @else
                <div class="flex flex-wrap gap-2 mb-5">
                    @foreach ($categorie as $chiave => $nome)
                        <a href="{{ route('bistro.comanda', ['tavolo' => $tavolo->id, 'cat' => $chiave]) }}"
                           class="px-5 py-2 rounded-full text-sm font-medium border transition {{ $chiave === $cat ? 'bg-[#C9822B] border-[#C9822B] text-white' : 'bg-white border-gray-200 text-slate-600 hover:border-[#C9822B]' }}">{{ $nome }}</a>
                    @endforeach
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @forelse ($articoli as $a)
                        <form method="POST" action="{{ route('bistro.aggiungi', $tavolo->id) }}">
                            @csrf
                            <input type="hidden" name="articolo_id" value="{{ $a->id }}">
                            <button class="w-full h-24 bg-white rounded-xl border border-gray-200 hover:border-[#C9822B] transition text-center px-2">
                                <span class="block text-[15px] font-medium">{{ $a->nome }}</span>
                                <span class="block text-sm text-slate-500 mt-1">€ {{ $eur($a->prezzo) }}</span>
                            </button>
                        </form>
                    @empty
                        <p class="col-span-full text-slate-500 text-sm">Nessun articolo in questa categoria.</p>
                    @endforelse
                </div>
            @endif
        </section>

        {{-- ===== RIEPILOGO TAVOLO ===== --}}
        <aside class="bg-white rounded-2xl border border-gray-200 self-start">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-lg font-medium">Tavolo {{ $tavolo->numero }}</h2>
                @if ($comanda)<span class="text-sm text-slate-500">{{ $comanda->coperti }} coperti</span>@endif
            </div>

            <div class="px-5 py-3 min-h-[120px]">
                @php $gruppo = null; @endphp
                @forelse ($righe as $r)
                    @if ($r->categoria !== $gruppo)
                        @php $gruppo = $r->categoria; @endphp
                        <p class="text-xs font-medium text-[#C9822B] mt-3 first:mt-0">{{ $categorie[$gruppo] ?? ucfirst($gruppo) }}</p>
                    @endif
                    <div class="flex items-start justify-between gap-3 py-1.5 {{ $r->stato === 'nuova' ? '' : 'opacity-60' }}">
                        <span class="text-[15px]">{{ $r->nome }}<span class="block text-xs text-slate-400">× {{ $r->quantita }}@if ($r->stato !== 'nuova') · {{ $r->stato }}@endif</span></span>
                        <span class="flex items-center gap-2 shrink-0 text-[15px]">
                            € {{ $eur($r->prezzo * $r->quantita) }}
                            @if ($r->stato === 'nuova')
                                <form method="POST" action="{{ route('bistro.meno', ['riga' => $r->id, 'cat' => $cat]) }}">@csrf
                                    <button class="w-7 h-7 rounded-md border border-gray-200 text-slate-500 hover:bg-slate-50 leading-none" aria-label="Uno in meno">&minus;</button>
                                </form>
                            @endif
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 pt-2">Nessun ordine.</p>
                @endforelse
            </div>

            <div class="px-5 py-4 border-t border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-slate-500">Totale</span>
                    <span class="text-xl font-medium">€ {{ $eur($totale) }}</span>
                </div>
                @if ($comanda)
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('bistro.invia', $tavolo->id) }}" class="flex-1">@csrf
                            <button class="w-full bg-[#C9822B] hover:opacity-90 text-white font-medium px-4 py-2.5 rounded-lg text-sm">Invia in cucina</button>
                        </form>
                        <form method="POST" action="{{ route('bistro.conto.chiedi', $tavolo->id) }}">@csrf
                            <button class="bg-white border border-gray-200 hover:border-[#C9822B] text-slate-700 font-medium px-5 py-2.5 rounded-lg text-sm">Conto</button>
                        </form>
                    </div>
                @endif
            </div>
        </aside>
    </div>
</div>
@endsection
