@php
    app()->setLocale(session('locale', 'it'));
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $campo = 'border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slateBlue';
    $totale = $righe->sum(fn ($r) => $r->quantita_ordinata * (float) $r->prezzo_acquisto);
    $testo = "Buongiorno,\nvorremmo ordinare:\n\n" . $righe->map(fn ($r) => '- ' . $n($r->quantita_ordinata) . ' ' . $r->unita_misura . ' ' . $r->nome)->implode("\n") . "\n\nGrazie.";
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Ordine #' . $o->id)
@section('page_title', 'Ordine #' . $o->id . ' · ' . $o->fornitore)
@section('page_color', '#4D6C86')

@section('content')
<div class="w-full max-w-4xl">
    @include('magazzino.partials.tabs')

    <p class="text-sm text-slate-500 mb-5">
        <b class="text-deepNavy">{{ $stati[$o->stato] }}</b>
        @if ($o->creato_da) · creato da {{ $utenti[$o->creato_da] ?? '—' }}@else · generato dalla soglia di riordino @endif
        @if ($o->approvato_il) · approvato da {{ $utenti[$o->approvato_da] ?? '—' }} il {{ \Carbon\Carbon::parse($o->approvato_il)->format('d/m/Y H:i') }}@endif
        @if ($o->inviato_il) · inviato il {{ \Carbon\Carbon::parse($o->inviato_il)->format('d/m/Y H:i') }}@endif
        @if ($o->consegnato_il) · consegnato il {{ \Carbon\Carbon::parse($o->consegnato_il)->format('d/m/Y H:i') }}@endif
    </p>

    {{-- ===== RIGHE ===== --}}
    @if ($o->stato === 'inviato')
    <form method="POST" action="{{ route('magazzino.ordine.consegna', $o->id) }}">@csrf
    @endif
    <div class="bg-white rounded-2xl border border-gray-200 mb-5">
        <div class="divide-y divide-gray-100">
            @forelse ($righe as $r)
                @if ($o->stato === 'bozza')
                    <div class="flex flex-wrap items-center gap-3 px-5 py-3">
                        <form method="POST" action="{{ route('magazzino.ordine.riga.aggiorna', [$o->id, $r->id]) }}" class="flex flex-wrap items-center gap-3 flex-1">@csrf
                            <span class="flex-1 min-w-[160px] font-medium">{{ $r->nome }}</span>
                            <input name="quantita_ordinata" type="number" step="0.001" min="0.001" required value="{{ (float) $r->quantita_ordinata }}" class="w-28 text-right {{ $campo }}" aria-label="Quantità"> <span class="text-sm text-slate-400 w-8">{{ $r->unita_misura }}</span>
                            <input name="prezzo_acquisto" type="number" step="0.01" min="0" value="{{ $r->prezzo_acquisto }}" placeholder="€" class="w-24 text-right {{ $campo }}" aria-label="Prezzo">
                            <button class="text-sm font-medium text-slateBlue hover:underline">Salva</button>
                        </form>
                        <form method="POST" action="{{ route('magazzino.ordine.riga.elimina', [$o->id, $r->id]) }}">@csrf<button class="text-sm text-slate-400 hover:text-[#B5563C]">Togli</button></form>
                    </div>
                @else
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                        <span class="font-medium">{{ $r->nome }}</span>
                        <span class="flex items-center gap-4 text-sm">
                            <span class="text-slate-500">ordinati {{ $n($r->quantita_ordinata) }} {{ $r->unita_misura }}@if ($r->prezzo_acquisto !== null) · € {{ number_format($r->prezzo_acquisto, 2, ',', '.') }}@endif</span>
                            @if ($o->stato === 'inviato')
                                <label class="flex items-center gap-1.5 text-slate-600">consegnati
                                    <input name="consegnata[{{ $r->id }}]" type="number" step="0.001" min="0" required value="{{ (float) $r->quantita_ordinata }}" class="w-24 text-right {{ $campo }}"></label>
                            @elseif ($o->stato === 'consegnato')
                                <span class="{{ (float) $r->quantita_consegnata < (float) $r->quantita_ordinata ? 'text-[#B5563C] font-medium' : 'text-okGreen' }}">consegnati {{ $n($r->quantita_consegnata) }}</span>
                            @endif
                        </span>
                    </div>
                @endif
            @empty
                <p class="px-5 py-8 text-center text-slate-400 text-sm">Nessun articolo nell'ordine.</p>
            @endforelse
        </div>
        <div class="flex items-center justify-between px-5 py-4 border-t border-gray-100">
            <span class="text-slate-500">Totale stimato</span><span class="text-xl font-medium">€ {{ number_format($totale, 2, ',', '.') }}</span>
        </div>
    </div>
    @if ($o->stato === 'inviato')
        <button class="bg-okGreen hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm mb-5">Registra consegna e carica le scorte</button>
    </form>
    @endif

    @if ($o->stato === 'bozza' && $disponibili->isNotEmpty())
        <form method="POST" action="{{ route('magazzino.ordine.riga.aggiungi', $o->id) }}" class="bg-white rounded-2xl border border-gray-200 p-5 mb-5 flex flex-wrap items-end gap-3">@csrf
            <label><span class="block text-xs text-slate-500 mb-1">Aggiungi articolo</span>
                <select name="articolo_id" class="{{ $campo }}">@foreach ($disponibili as $d)<option value="{{ $d->id }}">{{ $d->nome }} ({{ $d->unita_misura }})</option>@endforeach</select></label>
            <label><span class="block text-xs text-slate-500 mb-1">Quantità (vuoto = quella standard)</span><input name="quantita" type="number" step="0.001" min="0.001" class="w-28 {{ $campo }}"></label>
            <button class="bg-white border border-gray-200 hover:border-slateBlue text-slate-700 font-medium px-4 py-2 rounded-lg text-sm">Aggiungi</button>
        </form>
    @endif

    {{-- ===== AZIONI ===== --}}
    <div class="flex flex-wrap items-center gap-3">
        @if ($o->stato === 'bozza')
            <form method="POST" action="{{ route('magazzino.ordine.approva', $o->id) }}">@csrf
                <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">Approva ordine</button></form>
        @endif
        @if ($o->stato === 'approvato')
            <form method="POST" action="{{ route('magazzino.ordine.invia', $o->id) }}">@csrf
                <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">Segna come inviato</button></form>
        @endif
        @if (in_array($o->stato, ['approvato', 'inviato']) && $o->fornitore_email)
            <a href="mailto:{{ $o->fornitore_email }}?subject={{ rawurlencode('Ordine #' . $o->id) }}&body={{ rawurlencode($testo) }}" class="bg-white border border-gray-200 hover:border-slateBlue text-slate-700 font-medium px-5 py-2.5 rounded-lg text-sm">Scrivi al fornitore</a>
        @endif
        @if (in_array($o->stato, ['bozza', 'approvato', 'inviato']))
            <form method="POST" action="{{ route('magazzino.ordine.annulla', $o->id) }}" onsubmit="return confirm('Annullare l\'ordine?')">@csrf
                <button class="text-sm text-slate-400 hover:text-[#B5563C] px-2">Annulla ordine</button></form>
        @endif
    </div>
    @if (in_array($o->stato, ['approvato', 'inviato']))
        <details class="mt-4 text-sm"><summary class="cursor-pointer text-slate-500">Testo dell'ordine</summary><pre class="bg-white border border-gray-200 rounded-xl p-4 mt-2 whitespace-pre-wrap">{{ $testo }}</pre></details>
    @endif
</div>
@endsection
