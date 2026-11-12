@php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($v) => number_format((float) $v, 2, ',', '.');
    $colore = config('permessi.moduli.bistro.colore', '#C9822B');
    $campo = 'border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white';
    $nomi = ['camera' => 'In camera (fattura)', 'carta' => 'Carta', 'contanti' => 'Contanti'];
    $daBattere = $perAliquota->whereIn('metodo', ['carta', 'contanti']);
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Incassi e IVA')
@section('page_title', 'Incassi e IVA')
@section('page_color', $colore)

@section('content')
<div class="w-full max-w-4xl">

    <a href="{{ route('bistro.mappa') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Torna ai tavoli</a>
    @include('frontdesk.partials.messaggi')

    <form method="GET" class="flex flex-wrap items-end gap-3 mb-6">
        <label><span class="block text-xs text-slate-500 mb-1">Dal</span><input type="date" name="dal" value="{{ $dal }}" class="{{ $campo }}"></label>
        <label><span class="block text-xs text-slate-500 mb-1">Al</span><input type="date" name="al" value="{{ $al }}" class="{{ $campo }}"></label>
        <button class="bg-[#C9822B] text-white font-medium px-5 py-2 rounded-lg text-sm">Mostra</button>
        <a href="{{ route('bistro.iva') }}" class="ml-auto text-sm text-slate-500 underline">Correggi l'aliquota di vecchi addebiti</a>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        @foreach ($nomi as $k => $nome)
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <p class="text-sm text-slate-500">{{ $nome }}</p>
                <p class="text-2xl font-medium">€ {{ $eur($perMetodo[$k]->totale ?? 0) }}</p>
                <p class="text-xs text-slate-400">{{ $perMetodo[$k]->n ?? 0 }} incassi</p>
            </div>
        @endforeach
    </div>

    <h2 class="text-base font-medium mb-2">IVA per aliquota</h2>
    <div class="bg-white rounded-2xl border border-gray-200 overflow-x-auto mb-3">
        <table class="w-full text-[15px] min-w-[560px]">
            <thead class="text-left text-sm text-slate-500 border-b border-gray-100"><tr><th class="px-5 py-3 font-normal">Incasso</th><th class="px-3 py-3 font-normal text-right">Aliquota</th><th class="px-3 py-3 font-normal text-right">Lordo</th><th class="px-3 py-3 font-normal text-right">Imponibile</th><th class="px-3 py-3 font-normal text-right">IVA</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($perAliquota as $r)
                    <tr><td class="px-5 py-2.5">{{ $nomi[$r->metodo] ?? $r->metodo }}</td><td class="px-3 py-2.5 text-right">{{ rtrim(rtrim(number_format($r->aliquota, 2, ',', ''), '0'), ',') }}%</td><td class="px-3 py-2.5 text-right">€ {{ $eur($r->lordo) }}</td><td class="px-3 py-2.5 text-right">€ {{ $eur($r->imponibile) }}</td><td class="px-3 py-2.5 text-right">€ {{ $eur($r->imposta) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">Nessun incasso nel periodo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="text-sm text-slate-500">Carta e contanti del periodo: <b>€ {{ $eur($daBattere->sum('lordo')) }}</b> lordi (IVA € {{ $eur($daBattere->sum('imposta')) }}). Questo è un riepilogo di controllo: lo scontrino o la ricevuta fiscale si emettono con il tuo registratore di cassa, qui non vengono emessi. Gli addebiti in camera si fatturano dal folio.</p>
</div>
@endsection
