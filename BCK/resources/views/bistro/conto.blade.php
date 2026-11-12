@php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.');
    $colore = config('permessi.moduli.bistro.colore', '#C9822B');
    $configJs = [
        'url' => route('bistro.camera.verifica'), 'metodo' => old('metodo', 'camera'), 'camera' => old('camera', ''),
        'totale' => (float) $totale,
        'righe' => $righe->map(fn ($r) => ['id' => $r->id, 'prezzo' => (float) $r->prezzo])->values(),
    ];
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Conto e addebito')
@section('page_title', 'Conto e addebito')
@section('page_color', $colore)

@section('content')
<div class="w-full ">

    <a href="{{ route('bistro.mappa', ['zona' => $tavolo->zona]) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Torna ai tavoli</a>

    @include('frontdesk.partials.messaggi')

    <form method="POST" action="{{ route('bistro.paga', $tavolo->id) }}" class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="bistroPagamento(@js($configJs))">
        @csrf
        <input type="hidden" name="dividi" value="1" :disabled="! dividi">

        {{-- ===== CONTO ===== --}}
        <section class="bg-white rounded-2xl border border-gray-200 self-start">
            <h2 class="px-5 py-4 border-b border-gray-100 text-lg font-medium">Tavolo {{ $tavolo->numero }}</h2>

            <div class="px-5 py-3 text-[15px]">
                @foreach ($righe as $r)
                    <div class="flex items-center justify-between gap-3 py-1.5">
                        <span>{{ $r->nome }}@if ($r->quantita > 1) × {{ $r->quantita }}@endif</span>
                        <span class="flex items-center gap-3">
                            <span x-show="dividi" x-cloak class="flex items-center gap-1 text-sm text-slate-500">
                                paga
                                <input type="number" name="quantita[{{ $r->id }}]" min="0" max="{{ $r->quantita }}" x-model.number="q[{{ $r->id }}]" :disabled="! dividi" inputmode="numeric"
                                       class="w-14 text-center border border-gray-200 rounded-lg py-1 text-sm" aria-label="Quanti {{ $r->nome }} incassare">
                            </span>
                            <span>€ {{ $eur($r->prezzo * $r->quantita) }}</span>
                        </span>
                    </div>
                @endforeach
            </div>

            @if ($pagamenti->isNotEmpty())
                <div class="px-5 py-3 border-t border-gray-100 text-sm text-slate-500">
                    <p class="font-medium text-slate-600 mb-1">Già incassato</p>
                    @foreach ($pagamenti as $pg)
                        <div class="flex justify-between py-0.5">
                            <span>{{ ucfirst($pg->metodo) }}@if ($pg->camera) · camera {{ $pg->camera }}@endif</span>
                            <span>€ {{ $eur($pg->importo) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center justify-between px-5 py-4 border-t border-gray-100">
                <span class="text-slate-500">Resta da pagare</span>
                <span class="text-2xl font-medium">€ {{ $eur($totale) }}</span>
            </div>
            <div class="px-5 pb-5">
                <button type="button" @click="dividi = ! dividi"
                        class="bg-white border border-gray-200 hover:border-[#C9822B] text-slate-700 font-medium px-5 py-2 rounded-lg text-sm"
                        x-text="dividi ? 'Paga tutto' : 'Dividi'">Dividi</button>
                <p x-show="dividi" x-cloak class="text-xs text-slate-400 mt-2">Scegli quanti pezzi di ogni voce incassare ora, con il metodo qui a fianco. Il resto resta sul conto del tavolo.</p>
            </div>
        </section>

        {{-- ===== PAGAMENTO ===== --}}
        <div>@include('bistro.partials.pagamento')</div>
    </form>
</div>
@endsection

@push('scripts')
@include('bistro.partials.script-pagamento')
@endpush
