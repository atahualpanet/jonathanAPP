@php
    app()->setLocale(session('locale', 'it'));
    $camPrincipale = $camere->first();
    $statoLabel = [
        'in_attesa'  => ['Opzione', 'bg-warmGold text-white'],
        'confermata' => ['In arrivo', 'bg-fdBlue text-white'],
        'in_casa'    => ['In casa', 'bg-okGreen text-white'],
        'partita'    => ['Partita', 'bg-slate-300 text-slate-700'],
        'annullata'  => ['Annullata', 'bg-slate-400 text-white'],
        'no_show'    => ['No show', 'bg-[#B5563C] text-white'],
    ][$prenotazione->stato] ?? ['—', 'bg-slate-300 text-white'];
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Prenotazione #' . $prenotazione->id)
@section('page_title', 'Prenotazione #' . $prenotazione->id)
@section('page_color', '#4F86B0')

@section('content')

<a href="{{ route('frontdesk.prenotazioni') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">
    &larr; Torna al FrontDesk
</a>

@include('frontdesk.partials.messaggi')

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- ===== SCHEDA PRENOTAZIONE ===== --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-5">
            <h2 class="text-lg font-medium">{{ $prenotazione->ospite->nome }} {{ $prenotazione->ospite->cognome }}</h2>
            <span class="text-xs font-medium px-2.5 py-1 rounded-full {{ $statoLabel[1] }}">{{ $statoLabel[0] }}</span>
        </div>
        @if ($camPrincipale && $camPrincipale->camera->tipologia)
            <p class="text-sm text-slate-500 -mt-3 mb-5">{{ $prenotazione->adulti }} {{ $prenotazione->adulti === 1 ? 'adulto' : 'adulti' }} · {{ $camPrincipale->camera->tipologia->nome }}</p>
        @endif

        <dl class="space-y-3 text-[15px]">
            <div class="flex justify-between"><dt class="text-slate-500">Ospiti</dt>
                <dd>{{ $prenotazione->adulti }} adulti @if($prenotazione->bambini) · {{ $prenotazione->bambini }} bambini @endif</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Arrivo</dt><dd>{{ $prenotazione->data_arrivo->translatedFormat('D j M') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Partenza</dt><dd>{{ $prenotazione->data_partenza->translatedFormat('D j M') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Camer{{ $camere->count() > 1 ? 'e' : 'a' }}</dt>
                <dd>{{ $camere->pluck('camera.numero')->implode(', ') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Notti</dt><dd>{{ (int) $prenotazione->data_arrivo->diffInDays($prenotazione->data_partenza) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Canale</dt><dd class="capitalize">{{ str_replace('_', ' ', $prenotazione->canale) }}</dd></div>
            @if ($prenotazione->note)
                <div class="flex justify-between gap-6"><dt class="text-slate-500">Note</dt><dd class="text-right">{{ $prenotazione->note }}</dd></div>
            @endif
        </dl>

        @if ($prenotazione->uscita_motivo)
            <p class="text-sm text-warmGold mt-4">
                {{ ['annullata' => 'Motivo dell\'annullamento', 'no_show' => 'Nota sul no-show'][$prenotazione->stato] ?? 'Partenza anticipata' }}: {{ $prenotazione->uscita_motivo }}
            </p>
        @endif

        @if ($prenotazione->caparra_gestita_il)
            <p class="text-sm text-slate-600 mt-2">
                Caparra di € {{ number_format((float) $prenotazione->importo_caparra, 2, ',', '.') }}:
                @if ((float) $prenotazione->caparra_trattenuta <= 0)
                    da rimborsare all'ospite
                @elseif ((float) $prenotazione->caparra_rimborsata <= 0)
                    trattenuta
                @else
                    € {{ number_format((float) $prenotazione->caparra_trattenuta, 2, ',', '.') }} trattenuti,
                    € {{ number_format((float) $prenotazione->caparra_rimborsata, 2, ',', '.') }} da rimborsare
                @endif
                <span class="text-slate-400">(decisione del {{ $prenotazione->caparra_gestita_il->format('d/m/Y H:i') }})</span>
            </p>
        @endif

        <div class="flex flex-wrap gap-2 mt-6">
            @unless (in_array($prenotazione->stato, ['annullata', 'no_show'], true))
                <a href="{{ route('prenotazioni.checkin', $prenotazione) }}"
                   class="bg-fdBlue hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">
                    {{ $prenotazione->stato === 'in_casa' ? 'Check-in effettuato' : 'Check-in' }}
                </a>
            @endunless
            @if ($prenotazione->stato === 'in_casa')
                <a href="{{ route('prenotazioni.checkout', $prenotazione) }}"
                   class="bg-deepNavy hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Check-out</a>
            @elseif ($prenotazione->stato === 'partita')
                <span class="bg-slate-200 text-slate-600 font-medium px-4 py-2 rounded-lg text-sm">Check-out effettuato</span>
            @endif
            <a href="{{ route('prenotazioni.folio', $prenotazione) }}"
               class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Folio e conto</a>
            <a href="{{ route('prenotazioni.adempimenti', $prenotazione) }}"
               class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Adempimenti</a>
            <a href="{{ route('prenotazioni.modifica', $prenotazione) }}"
               class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Modifica prenotazione</a>
        </div>
    </div>

    {{-- ===== RIEPILOGO IMPORTI ===== --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 class="text-lg font-medium mb-5">Riepilogo importi</h2>

        <dl class="space-y-3 text-[15px]">
            @foreach ($camere as $c)
                <div class="flex justify-between">
                    <dt class="text-slate-500">Camera {{ $c->camera->numero }} · {{ $c->notti }} {{ $c->notti === 1 ? 'notte' : 'notti' }} × € {{ number_format($c->tariffa, 2, ',', '.') }}</dt>
                    <dd>€ {{ number_format($c->subtotale, 2, ',', '.') }}</dd>
                </div>
            @endforeach

            @if ($tassa > 0)
                <div class="flex justify-between">
                    <dt class="text-slate-500">Tassa di soggiorno</dt>
                    <dd>€ {{ number_format($tassa, 2, ',', '.') }}</dd>
                </div>
            @endif
            @if ($extra > 0)
                <div class="flex justify-between">
                    <dt class="text-slate-500">Extra e servizi</dt>
                    <dd>€ {{ number_format($extra, 2, ',', '.') }}</dd>
                </div>
            @endif

            <div class="border-t border-gray-100 pt-3 flex justify-between font-medium">
                <dt>Totale</dt><dd>€ {{ number_format($totale, 2, ',', '.') }}</dd>
            </div>
            @if ($caparra > 0)
                <div class="flex justify-between text-slate-500">
                    <dt>Caparra versata</dt><dd class="text-okGreen">- € {{ number_format($caparra, 2, ',', '.') }}</dd>
                </div>
            @endif
            @if ($pagato > 0)
                <div class="flex justify-between text-slate-500">
                    <dt>Pagamenti ricevuti</dt><dd class="text-okGreen">- € {{ number_format($pagato, 2, ',', '.') }}</dd>
                </div>
            @endif

            <div class="bg-[#EAF0F8] rounded-xl px-4 py-3 flex justify-between items-center text-lg font-medium">
                <dt>Saldo</dt><dd class="text-xl">€ {{ number_format($saldo, 2, ',', '.') }}</dd>
            </div>
        </dl>

        <p class="text-xs text-slate-400 mt-5">
            @if ($conto)
                Conto {{ $conto->codice }} · {{ $conto->stato }}.
            @else
                Il conto non è ancora aperto: si apre da solo al check-in e da lì compaiono anche gli extra (bistro, minibar...).
            @endif
        </p>
    </div>


    {{-- ===== OSPITI DELLA PRENOTAZIONE ===== --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium">Ospiti <span class="text-sm text-slate-400 font-normal">{{ $persone->count() }} di {{ $prenotazione->personePreviste() }} registrati</span></h2>
            <a href="{{ route('prenotazioni.checkin', $prenotazione) }}" class="text-sm font-medium text-fdBlue hover:underline">Documenti e check-in</a>
        </div>

        @foreach ($persone as $p)
            <div class="flex flex-wrap items-center justify-between gap-2 py-2.5 border-b border-gray-100 last:border-b-0">
                <div class="flex items-center gap-2">
                    <span>{{ $p->ospite->nome }} {{ $p->ospite->cognome }}</span>
                    <span class="persona__tag">{{ $p->ruolo }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span @class([
                        'text-xs font-medium px-2.5 py-1 rounded-full',
                        'bg-okGreen/15 text-okGreen' => $p->documentoOk,
                        'bg-warmGold/15 text-warmGold' => ! $p->documentoOk && $p->richiedeDoc,
                        'bg-slate-100 text-slate-400' => ! $p->documentoOk && ! $p->richiedeDoc,
                    ])>{{ $p->documentoOk ? 'Documento' : ($p->richiedeDoc ? 'Documento mancante' : 'Senza documento') }}</span>
                    <span @class(['text-xs font-medium px-2.5 py-1 rounded-full', 'bg-okGreen/15 text-okGreen' => $p->datiOk, 'bg-warmGold/15 text-warmGold' => ! $p->datiOk])>{{ $p->datiOk ? 'Dati completi' : 'Dati incompleti' }}</span>
                    <a href="{{ route('prenotazioni.ospiti.modifica', [$prenotazione, $p->ospite]) }}" class="text-xs font-medium text-fdBlue hover:underline">Modifica</a>
                </div>
            </div>
        @endforeach

        @if ($prenotazione->postiMancanti() > 0)
            <div class="flex items-center justify-between py-2.5 text-sm">
                <span class="text-warmGold">{{ $prenotazione->postiMancanti() }} {{ $prenotazione->postiMancanti() === 1 ? 'persona' : 'persone' }} ancora da registrare</span>
                <a href="{{ route('prenotazioni.checkin', $prenotazione) }}" class="text-xs font-medium text-fdBlue hover:underline">Registra</a>
            </div>
        @endif
    </div>

</div>
@endsection
