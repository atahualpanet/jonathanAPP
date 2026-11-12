@php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.');
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Minibar camera ' . $camera->numero)
@section('page_title', 'Minibar · Camera ' . $camera->numero)
@section('page_color', '#4F86B0')

@section('content')

<a href="{{ route('camere.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Torna alle camere</a>

@include('frontdesk.partials.messaggi')

@if (! $prenotazione)
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <p class="text-slate-600">Nessun ospite in casa (né partito da poco) nella camera {{ $camera->numero }}: non c'è un conto a cui addebitare il minibar.</p>
    </div>
@else
    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-lg font-medium">{{ $prenotazione->ospite->nome }} {{ $prenotazione->ospite->cognome }}</h2>
                <p class="text-sm text-slate-500">{{ $camera->tipologia->nome ?? '' }} · {{ $prenotazione->stato === 'in_casa' ? 'in casa' : 'partito' }}</p>
            </div>
            @if ($conto && $conto->stato !== 'aperto')
                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-slate-200 text-slate-600">Conto chiuso</span>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('camere.minibar.salva', $camera) }}" class="bg-white rounded-2xl border border-gray-200 p-6">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <h2 class="text-lg font-medium mb-1">Quanti ne mancano?</h2>
        <p class="text-sm text-slate-500 mb-5">Scrivi per ogni articolo quanti pezzi mancano rispetto alla dotazione. Le mancanze vengono addebitate subito sul conto della camera.</p>

        @if ($inDotazione->isNotEmpty())
            <div class="space-y-2">
                @foreach ($inDotazione as $a)
                    <div class="flex items-center justify-between gap-3 bg-slate-50 rounded-lg px-4 py-3 text-[15px]">
                        <span>{{ $a->nome }} <span class="text-xs text-slate-400">dotazione {{ $dotazione[$a->id] }} · € {{ $eur($a->prezzo) }}</span></span>
                        <span class="flex items-center gap-1.5 shrink-0">
                            <button type="button" data-meno class="w-9 h-9 rounded-lg bg-white border border-gray-200 text-lg leading-none" aria-label="Uno in meno">&minus;</button>
                            <input type="number" name="mancanti[{{ $a->id }}]" min="0" max="{{ $dotazione[$a->id] }}" value="{{ old('mancanti.' . $a->id, 0) }}" inputmode="numeric"
                                   class="w-14 text-center border border-gray-200 rounded-lg py-1.5 text-sm" aria-label="Mancanti: {{ $a->nome }}">
                            <button type="button" data-piu class="w-9 h-9 rounded-lg bg-white border border-gray-200 text-lg leading-none" aria-label="Uno in più">+</button>
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-warmGold mb-3">Per questa tipologia non c'è ancora una dotazione standard: scegli sotto gli articoli mancanti, oppure impostala dal <a href="{{ route('minibar.listino') }}" class="underline">listino minibar</a>.</p>
        @endif

        @if ($altri->isNotEmpty())
            <details class="mt-5" @if ($inDotazione->isEmpty()) open @endif>
                <summary class="cursor-pointer text-sm font-medium text-fdBlue">Altri articoli consumati (fuori dotazione)</summary>
                <div class="space-y-2 mt-3">
                    @foreach ($altri as $a)
                        <div class="flex items-center justify-between gap-3 bg-slate-50 rounded-lg px-4 py-3 text-[15px]">
                            <span>{{ $a->nome }} <span class="text-xs text-slate-400">€ {{ $eur($a->prezzo) }}</span></span>
                            <span class="flex items-center gap-1.5 shrink-0">
                                <button type="button" data-meno class="w-9 h-9 rounded-lg bg-white border border-gray-200 text-lg leading-none" aria-label="Uno in meno">&minus;</button>
                                <input type="number" name="mancanti[{{ $a->id }}]" min="0" max="20" value="{{ old('mancanti.' . $a->id, 0) }}" inputmode="numeric"
                                       class="w-14 text-center border border-gray-200 rounded-lg py-1.5 text-sm" aria-label="Consumati: {{ $a->nome }}">
                                <button type="button" data-piu class="w-9 h-9 rounded-lg bg-white border border-gray-200 text-lg leading-none" aria-label="Uno in più">+</button>
                            </span>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif

        @if ($inDotazione->isEmpty() && $altri->isEmpty())
            <p class="text-sm text-slate-500">Il listino minibar è vuoto: <a href="{{ route('minibar.listino') }}" class="underline text-fdBlue">crealo qui</a>.</p>
        @else
            <div class="flex flex-wrap items-center gap-3 mt-6">
                <input name="controllato_da" maxlength="100" placeholder="Controllato da (nome)" value="{{ old('controllato_da') }}"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                <button class="bg-fdBlue hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">Conferma controllo</button>
            </div>
            <p class="text-xs text-slate-400 mt-3">Se non manca niente lascia tutto a 0 e conferma: il controllo resta registrato senza addebiti.</p>
        @endif
    </form>

    @if ($ultimi->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 p-6 mt-6">
            <h2 class="text-base font-medium mb-3">Ultimi controlli di questo soggiorno</h2>
            <ul class="divide-y divide-gray-100 text-[15px]">
                @foreach ($ultimi as $u)
                    <li class="flex justify-between py-2">
                        <span class="text-slate-600">{{ \Carbon\Carbon::parse($u->created_at)->format('d/m H:i') }}@if ($u->controllato_da) · {{ $u->controllato_da }}@endif</span>
                        <span>{{ $u->totale > 0 ? '€ ' . $eur($u->totale) : 'completo' }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-meno], [data-piu]').forEach(b => b.addEventListener('click', () => {
    const input = b.parentElement.querySelector('input[type="number"]');
    const max = parseInt(input.max || '20', 10);
    let v = parseInt(input.value || '0', 10) + (b.hasAttribute('data-piu') ? 1 : -1);
    input.value = Math.max(0, Math.min(max, v));
}));
</script>
@endpush
