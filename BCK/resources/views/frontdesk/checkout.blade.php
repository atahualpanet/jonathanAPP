@php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.');
    $okMinibar = (bool) $minibarOggi;
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Check-out')
@section('page_title', 'Check-out · Camera ' . ($camera->numero ?? '—'))
@section('page_color', '#4F86B0')

@section('content')

<a href="{{ route('prenotazioni.scheda', $prenotazione) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Torna alla prenotazione</a>

@include('frontdesk.partials.messaggi')

<form method="POST" action="{{ route('prenotazioni.checkout.conferma', $prenotazione) }}">
    @csrf

    {{-- ===== SITUAZIONE ===== --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-medium">{{ $prenotazione->ospite->nome }} {{ $prenotazione->ospite->cognome }}</h2>
                <p class="text-sm text-slate-500">
                    Camera {{ $prenotazione->camere->pluck('numero')->implode(', ') }}
                    · arrivo {{ $prenotazione->data_arrivo->translatedFormat('D j M') }}
                    · partenza prevista {{ $prenotazione->data_partenza->translatedFormat('D j M') }}
                </p>
            </div>
            @if ($anticipata)
                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-warmGold text-white">Partenza anticipata</span>
            @elseif ($inRitardo)
                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-red-600 text-white">Oltre la partenza prevista</span>
            @else
                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-okGreen text-white">Partenza prevista oggi</span>
            @endif
        </div>
        @if ($inRitardo)
            <p class="text-sm text-red-700 mt-3">L'ospite è rimasto oltre la data di partenza: le notti in più non sono nel conto. Aggiungile dal folio prima di chiudere.</p>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ===== CONTO ===== --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-lg font-medium mb-4">Conto</h2>

            @if ($conto)
                <div class="space-y-1.5 text-[15px]">
                    @foreach ($riepilogo as $f)
                        <div class="flex justify-between"><span class="text-slate-500">Folio {{ $f->chiave }} · {{ $f->nome }}</span><span>€ {{ $eur($f->saldo) }}</span></div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-500">Il conto non è ancora aperto: il saldo si calcola dalle camere e dalla caparra.</p>
            @endif

            <div class="bg-[#EAF0F8] rounded-xl px-4 py-3 flex justify-between items-center text-lg font-medium mt-4">
                <span>Saldo da incassare</span><span>€ {{ $eur($saldo) }}</span>
            </div>

            <div class="flex flex-wrap gap-2 mt-4">
                <a href="{{ route('prenotazioni.folio', $prenotazione) }}" class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Apri il folio per incassare</a>
            </div>

            @if ($daFatturare->isNotEmpty())
                <p class="text-sm text-slate-500 mt-4">Fattura non ancora emessa per il folio {{ $daFatturare->implode(' e ') }}: puoi farlo dal folio, anche dopo la partenza.</p>
            @endif

            @if (abs($saldo) > 0.005)
                <label class="flex items-start gap-2 mt-5 text-sm text-slate-700">
                    <input type="checkbox" name="saldo_aperto" value="1" class="rounded mt-0.5" @checked(old('saldo_aperto'))>
                    <span>Lascia il conto aperto con saldo di € {{ $eur($saldo) }} e fai partire l'ospite comunque (da incassare dopo).</span>
                </label>
            @endif
        </div>

        {{-- ===== MINIBAR ===== --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-lg font-medium mb-4">Minibar</h2>

            @if ($okMinibar)
                <p class="text-[15px] text-okGreen font-medium">Controllato oggi alle {{ \Carbon\Carbon::parse($minibarOggi->created_at)->format('H:i') }}@if ($minibarOggi->controllato_da) da {{ $minibarOggi->controllato_da }}@endif
                    · {{ $minibarOggi->totale > 0 ? '€ ' . $eur($minibarOggi->totale) . ' addebitati' : 'completo, niente da addebitare' }}</p>
            @else
                <p class="text-[15px] text-warmGold font-medium">Non ancora controllato oggi.</p>
                <p class="text-sm text-slate-500 mt-2">Se la governante non fa in tempo (partenza urgente), puoi procedere e controllare dopo: finché il conto è aperto l'addebito entra comunque.</p>
                <div class="flex flex-wrap gap-2 mt-4">
                    @foreach ($prenotazione->camere as $c)
                        <a href="{{ route('camere.minibar', $c->id) }}" class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Controlla camera {{ $c->numero }}</a>
                    @endforeach
                </div>
                <label class="flex items-start gap-2 mt-5 text-sm text-slate-700">
                    <input type="checkbox" name="senza_minibar" value="1" class="rounded mt-0.5" @checked(old('senza_minibar'))>
                    <span>Procedi senza controllo minibar.</span>
                </label>
            @endif
        </div>
    </div>

    {{-- ===== PARTENZA ANTICIPATA ===== --}}
    @if ($anticipata)
        <div class="bg-white rounded-2xl border border-gray-200 p-6 mt-6">
            <h2 class="text-lg font-medium mb-1">Partenza anticipata</h2>
            <p class="text-sm text-slate-500 mb-5">L'ospite lascia la camera prima del {{ $prenotazione->data_partenza->translatedFormat('j F') }}. La camera risulta libera da oggi; scegli cosa fare delle notti non usate.</p>

            <div class="space-y-3 mb-5">
                <label class="flex items-start gap-3 text-[15px]">
                    <input type="radio" name="notti" value="tieni_tutte" class="mt-1" @checked(old('notti', 'tieni_tutte') === 'tieni_tutte')>
                    <span><span class="font-medium">Addebita tutte le notti prenotate</span>
                        <span class="block text-sm text-slate-500">Il conto resta com'è (€ {{ $eur($prenotato) }} di soggiorno e tassa): penale per partenza anticipata.</span></span>
                </label>
                <label class="flex items-start gap-3 text-[15px]">
                    <input type="radio" name="notti" value="solo_usate" class="mt-1" @checked(old('notti') === 'solo_usate')>
                    <span><span class="font-medium">Addebita solo le notti usate</span>
                        <span class="block text-sm text-slate-500">Il soggiorno si accorcia a {{ $ridotto['notti'] }} {{ $ridotto['notti'] === 1 ? 'notte' : 'notti' }}: € {{ $eur($ridotto['soggiorno'] + $ridotto['tassa']) }} invece di € {{ $eur($prenotato) }}
                            (− € {{ $eur($prenotato - $ridotto['soggiorno'] - $ridotto['tassa']) }}). Camera, tassa e prenotazione si aggiornano.</span></span>
                </label>
            </div>

            <label class="block text-sm font-medium mb-1.5" for="motivo">Motivo della partenza anticipata *</label>
            <input id="motivo" name="motivo" maxlength="255" value="{{ old('motivo') }}" placeholder="Es. emergenza familiare, problema in camera, cambio programma"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
        </div>
    @endif

    {{-- ===== CONFERMA ===== --}}
    <div class="flex flex-wrap items-center gap-3 mt-6">
        <button class="bg-fdBlue hover:opacity-90 text-white font-medium px-6 py-3 rounded-lg text-sm">Conferma check-out</button>
        <a href="{{ route('prenotazioni.scheda', $prenotazione) }}" class="bg-white border border-gray-200 hover:bg-gray-50 px-5 py-3 rounded-lg text-sm font-medium">Annulla</a>
        <p class="text-sm text-slate-500">La camera passa a "sporca" e l'ospite risulta partito.</p>
    </div>
</form>
@endsection
