@php
    app()->setLocale(session('locale', 'it'));

    $piuPersone    = ($persone->count() + count($slotMancanti)) > 1;
    $inCasa        = $prenotazione->stato === 'in_casa';
    $camereNumeri  = $prenotazione->camere->pluck('numero');
    $completi      = $persone->where('completo', true)->count();

    // Adulti che devono ancora accettare la privacy (registrati e da registrare): se sono più di uno, serve "spunta per tutti"
    $privacyDaDare = $persone->where('richiedePrivacy', true)->where('privacyOk', false)->count()
                   + collect($slotMancanti)->where('bambino', false)->count();
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Check-in')
@section('page_title', 'Check-in · ' . ($camereNumeri->implode(', ') ?: '—'))
@section('page_color', '#4F86B0')

@section('content')

<a href="{{ route('prenotazioni.scheda', $prenotazione) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">
    &larr; Torna alla prenotazione
</a>

{{-- Messaggi: l'esito dell'ultimo salvataggio e cosa impedisce di completare --}}
@if (session('ok'))
    <div class="mb-5 bg-okGreen/10 border border-okGreen/30 text-okGreen text-sm font-medium px-4 py-2.5 rounded-lg">
        {{ session('ok') }}
    </div>
@endif
@if ($errors->has('checkin'))
    <div class="mb-5 bg-[#B5563C]/10 border border-[#B5563C]/30 text-[#B5563C] text-sm font-medium px-4 py-2.5 rounded-lg space-y-1">
        @foreach ($errors->get('checkin') as $messaggio)
            <p>{{ $messaggio }}</p>
        @endforeach
    </div>
@elseif ($errors->any())
    <div class="mb-5 bg-[#B5563C]/10 border border-[#B5563C]/30 text-[#B5563C] text-sm font-medium px-4 py-2.5 rounded-lg">
        Alcuni campi non sono validi: controlla quelli segnalati qui sotto. Il file del documento va selezionato di nuovo.
    </div>
@endif

