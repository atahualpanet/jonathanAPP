@php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.');
    $colore = config('permessi.moduli.bistro.colore', '#C9822B');
    $ristorante = array_keys(\App\Http\Controllers\BistroController::RISTORANTE);
    $cassa = array_keys(\App\Http\Controllers\BistroController::CASSA);
    $baseUtile = $articoli->whereIn('categoria', $ristorante)->isEmpty() || $articoli->whereIn('categoria', $cassa)->isEmpty();
    $campo = 'border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#C9822B]';
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Listino Bistro')
@section('page_title', 'Listino e tavoli Bistro')
@section('page_color', $colore)

@section('content')
<div class="w-full">

    <a href="{{ route('bistro.mappa') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Torna ai tavoli</a>

    @include('frontdesk.partials.messaggi')

    {{-- ===== ARTICOLI ===== --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <h2 class="text-lg font-medium">Listino articoli</h2>
        @if ($baseUtile)
            <form method="POST" action="{{ route('bistro.base') }}">@csrf
                <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Carica listino base (dove manca)</button>
            </form>
        @endif
    </div>
    <p class="text-sm text-slate-500 mb-5 max-w-3xl">Il prezzo viene copiato sulla comanda quando l'articolo è ordinato: se lo cambi qui, le comande già prese restano com'erano. Gli articoli disattivati non si ordinano più.<br><b>Scorta:</b> ogni articolo scarica un articolo di magazzino a ogni vendita (dal Bar o dal Ristorante, secondo dove si vende), oppure va segnato "Non tracciare la scorta". I piatti non tengono scorta.</p>

    <form method="POST" action="{{ route('bistro.articoli.crea') }}" class="bg-white rounded-2xl border border-gray-200 p-5 mb-5">
        @csrf
        <div class="flex flex-wrap items-end gap-3">
            <label class="flex-1 min-w-[200px]"><span class="block text-xs text-slate-500 mb-1">Nuovo articolo</span>
                <input name="nome" required maxlength="100" value="{{ old('nome') }}" class="w-full {{ $campo }}"></label>
            <label><span class="block text-xs text-slate-500 mb-1">Dove si vende</span>
                <select name="sezione" required class="{{ $campo }}"><option value="">Dove si vende…</option>@foreach ($sezioni as $nomeRep => $cats)<optgroup label="{{ $nomeRep }}">@foreach ($cats as $k => $nomeCat)<option value="{{ $nomeRep }}|{{ $k }}" @selected(old('sezione') === $nomeRep . '|' . $k)>{{ $nomeCat }}</option>@endforeach</optgroup>@endforeach</select></label>
            <label><span class="block text-xs text-slate-500 mb-1">Prezzo €</span>
                <input name="prezzo" type="number" step="0.01" min="0" required value="{{ old('prezzo') }}" class="w-28 {{ $campo }}"></label>
            <label><span class="block text-xs text-slate-500 mb-1">IVA % (vuoto = {{ rtrim(rtrim(number_format($ivaPredefinita, 2, ',', ''), '0'), ',') }})</span>
                <input name="aliquota_iva" type="number" step="0.01" min="0" max="100" value="{{ old('aliquota_iva') }}" class="w-24 {{ $campo }}"></label>
            <label><span class="block text-xs text-slate-500 mb-1">Scorta: scarica dal magazzino</span>
                <select name="scarica" class="{{ $campo }}"><option value="">— scegli —</option><option value="no" @selected(old('scarica') === 'no')>Non tracciare la scorta</option>@foreach ($magazzino as $m)<option value="{{ $m->id }}" @selected(old('scarica') === (string) $m->id)>{{ $m->nome }}</option>@endforeach</select></label>
            <label><span class="block text-xs text-slate-500 mb-1">Quantità a vendita</span>
                <input name="quantita_per_vendita" type="number" step="0.001" min="0.001" value="1" class="w-24 {{ $campo }}"></label>
            <button class="bg-[#C9822B] hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">Aggiungi</button>
        </div>
    </form>

    <div class="space-y-2 mb-10">
        @foreach ($articoli as $a)
            <div>
            <form method="POST" action="{{ route('bistro.articoli.aggiorna', $a->id) }}"
                  class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap items-center gap-3 {{ $a->attivo ? '' : 'opacity-60' }}">
                @csrf
                <input name="nome" required maxlength="100" value="{{ $a->nome }}" class="flex-1 min-w-[180px] {{ $campo }}">
                @php $sezA = (array_key_exists($a->categoria, $sezioni['Bar']) ? 'Bar' : 'Ristorante') . '|' . $a->categoria; @endphp
                <select name="sezione" required class="{{ $campo }}"><option value="">Dove si vende…</option>@foreach ($sezioni as $nomeRep => $cats)<optgroup label="{{ $nomeRep }}">@foreach ($cats as $k => $nomeCat)<option value="{{ $nomeRep }}|{{ $k }}" @selected($sezA === $nomeRep . '|' . $k)>{{ $nomeCat }}</option>@endforeach</optgroup>@endforeach</select>
                <input name="prezzo" type="number" step="0.01" min="0" required value="{{ $a->prezzo }}" class="w-28 {{ $campo }}">
                <input name="aliquota_iva" type="number" step="0.01" min="0" max="100" value="{{ $a->aliquota_iva !== null ? (float) $a->aliquota_iva : '' }}" placeholder="IVA %" title="IVA %: vuoto = quella predefinita" class="w-24 {{ $campo }}">
                @php
                    $col = $collegamenti[$a->id] ?? null;
                    $valScarica = $col ? (string) $col->magazzino_articolo_id : ($a->senza_scorta ? 'no' : '');
                    $daDefinire = $valScarica === '' && ! in_array($a->categoria, ['antipasti', 'primi', 'secondi', 'dessert'], true);
                @endphp
                <select name="scarica" class="{{ $campo }}{{ $daDefinire ? ' border-[#B5563C] ring-1 ring-[#B5563C]' : '' }}"><option value="">— scegli —</option><option value="no" @selected($valScarica === 'no')>Non tracciare la scorta</option>@foreach ($magazzino as $m)<option value="{{ $m->id }}" @selected($valScarica === (string) $m->id)>{{ $m->nome }}</option>@endforeach</select>
                <input name="quantita_per_vendita" type="number" step="0.001" min="0.001" value="{{ $col ? (float) $col->quantita : 1 }}" class="w-20 {{ $campo }}" title="Quantità scaricata a ogni vendita" aria-label="Quantità a vendita">
                <label class="flex items-center gap-1.5 text-sm text-slate-600"><input type="checkbox" name="attivo" value="1" @checked($a->attivo) class="accent-[#C9822B]"> Attivo</label>
                <button class="bg-white border border-gray-200 hover:border-[#C9822B] text-slate-700 font-medium px-4 py-2 rounded-lg text-sm">Salva</button>
            </form>
            </div>
        @endforeach
    </div>

    {{-- ===== TAVOLI ===== --}}
    <h2 class="text-lg font-medium mb-3">Tavoli</h2>

    <form method="POST" action="{{ route('bistro.tavoli.crea') }}" class="bg-white rounded-2xl border border-gray-200 p-5 mb-5 flex flex-wrap items-end gap-3">
        @csrf
        <label><span class="block text-xs text-slate-500 mb-1">Zona</span>
            <select name="zona" class="{{ $campo }}">@foreach ($zone as $k => $n)<option value="{{ $k }}">{{ $n }}</option>@endforeach</select></label>
        <label><span class="block text-xs text-slate-500 mb-1">Numero</span>
            <input name="numero" type="number" min="1" max="999" required class="w-24 {{ $campo }}"></label>
        <button class="bg-[#C9822B] hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">Aggiungi tavolo</button>
    </form>

    <div class="space-y-4">
        @foreach ($zone as $k => $nomeZona)
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <p class="text-sm font-medium mb-3">{{ $nomeZona }}</p>
                <div class="flex flex-wrap gap-2">
                    @forelse (($tavoli[$k] ?? collect())->where('attivo', true) as $t)
                        <form method="POST" action="{{ route('bistro.tavoli.disattiva', $t->id) }}" onsubmit="return confirm('Disattivare il tavolo {{ $t->numero }}?')">@csrf
                            <button class="px-4 py-2 rounded-lg border border-gray-200 text-sm hover:border-[#B5563C] hover:text-[#B5563C]" title="Clicca per disattivare">{{ $t->numero }} &times;</button>
                        </form>
                    @empty
                        <span class="text-sm text-slate-400">Nessun tavolo.</span>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
