@php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.');
    $colore = config('permessi.moduli.bistro.colore', '#C9822B');
    $configJs = ['url' => route('bistro.camera.verifica'), 'metodo' => old('metodo', 'camera'), 'camera' => old('camera', ''), 'totale' => (float) $totale, 'righe' => []];
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Punto cassa')
@section('page_title', 'Punto cassa')
@section('page_color', $colore)

@section('content')
<div class="w-full ">

    <a href="{{ route('bistro.mappa') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Torna ai tavoli</a>

    @include('frontdesk.partials.messaggi')

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6">

        <section>
            <div class="flex flex-wrap gap-2 mb-5">
                @foreach ($categorie as $chiave => $nome)
                    <a href="{{ route('bistro.cassa', ['cat' => $chiave]) }}"
                       class="px-5 py-2 rounded-full text-sm font-medium border transition {{ $chiave === $cat ? 'bg-[#C9822B] border-[#C9822B] text-white' : 'bg-white border-gray-200 text-slate-600 hover:border-[#C9822B]' }}">{{ $nome }}</a>
                @endforeach
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @forelse ($articoli as $a)
                    <form method="POST" action="{{ route('bistro.cassa.aggiungi') }}">
                        @csrf
                        <input type="hidden" name="articolo_id" value="{{ $a->id }}">
                        <button class="w-full h-24 bg-white rounded-xl border border-gray-200 hover:border-[#C9822B] transition text-center px-2">
                            <span class="block text-[15px] font-medium">{{ $a->nome }}</span>
                            <span class="block text-sm text-slate-500 mt-1">€ {{ $eur($a->prezzo) }}</span>
                        </button>
                    </form>
                @empty
                    <p class="col-span-full text-slate-500 text-sm">Nessun articolo in questa categoria. @if (auth()->user()->isGestore()) <a href="{{ route('bistro.listino') }}" class="underline text-fdBlue">Aggiungili dal listino</a>.@endif</p>
                @endforelse
            </div>
        </section>

        <aside class="self-start">
            <div class="bg-white rounded-2xl border border-gray-200 mb-4">
                <h2 class="px-5 py-4 border-b border-gray-100 text-lg font-medium">Ordine</h2>
                <div class="px-5 py-3 min-h-[100px] text-[15px]">
                    @forelse ($righe as $r)
                        <div class="flex items-center justify-between gap-3 py-1.5">
                            <span>{{ $r->nome }}<span class="block text-xs text-slate-400">× {{ $r->quantita }}</span></span>
                            <span class="flex items-center gap-2 shrink-0">
                                € {{ $eur($r->prezzo * $r->quantita) }}
                                <form method="POST" action="{{ route('bistro.cassa.meno', ['riga' => $r->id, 'cat' => $cat]) }}">@csrf
                                    <button class="w-7 h-7 rounded-md border border-gray-200 text-slate-500 hover:bg-slate-50 leading-none" aria-label="Uno in meno">&minus;</button>
                                </form>
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400 pt-2">Nessun articolo.</p>
                    @endforelse
                </div>
                <div class="flex items-center justify-between px-5 py-4 border-t border-gray-100">
                    <span class="text-slate-500">Totale</span>
                    <span class="text-xl font-medium">€ {{ $eur($totale) }}</span>
                </div>
            </div>

            @if ($righe->isNotEmpty())
                <form method="POST" action="{{ route('bistro.cassa.paga') }}" x-data="bistroPagamento(@js($configJs))">
                    @csrf
                    @include('bistro.partials.pagamento')
                </form>
                <form method="POST" action="{{ route('bistro.cassa.annulla') }}" class="mt-3" onsubmit="return confirm('Svuotare l\'ordine?')">@csrf
                    <button class="w-full text-sm text-slate-500 hover:text-[#B5563C] py-2">Svuota ordine</button>
                </form>
            @endif
        </aside>
    </div>
</div>
@endsection

@push('scripts')
@include('bistro.partials.script-pagamento')
@endpush
