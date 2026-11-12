@php
    app()->setLocale(session('locale', 'it'));
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $campo = 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slateBlue';
    $v = fn ($campo, $default = '') => old($campo, $a->$campo ?? $default);
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Articolo di magazzino')
@section('page_title', $a ? $a->nome : 'Nuovo articolo')
@section('page_color', '#4D6C86')

@section('content')
<div class="w-full max-w-4xl">
    @include('magazzino.partials.tabs')

    <form method="POST" action="{{ $a ? route('magazzino.articolo.salva', $a->id) : route('magazzino.articolo.crea') }}" class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <label class="sm:col-span-2"><span class="block text-xs text-slate-500 mb-1">Nome</span><input name="nome" required maxlength="100" value="{{ $v('nome') }}" class="{{ $campo }}"></label>
            <label><span class="block text-xs text-slate-500 mb-1">Unità di misura (pz, kg, l, cl...)</span><input name="unita_misura" required maxlength="10" value="{{ $v('unita_misura', 'pz') }}" class="{{ $campo }}"></label>
            <label><span class="block text-xs text-slate-500 mb-1">Prezzo d'acquisto € (per unità)</span><input name="prezzo_acquisto" type="number" step="0.01" min="0" value="{{ $v('prezzo_acquisto') }}" class="{{ $campo }}"></label>
            <label><span class="block text-xs text-slate-500 mb-1">Fornitore abituale</span>
                <select name="fornitore_id" class="{{ $campo }}"><option value="">—</option>@foreach ($fornitori as $f)<option value="{{ $f->id }}" @selected((string) $v('fornitore_id') === (string) $f->id)>{{ $f->nome }}</option>@endforeach</select></label>
            <label><span class="block text-xs text-slate-500 mb-1">Soglia di riordino</span><input name="soglia_riordino" type="number" step="0.001" min="0" value="{{ $v('soglia_riordino') }}" class="{{ $campo }}"></label>
            <label><span class="block text-xs text-slate-500 mb-1">Quantità standard per ordine</span><input name="quantita_standard_ordine" type="number" step="0.001" min="0" value="{{ $v('quantita_standard_ordine') }}" class="{{ $campo }}"></label>
            @if ($a)
                <label class="flex items-center gap-2 text-sm text-slate-600 self-end pb-2"><input type="checkbox" name="attivo" value="1" @checked($a->attivo)> Attivo</label>
            @endif
        </div>
        <p class="text-xs text-slate-400 mt-3">Quando la scorta scende alla soglia, l'articolo entra in una bozza d'ordine al fornitore abituale (servono soglia, fornitore e quantità standard). L'ordine parte solo dopo la tua approvazione.</p>
        <div class="mt-5 flex gap-3">
            <button class="bg-slateBlue hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">{{ $a ? 'Salva' : 'Crea articolo' }}</button>
            <a href="{{ route('magazzino.index') }}" class="px-5 py-2.5 text-sm text-slate-500">Annulla</a>
        </div>
    </form>

    @if ($a)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <form method="POST" action="{{ route('magazzino.movimento', $a->id) }}" class="bg-white rounded-2xl border border-gray-200 p-6 self-start">
                @csrf
                <p class="text-sm text-slate-500">Scorta attuale</p>
                <p class="text-3xl font-medium mb-4">{{ $n($a->scorta_attuale) }} <span class="text-base text-slate-400">{{ $a->unita_misura }}</span></p>
                <div class="flex flex-wrap items-end gap-3">
                    <label><span class="block text-xs text-slate-500 mb-1">Movimento</span>
                        <select name="tipo" class="border border-gray-200 rounded-lg px-3 py-2 text-sm"><option value="carico">Carico</option><option value="scarico">Scarico (rottura, inventario)</option></select></label>
                    <label><span class="block text-xs text-slate-500 mb-1">Quantità</span><input name="quantita" type="number" step="0.001" min="0.001" required class="w-28 border border-gray-200 rounded-lg px-3 py-2 text-sm"></label>
                </div>
                <label class="block mt-3"><span class="block text-xs text-slate-500 mb-1">Nota (facoltativa)</span><input name="nota" maxlength="255" class="{{ $campo }}"></label>
                <button class="mt-4 bg-white border border-gray-200 hover:border-slateBlue text-slate-700 font-medium px-5 py-2 rounded-lg text-sm">Registra</button>
            </form>

            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="text-base font-medium mb-3">Ultimi movimenti</h2>
                <ul class="divide-y divide-gray-100 text-sm">
                    @forelse ($movimenti as $m)
                        <li class="flex justify-between gap-3 py-2">
                            <span class="text-slate-600">{{ \Carbon\Carbon::parse($m->created_at)->format('d/m H:i') }} · {{ \App\Http\Controllers\MagazzinoController::SORGENTI[$m->sorgente] ?? $m->sorgente }}@if ($m->riferimento) <span class="text-slate-400">({{ $m->riferimento }})</span>@endif @if ($m->nota) · {{ $m->nota }}@endif</span>
                            <span class="{{ $m->tipo === 'carico' ? 'text-okGreen' : 'text-[#B5563C]' }} shrink-0">{{ $m->tipo === 'carico' ? '+' : '−' }}{{ $n($m->quantita) }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-slate-400">Nessun movimento.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif
</div>
@endsection
