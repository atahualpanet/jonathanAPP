@extends('layouts.shell')

@section('title', 'Jonathan PMS — Utenti e permessi')
@section('page_title', 'Utenti e permessi')
@section('page_color', '#7A3B4A')

@section('content')
@php
    $moduli = config('permessi.moduli');
    $n = $operatori->count();
    $colonne = 'grid-cols-[2.4fr_1.6fr_1.2fr_1fr]';
    $pillStato = [
        'attivo'      => ['Attivo',      'bg-okGreen text-white'],
        'disattivato' => ['Disattivato', 'bg-slate-200 text-slate-600'],
    ];
@endphp

<div class="w-full">

    @include('frontdesk.partials.messaggi')

    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-6">
            <h2 class="text-lg font-medium">{{ $n }} {{ $n === 1 ? 'operatore' : 'operatori' }}</h2>
            <a href="{{ route('permessi.index') }}" class="text-sm text-fdBlue hover:underline">Permessi per profilo &rarr;</a>
            <a href="{{ route('profili.index') }}" class="text-sm text-fdBlue hover:underline">Gestisci profili &rarr;</a>
        </div>
        <a href="{{ route('operatori.nuovo') }}" class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">+ Nuovo operatore</a>
    </div>

    <div class="grid {{ $colonne }} items-center bg-white rounded-xl border border-gray-100 px-5 h-12 text-sm text-slate-500 mb-3">
        <span>Operatore</span><span>Profilo</span><span>Moduli</span><span>Stato</span>
    </div>

    <div class="flex flex-col gap-3">
        @forelse ($operatori as $u)
            @php
                $profilo = $ruoli->get(\Illuminate\Support\Str::lower((string) $u->ruolo));
                $stato = $u->attivo ? 'attivo' : 'disattivato';
                [$etichetta, $classePill] = $pillStato[$stato];
            @endphp
            <div class="grid {{ $colonne }} items-center bg-white rounded-xl border border-gray-200 px-5 h-[62px]">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-9 h-9 shrink-0 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center text-sm font-medium">
                        {{ mb_strtoupper(mb_substr($u->nome, 0, 1)) }}
                    </span>
                    <a href="{{ route('operatori.modifica', $u->id) }}" title="Modifica" class="truncate text-[15px] font-medium hover:text-fdBlue hover:underline">{{ $u->nomeCompleto() }}</a>
                    <a href="{{ route('operatori.modifica', $u->id) }}" class="text-xs text-fdBlue hover:underline shrink-0">Modifica</a>
                </div>

                {{-- cambio profilo --}}
                <form method="POST" action="{{ route('operatori.ruolo', $u->id) }}">
                    @csrf
                    <select name="ruolo" onchange="this.form.submit()" aria-label="Profilo di {{ $u->nomeCompleto() }}"
                            class="w-full max-w-[200px] border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
                        @unless ($profilo)
                            <option value="" selected disabled>{{ $u->ruolo ?: '—' }} (non valido)</option>
                        @endunless
                        @foreach ($ruoli as $r)
                            <option value="{{ $r->chiave }}" @selected($profilo && $profilo->chiave === $r->chiave)>{{ $r->nome }}</option>
                        @endforeach
                    </select>
                </form>

                <div class="flex items-center gap-1.5">
                    @foreach ($profilo?->moduli ?? [] as $m)
                        @if (isset($moduli[$m]))
                            <span class="w-3.5 h-3.5 rounded-[4px] bg-[{{ $moduli[$m]['colore'] }}]" title="{{ $moduli[$m]['nome'] }}"></span>
                        @endif
                    @endforeach
                </div>

                <form method="POST" action="{{ route('operatori.attiva', $u->id) }}">
                    @csrf
                    <button @disabled($u->id === auth()->id()) title="{{ $u->id === auth()->id() ? 'Sei tu' : ($u->attivo ? 'Clicca per disattivare' : 'Clicca per riattivare') }}"
                            class="inline-flex items-center justify-center w-[102px] h-8 rounded-full text-sm font-medium {{ $classePill }} {{ $u->id === auth()->id() ? 'cursor-default' : 'hover:opacity-80' }}">{{ $etichetta }}</button>
                </form>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-6 text-sm text-slate-500">Nessun operatore.</div>
        @endforelse
    </div>

    <p class="mt-4 px-1 text-sm text-slate-400">I moduli attivi si vedono dai riquadri colorati. Il profilo si cambia dal menu; lo stato si cambia cliccando sul pulsante.</p>
</div>
@endsection
