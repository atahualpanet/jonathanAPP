@php
    app()->setLocale(session('locale', 'it'));
    $campo = 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm';
    $etichetta = 'block text-sm text-slate-500 mb-1';

    $stati = ['confermata' => 'Confermata', 'in_attesa' => 'Opzione', 'in_casa' => 'In casa', 'partita' => 'Partita', 'annullata' => 'Annullata', 'no_show' => 'No-show'];
    $modificabile = $prenotazione->modificabile();
    $inCasa = $prenotazione->stato === 'in_casa';
    $primaDellArrivo = in_array($prenotazione->stato, ['confermata', 'in_attesa'], true);

    // Le camere scelte nel menu: tutte quelle attive, più quelle già assegnate (anche se ora non fossero più attive)
    $opzioniCamere = $camere->concat($prenotazione->camere->reject(fn ($c) => $camere->contains('id', $c->id)))->sortBy('numero');

    $adultiReg  = $prenotazione->adultiRegistrati();
    $bambiniReg = $prenotazione->bambiniRegistrati();
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Modifica prenotazione')
@section('page_title', 'Modifica prenotazione #' . $prenotazione->id)
@section('page_color', '#4F86B0')

@section('content')

<a href="{{ route('prenotazioni.scheda', $prenotazione) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">
    &larr; Torna alla prenotazione
</a>

@include('frontdesk.partials.messaggi')

<div class="bg-white rounded-2xl border border-gray-200 p-6 mb-5">
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-lg font-medium">{{ $prenotazione->ospite->nome }} {{ $prenotazione->ospite->cognome }} · {{ $prenotazione->codice }}</h2>
        <span @class([
            'text-xs font-medium px-2.5 py-1 rounded-full',
            'bg-okGreen/15 text-okGreen' => in_array($prenotazione->stato, ['confermata', 'in_casa'], true),
            'bg-warmGold/15 text-warmGold' => $prenotazione->stato === 'in_attesa',
            'bg-slate-100 text-slate-500' => in_array($prenotazione->stato, ['partita', 'annullata', 'no_show'], true),
        ])>{{ $stati[$prenotazione->stato] ?? $prenotazione->stato }}</span>
    </div>
    @unless ($modificabile)
        <p class="text-sm text-slate-500 mt-3">Questa prenotazione è {{ mb_strtolower($stati[$prenotazione->stato] ?? $prenotazione->stato) }}: non si può più modificare.</p>
    @endunless
</div>

