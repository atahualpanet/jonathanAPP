@php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($v) => number_format((float) $v, 2, ',', '.');
    $colore = config('permessi.moduli.bistro.colore', '#C9822B');
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Aliquota addebiti Bistro')
@section('page_title', 'Aliquota degli addebiti Bistro')
@section('page_color', $colore)

@section('content')
<div class="w-full max-w-4xl">

    <a href="{{ route('bistro.incassi') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Incassi e IVA</a>
    @include('frontdesk.partials.messaggi')

    <p class="text-sm text-slate-500 mb-5 max-w-3xl">Gli ultimi addebiti Bistro nel folio B (extra). Puoi cambiare l'aliquota di una voce finché il folio non è stato fatturato: la fattura scorpora l'IVA con l'aliquota indicata qui. Gli importi non cambiano.</p>

    <div class="space-y-2">
        @forelse ($voci as $v)
            <form method="POST" action="{{ route('bistro.iva.salva', $v->id) }}" class="bg-white rounded-xl border border-gray-200 px-5 py-3 flex flex-wrap items-center justify-between gap-3 {{ $v->bloccata ? 'opacity-60' : '' }}">
                @csrf
                <span class="text-[15px]">{{ $v->descrizione }} <span class="text-sm text-slate-400">· {{ \Carbon\Carbon::parse($v->registrata_il)->format('d/m H:i') }} · € {{ $eur($v->importo) }}</span></span>
                <span class="flex items-center gap-2">
                    @if ($v->bloccata)
                        <span class="text-sm text-slate-500">{{ $v->aliquota !== null ? rtrim(rtrim(number_format($v->aliquota, 2, ',', ''), '0'), ',') . '%' : '—' }} · fatturato</span>
                    @else
                        <input name="aliquota" type="number" step="0.01" min="0" max="100" required value="{{ $v->aliquota }}" class="w-24 border border-gray-200 rounded-lg px-3 py-2 text-sm" aria-label="Aliquota IVA"> %
                        <button class="text-sm font-medium text-[#C9822B] hover:underline">Salva</button>
                    @endif
                </span>
            </form>
        @empty
            <p class="text-slate-500 text-sm">Nessun addebito Bistro nel folio.</p>
        @endforelse
    </div>
</div>
@endsection
