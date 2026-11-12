@php
    app()->setLocale(session('locale', 'it'));
    $torna = request()->query('torna', 'scheda');
    $tornaARotta = $torna === 'checkin' ? route('prenotazioni.checkin', $prenotazione) : route('prenotazioni.scheda', $prenotazione);
    $campo = 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm';
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Dati ospite')
@section('page_title', 'Dati ospite · ' . $o->nome . ' ' . $o->cognome)
@section('page_color', '#4F86B0')

@section('content')

<a href="{{ $tornaARotta }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">
    &larr; {{ $torna === 'checkin' ? 'Torna al check-in' : 'Torna alla prenotazione' }}
</a>

@include('frontdesk.partials.messaggi')

<form method="POST" action="{{ route('prenotazioni.ospiti.salva', [$prenotazione, $o]) }}"
      class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
    @csrf
    <input type="hidden" name="torna" value="{{ $torna }}">

    <div class="flex items-center gap-2">
        <h2 class="text-lg font-medium">Dati anagrafici</h2>
        @if ($persona)<span class="persona__tag">{{ $persona->ruolo }}</span>@endif
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-slate-500 mb-1">Nome</label>
            <input name="nome" value="{{ old('nome', $o->nome) }}" required class="{{ $campo }}">
        </div>
        <div>
            <label class="block text-sm text-slate-500 mb-1">Cognome</label>
            <input name="cognome" value="{{ old('cognome', $o->cognome) }}" required class="{{ $campo }}">
        </div>
        <div>
            <label class="block text-sm text-slate-500 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $o->email) }}" class="{{ $campo }}">
        </div>
        <div>
            <label class="block text-sm text-slate-500 mb-1">Telefono</label>
            <input name="telefono" value="{{ old('telefono', $o->telefono) }}" class="{{ $campo }}">
        </div>
        <div>
            <label class="block text-sm text-slate-500 mb-1">Data di nascita</label>
            <input type="date" name="data_nascita" value="{{ old('data_nascita', $o->data_nascita?->format('Y-m-d')) }}" class="{{ $campo }}">
        </div>
        <div>
            <label class="block text-sm text-slate-500 mb-1">Nazionalità (codice a 2 lettere, es. IT)</label>
            <input name="nazionalita" maxlength="2" value="{{ old('nazionalita', $o->nazionalita) }}" class="{{ $campo }} uppercase">
        </div>
    </div>

    <div>
        <label class="block text-sm text-slate-500 mb-1">Indirizzo</label>
        <input name="indirizzo" value="{{ old('indirizzo', $o->indirizzo) }}" class="{{ $campo }}">
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-sm text-slate-500 mb-1">Città</label>
            <input name="citta" value="{{ old('citta', $o->citta) }}" class="{{ $campo }}">
        </div>
        <div>
            <label class="block text-sm text-slate-500 mb-1">CAP</label>
            <input name="cap" value="{{ old('cap', $o->cap) }}" class="{{ $campo }}">
        </div>
        <div>
            <label class="block text-sm text-slate-500 mb-1">Paese (codice a 2 lettere)</label>
            <input name="paese" maxlength="2" value="{{ old('paese', $o->paese) }}" class="{{ $campo }} uppercase">
        </div>
    </div>

    <div>
        <label class="block text-sm text-slate-500 mb-1">Note</label>
        <textarea name="note" rows="3" class="{{ $campo }}">{{ old('note', $o->note) }}</textarea>
    </div>

    <div class="flex gap-2 pt-2">
        <button class="bg-fdBlue hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">Salva dati ospite</button>
        <a href="{{ $tornaARotta }}" class="bg-white border border-gray-200 hover:bg-gray-50 px-5 py-2.5 rounded-lg text-sm font-medium">Annulla</a>
    </div>
</form>

{{-- ===== DOCUMENTO DELLA STESSA PERSONA ===== --}}
<div class="bg-white rounded-2xl border border-gray-200 p-6 mt-6">
    <h2 class="text-lg font-medium mb-4">Documento d'identità</h2>
    @include('frontdesk.partials.documento-form', ['prenotazione' => $prenotazione, 'ospite' => $o, 'torna' => $torna, 'annullabile' => false])
</div>
@endsection