<form method="POST" action="{{ route('prenotazioni.checkin.completa', $prenotazione) }}" enctype="multipart/form-data"
      x-data="{ invio: false }" @submit="if (invio) { $event.preventDefault(); } else { invio = true; }">
    @csrf

    <div class="bg-white rounded-2xl border border-gray-200 p-6">

        {{-- Cosa manca, in una riga per persona --}}
        @unless ($inCasa)
            @if (count($daCompletare))
                <div class="mb-6 bg-warmGold/10 border border-warmGold/30 rounded-lg px-4 py-3 text-sm">
                    <p class="font-medium text-warmGold mb-1">
                        Da completare ({{ $completi }}/{{ $persone->count() }} {{ $persone->count() === 1 ? 'persona pronta' : 'persone pronte' }})
                    </p>
                    <ul class="text-slate-600 space-y-0.5">
                        @foreach ($daCompletare as $riga)
                            <li>{{ $riga }}</li>
                        @endforeach
                    </ul>
                    <p class="text-xs text-slate-400 mt-2">Compila i campi qui sotto e premi "Salva e completa check-in": l'elenco si aggiorna a ogni salvataggio.</p>
                </div>
            @else
                <div class="mb-6 bg-okGreen/10 border border-okGreen/30 rounded-lg px-4 py-3 text-sm text-okGreen font-medium">
                    Tutte le persone registrate sono complete: puoi completare il check-in.
                </div>
            @endif
        @endunless

        @if ($privacyDaDare > 1 && ! $inCasa)
            <div class="mb-4 text-right">
                <button type="button" class="text-xs font-medium text-fdBlue hover:underline"
                        @click="document.querySelectorAll('[data-privacy]').forEach(c => c.checked = true)">
                    Spunta la privacy per tutti gli adulti
                </button>
            </div>
        @endif

        {{-- ===== UNA SEZIONE PER OGNI PERSONA ===== --}}
        @foreach ($persone as $p)
            @php $o = $p->ospite; @endphp
            <section class="persona" id="ospite-{{ $o->id }}">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-medium">
                        {{ $o->nome }} {{ $o->cognome }}
                        @if ($piuPersone)<span class="persona__tag ml-1">{{ $p->ruolo }}</span>@endif
                    </h2>
                    <div class="flex items-center gap-3">
                        <span @class(['text-xs font-medium px-2.5 py-1 rounded-full', 'bg-okGreen/15 text-okGreen' => $p->completo, 'bg-warmGold/15 text-warmGold' => ! $p->completo])>
                            {{ $p->completo ? 'Completo' : 'Da completare' }}
                        </span>
                        @unless ($p->titolare)
                            <button type="submit" form="rimuovi-{{ $o->id }}" class="text-xs text-slate-400 hover:text-[#B5563C]"
                                    data-msg="Togliere {{ $o->nome }} {{ $o->cognome }} dalla prenotazione?"
                                    onclick="return confirm(this.dataset.msg)">Rimuovi</button>
                        @endunless
                    </div>
                </div>

                @include('frontdesk.partials.checkin-persona', [
                    'prefisso' => 'ospiti[' . $o->id . ']',
                    'chiave'   => 'ospiti.' . $o->id,
                    'o'        => $o,
                    'bambino'  => $p->bambino,
                    'nuovo'    => false,
                ])
            </section>
        @endforeach

        {{-- ===== PERSONE ANCORA DA REGISTRARE: stessi campi, si salvano nello stesso invio ===== --}}
        @foreach ($slotMancanti as $i => $slot)
            <section class="persona">
                <h2 class="font-medium mb-3">
                    Ospite {{ $slot->numero }}
                    <span class="persona__tag ml-1">{{ $slot->bambino ? 'Bambino' : 'Adulto' }} · da registrare</span>
                </h2>
                <input type="hidden" name="nuovi[{{ $i }}][bambino]" value="{{ $slot->bambino ? 1 : 0 }}">

                @include('frontdesk.partials.checkin-persona', [
                    'prefisso' => 'nuovi[' . $i . ']',
                    'chiave'   => 'nuovi.' . $i,
                    'o'        => null,
                    'bambino'  => $slot->bambino,
                    'nuovo'    => true,
                ])
            </section>
        @endforeach

        @if (count($slotMancanti))
            <p class="text-xs text-warmGold mt-4">
                Mancano {{ count($slotMancanti) }} {{ count($slotMancanti) === 1 ? 'persona' : 'persone' }} da registrare: se lasci vuoti i campi il check-in si può completare lo stesso, ma Alloggiati Web resta bloccato finché non ci sono tutte.
            </p>
        @endif

        {{-- ===== AVVISI DA CONFERMARE (check-in anticipato, camera non pronta, opzione) ===== --}}
        @if (count($avvisi) && ! $inCasa)
            <div class="mt-6 bg-warmGold/10 border border-warmGold/30 rounded-lg px-4 py-3 text-sm">
                <ul class="text-slate-700 space-y-0.5 mb-2">
                    @foreach ($avvisi as $avviso)
                        <li>{{ $avviso }}</li>
                    @endforeach
                </ul>
                <label class="flex items-center gap-2 font-medium cursor-pointer">
                    <input type="checkbox" name="procedi_comunque" value="1" @checked(old('procedi_comunque')) class="w-4 h-4 rounded border-gray-300">
                    Procedo comunque
                </label>
            </div>
        @endif

        {{-- ===== PULSANTI ===== --}}
        <div class="mt-6 flex flex-wrap items-center gap-3">
            @if ($inCasa)
                <p class="text-sm text-okGreen font-medium mr-auto">Check-in già completato il {{ $prenotazione->entrata_il?->format('d/m/Y H:i') }}.</p>
                <button type="submit" name="azione" value="salva" :class="invio && 'opacity-50'"
                        class="bg-fdBlue hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">
                    Salva modifiche
                </button>
            @else
                {{-- "Salva" è il primo pulsante: è quello che parte premendo Invio in un campo --}}
                <button type="submit" name="azione" value="salva" :class="invio && 'opacity-50'"
                        class="bg-white border border-gray-200 hover:bg-gray-50 font-medium px-5 py-2.5 rounded-lg text-sm">
                    Salva
                </button>
                <button type="submit" name="azione" value="completa" :class="invio && 'opacity-50'"
                        class="bg-fdBlue hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">
                    Salva e completa check-in
                </button>
                <span class="text-xs text-slate-400">Con il check-in si apre anche il conto.</span>
            @endif
        </div>
    </div>
</form>

{{-- Form di "Rimuovi": stanno fuori dal form principale (in HTML i form non si annidano) e i pulsanti li richiamano con form="..." --}}
@foreach ($persone as $p)
    @unless ($p->titolare)
        <form id="rimuovi-{{ $p->ospite->id }}" method="POST" action="{{ route('prenotazioni.ospiti.rimuovi', [$prenotazione, $p->ospite]) }}">
            @csrf
            <input type="hidden" name="torna" value="checkin">
        </form>
    @endunless
@endforeach

@push('scripts')
    <script src="{{ asset('js/dropzone.js') }}"></script>
@endpush
@endsection