@if ($modificabile)
<form method="POST" action="{{ route('prenotazioni.modifica.salva', $prenotazione) }}"
      x-data="{ invio: false }" @submit="if (invio) { $event.preventDefault(); } else { invio = true; }"
      class="space-y-5">
    @csrf

    {{-- ===== SOGGIORNO: camera, date e tariffa di ogni assegnazione ===== --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h3 class="font-medium mb-1">Soggiorno</h3>
        <p class="text-xs text-slate-400 mb-4">
            Cambia camera, date e tariffa a notte. Le regole sono quelle del tableau: niente sovrapposizioni, niente camere in lavori.
            @if ($inCasa) Ospite in casa: l'arrivo non si può cambiare. @endif
        </p>

        <div class="space-y-4">
            @foreach ($prenotazione->camere as $c)
                @php
                    $id = $c->id;
                    $notti = (int) \Carbon\Carbon::parse($c->pivot->data_arrivo)->diffInDays($c->pivot->data_partenza);
                @endphp
                <div class="border border-gray-100 rounded-xl p-4">
                    <p class="text-xs text-slate-400 mb-3">Camera {{ $c->numero }} · {{ $notti }} {{ $notti === 1 ? 'notte' : 'notti' }} attualmente</p>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="col-span-2 lg:col-span-1">
                            <label class="{{ $etichetta }}">Camera</label>
                            <select name="camere[{{ $id }}][camera_id]" class="{{ $campo }} bg-white">
                                @foreach ($opzioniCamere as $cam)
                                    <option value="{{ $cam->id }}" @selected((int) old("camere.$id.camera_id", $c->id) === (int) $cam->id)>
                                        {{ $cam->numero }}{{ $cam->tipologia ? ' · ' . $cam->tipologia->nome : '' }}{{ $cam->stato_pulizia === 'manutenzione' ? ' (in lavori)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($errors->first("camere.$id.camera_id"))<p class="text-xs text-[#B5563C] mt-1">{{ $errors->first("camere.$id.camera_id") }}</p>@endif
                        </div>
                        <div>
                            <label class="{{ $etichetta }}">Arrivo</label>
                            <input type="date" name="camere[{{ $id }}][data_arrivo]" required
                                   value="{{ old("camere.$id.data_arrivo", substr((string) $c->pivot->data_arrivo, 0, 10)) }}"
                                   @readonly($inCasa) class="{{ $campo }} {{ $inCasa ? 'bg-slate-50 text-slate-500' : '' }}">
                            @if ($errors->first("camere.$id.data_arrivo"))<p class="text-xs text-[#B5563C] mt-1">{{ $errors->first("camere.$id.data_arrivo") }}</p>@endif
                        </div>
                        <div>
                            <label class="{{ $etichetta }}">Partenza</label>
                            <input type="date" name="camere[{{ $id }}][data_partenza]" required
                                   value="{{ old("camere.$id.data_partenza", substr((string) $c->pivot->data_partenza, 0, 10)) }}" class="{{ $campo }}">
                            @if ($errors->first("camere.$id.data_partenza"))<p class="text-xs text-[#B5563C] mt-1">{{ $errors->first("camere.$id.data_partenza") }}</p>@endif
                        </div>
                        <div>
                            <label class="{{ $etichetta }}">Tariffa a notte (€)</label>
                            <input type="number" step="0.01" min="0" name="camere[{{ $id }}][tariffa]" required
                                   value="{{ old("camere.$id.tariffa", $c->pivot->tariffa) }}" class="{{ $campo }}">
                            @if ($errors->first("camere.$id.tariffa"))<p class="text-xs text-[#B5563C] mt-1">{{ $errors->first("camere.$id.tariffa") }}</p>@endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ===== PERSONE ===== --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h3 class="font-medium mb-4">Persone</h3>

        <div class="grid grid-cols-2 gap-4 max-w-md">
            <div>
                <label class="{{ $etichetta }}">Adulti</label>
                <input type="number" name="adulti" min="{{ max(1, $adultiReg) }}" max="10" value="{{ old('adulti', $prenotazione->adulti) }}" required class="{{ $campo }}">
            </div>
            <div>
                <label class="{{ $etichetta }}">Bambini</label>
                <input type="number" name="bambini" min="{{ $bambiniReg }}" max="10" value="{{ old('bambini', $prenotazione->bambini) }}" class="{{ $campo }}">
            </div>
        </div>
        <p class="text-xs text-slate-400 mt-2">Non si può scendere sotto le persone già registrate ({{ $adultiReg }} {{ $adultiReg === 1 ? 'adulto' : 'adulti' }}, {{ $bambiniReg }} {{ $bambiniReg === 1 ? 'bambino' : 'bambini' }}). Cambiando gli adulti si ricalcola la tassa di soggiorno.</p>

        <div class="mt-4 divide-y divide-gray-100 border border-gray-100 rounded-xl">
            @foreach ($persone as $p)
                <div class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                    <span>{{ $p->ospite->nome }} {{ $p->ospite->cognome }} <span class="persona__tag ml-1">{{ $p->ruolo }}</span></span>
                    <span class="flex items-center gap-3">
                        <span @class(['text-xs font-medium px-2.5 py-1 rounded-full', 'bg-okGreen/15 text-okGreen' => $p->completo, 'bg-warmGold/15 text-warmGold' => ! $p->completo])>
                            {{ $p->completo ? 'Dati e documento a posto' : 'Da completare' }}
                        </span>
                        <a href="{{ route('prenotazioni.checkin', $prenotazione) }}#ospite-{{ $p->ospite->id }}" class="text-xs font-medium text-fdBlue hover:underline">Dati e documento</a>
                    </span>
                </div>
            @endforeach
        </div>
        @if ($prenotazione->postiMancanti() > 0)
            <p class="text-xs text-warmGold mt-2">
                Mancano {{ $prenotazione->postiMancanti() }} {{ $prenotazione->postiMancanti() === 1 ? 'persona' : 'persone' }} da registrare: si inseriscono dalla pagina di check-in.
            </p>
        @endif
    </div>

    {{-- ===== ECONOMICO, CANALE, NOTE ===== --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h3 class="font-medium mb-4">Importi e note</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="{{ $etichetta }}">Caparra (€)</label>
                <input type="number" step="0.01" min="0" name="caparra" value="{{ old('caparra', $prenotazione->importo_caparra) }}" class="{{ $campo }}">
            </div>
            <div>
                <label class="{{ $etichetta }}">Canale</label>
                <select name="canale" class="{{ $campo }} bg-white">
                    @foreach (['diretto' => 'Diretto', 'telefono' => 'Telefono', 'walk_in' => 'Walk-in', 'ota' => 'OTA', 'altro' => 'Altro'] as $valore => $nomeCanale)
                        <option value="{{ $valore }}" @selected(old('canale', $prenotazione->canale) === $valore)>{{ $nomeCanale }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="{{ $etichetta }}">Note</label>
                <textarea name="note" rows="3" class="{{ $campo }}">{{ old('note', $prenotazione->note) }}</textarea>
            </div>
        </div>

        <p class="text-xs text-slate-400 mt-3">
            Totale attuale della prenotazione: € {{ number_format((float) $prenotazione->importo_totale, 2, ',', '.') }} (soggiorno + tassa di soggiorno): si ricalcola al salvataggio.
            @if ($conto)
                Il conto è {{ $conto->stato === 'aperto' ? 'aperto: camere, tassa e caparra del conto si aggiornano da sole (un folio con fattura emessa non si tocca)' : 'chiuso: i suoi importi non cambiano' }}.
            @endif
        </p>
    </div>

    <div class="flex gap-2">
        <button type="submit" :class="invio && 'opacity-50'" class="bg-fdBlue hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">Salva modifiche</button>
        <a href="{{ route('prenotazioni.scheda', $prenotazione) }}" class="bg-white border border-gray-200 hover:bg-gray-50 px-5 py-2.5 rounded-lg text-sm font-medium">Annulla</a>
    </div>
</form>

{{-- ===== CHIUDERE LA PRENOTAZIONE (fuori dal form di modifica: i form non si annidano) ===== --}}
<div class="bg-white rounded-2xl border border-gray-200 p-6 mt-5">
    <h3 class="font-medium mb-1">Chiudi la prenotazione</h3>

    @if ($primaDellArrivo)
        <p class="text-xs text-slate-400 mb-4">
            La camera torna libera nel tableau.
            @if ((float) $prenotazione->importo_caparra > 0)
                Dovrai scegliere cosa fare della caparra di € {{ number_format((float) $prenotazione->importo_caparra, 2, ',', '.') }}: rimborsarla, trattenerla o dividerla.
            @endif
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <form method="POST" action="{{ route('prenotazioni.annulla', $prenotazione) }}" class="space-y-2"
                  data-msg="Annullare la prenotazione di {{ $prenotazione->ospite->nome }} {{ $prenotazione->ospite->cognome }}?"
                  onsubmit="return confirm(this.dataset.msg)">
                @csrf
                <label class="{{ $etichetta }}">Motivo dell'annullamento</label>
                <input name="motivo" required maxlength="255" placeholder="Es. cancellata dal cliente" class="{{ $campo }}">
                @include('frontdesk.partials.caparra-chiusura', ['prenotazione' => $prenotazione, 'prefisso' => 'annulla', 'campo' => $campo])
                <button class="bg-white border border-[#B5563C]/40 text-[#B5563C] hover:bg-[#B5563C]/10 font-medium px-5 py-2 rounded-lg text-sm">Annulla prenotazione</button>
            </form>

            @if ($prenotazione->data_arrivo->lte(today()))
                <form method="POST" action="{{ route('prenotazioni.noshow', $prenotazione) }}" class="space-y-2"
                      data-msg="Segnare il no-show di {{ $prenotazione->ospite->nome }} {{ $prenotazione->ospite->cognome }}?"
                      onsubmit="return confirm(this.dataset.msg)">
                    @csrf
                    <label class="{{ $etichetta }}">Nota sul no-show (facoltativa)</label>
                    <input name="motivo" maxlength="255" placeholder="Es. nessuna risposta al telefono" class="{{ $campo }}">
                    @include('frontdesk.partials.caparra-chiusura', ['prenotazione' => $prenotazione, 'prefisso' => 'noshow', 'campo' => $campo])
                    <button class="bg-white border border-gray-200 hover:bg-gray-50 font-medium px-5 py-2 rounded-lg text-sm">Segna no-show</button>
                </form>
            @else
                <p class="text-xs text-slate-400 self-end">Il no-show si può segnare dal giorno di arrivo ({{ $prenotazione->data_arrivo->format('d/m/Y') }}) in poi.</p>
            @endif
        </div>
    @else
        <p class="text-sm text-slate-500">
            L'ospite è in casa: per chiudere il soggiorno fai il
            <a href="{{ route('prenotazioni.checkout', $prenotazione) }}" class="text-fdBlue hover:underline">check-out</a>.
        </p>
    @endif
</div>
@endif
@endsection
