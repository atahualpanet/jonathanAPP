@php
    app()->setLocale(session('locale', 'it'));

    $eur = fn ($n) => number_format((float) $n, 2, ',', '.');
    $attivi = $articoli->where('attivo', true);
    $nDisattivati = $articoli->count() - $attivi->count();

    // Stessa logica di "Utenti e permessi": riga di intestazione + righe-card sulla stessa griglia
    $colonne = 'grid-cols-[minmax(200px,2.6fr)_1.3fr_110px_90px_1.5fr_70px_64px]';
    $campo = 'border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-fdBlue';
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Minibar')
@section('page_title', 'Minibar')
@section('page_color', '#7A3B4A')

@section('content')

<div class="w-full"
     x-data="{ tab: sessionStorage.getItem('minibar-tab') || 'listino', nuovo: @js($errors->any()) }"
     x-init="$watch('tab', v => sessionStorage.setItem('minibar-tab', v))">

    @include('frontdesk.partials.messaggi')

    {{-- ===== TAB ===== --}}
    <div class="border-b border-gray-200 mb-6 flex overflow-x-auto whitespace-nowrap">
        <button type="button" @click="tab = 'listino'"
                :class="tab === 'listino' ? 'border-fdBlue text-fdBlue' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300'"
                class="py-3 px-6 border-b-2 font-medium text-sm transition-colors focus:outline-none">
            Listino articoli
        </button>
        <button type="button" @click="tab = 'dotazioni'"
                :class="tab === 'dotazioni' ? 'border-fdBlue text-fdBlue' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300'"
                class="py-3 px-6 border-b-2 font-medium text-sm transition-colors focus:outline-none">
            Dotazione per tipologia
        </button>
    </div>

    {{-- ==========================================
         TAB 1: LISTINO
         ========================================== --}}
    <div x-show="tab === 'listino'" x-transition.opacity>

        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-4">
                <h2 class="text-lg font-medium">{{ $attivi->count() }} {{ $attivi->count() === 1 ? 'articolo attivo' : 'articoli attivi' }}</h2>
                @if ($nDisattivati > 0)
                    <span class="text-sm text-slate-400">{{ $nDisattivati }} {{ $nDisattivati === 1 ? 'disattivato' : 'disattivati' }}</span>
                @endif
            </div>
            <button type="button" @click="nuovo = ! nuovo"
                    class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">
                <span x-text="nuovo ? 'Chiudi' : '+ Nuovo articolo'">+ Nuovo articolo</span>
            </button>
        </div>

        <p class="text-sm text-slate-500 mb-5 max-w-3xl">Il prezzo viene copiato sul conto al momento dell'addebito: se lo cambi qui, i conti già addebitati restano com'erano. Gli articoli disattivati non si vendono più ma restano nello storico.</p>

        {{-- nuovo articolo --}}
        <form x-show="nuovo" x-cloak x-transition.opacity method="POST" action="{{ route('minibar.articoli.crea') }}"
              class="bg-white rounded-2xl border border-gray-200 p-5 mb-5">
            @csrf
            <div class="flex flex-wrap items-end gap-3">
                <label class="flex-1 min-w-[200px]">
                    <span class="block text-xs text-slate-500 mb-1">Nome articolo</span>
                    <input name="nome" required maxlength="100" value="{{ old('nome') }}" class="w-full {{ $campo }}">
                </label>
                <label>
                    <span class="block text-xs text-slate-500 mb-1">Categoria</span>
                    <select name="categoria" class="{{ $campo }}">
                        @foreach ($categorie as $c)
                            <option value="{{ $c }}" @selected(old('categoria') === $c)>{{ ucfirst($c) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="block text-xs text-slate-500 mb-1">Prezzo (€)</span>
                    <input name="prezzo" type="number" step="0.01" min="0" required value="{{ old('prezzo') }}" class="w-28 text-right {{ $campo }}">
                </label>
                <label>
                    <span class="block text-xs text-slate-500 mb-1">IVA (%)</span>
                    <input name="aliquota_iva" type="number" step="0.01" min="0" max="100" required value="{{ old('aliquota_iva', 22) }}" class="w-24 text-right {{ $campo }}">
                </label>
                <label>
                    <span class="block text-xs text-slate-500 mb-1">Magazzino (scarica 1 pezzo)</span>
                    <select name="magazzino_articolo_id" class="{{ $campo }}">
                        <option value="">— non collegato</option>
                        @foreach ($magazzino as $m)
                            <option value="{{ $m->id }}">{{ $m->nome }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="bg-fdBlue hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Aggiungi articolo</button>
            </div>
        </form>

        @if ($articoli->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-200 px-6 py-10 text-center">
                <h3 class="text-lg font-medium mb-2">Il listino è vuoto</h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mb-5">Parti dagli articoli più comuni di un minibar (acqua, bibite, birra, vino, snack) e poi correggi prezzi e aliquote, oppure aggiungi il primo articolo a mano.</p>
                <form method="POST" action="{{ route('minibar.base') }}">
                    @csrf
                    <button class="bg-fdBlue hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Carica listino base</button>
                </form>
            </div>
        @else
            <div class="overflow-x-auto pb-1">
                <div class="min-w-[980px]">

                    <div class="grid {{ $colonne }} items-center gap-3 bg-white rounded-xl border border-gray-100 px-5 h-12 text-sm text-slate-500 mb-3">
                        <span class="px-2.5">Articolo</span>
                        <span class="px-2.5">Categoria</span>
                        <span class="px-2.5 text-right">Prezzo €</span>
                        <span class="px-2.5 text-right">IVA %</span>
                        <span class="px-2.5">Magazzino</span>
                        <span>Attivo</span>
                        <span></span>
                    </div>

                    <div class="flex flex-col gap-3">
                        @foreach ($articoli as $a)
                            <form method="POST" action="{{ route('minibar.articoli.aggiorna', $a->id) }}"
                                  @class(['grid items-center gap-3 bg-white rounded-xl border border-gray-200 px-5 h-[62px]', $colonne, 'opacity-60' => ! $a->attivo])>
                                @csrf

                                <input name="nome" value="{{ $a->nome }}" required maxlength="100" aria-label="Nome"
                                       class="campo-inline w-full text-[15px] font-medium truncate">

                                <select name="categoria" aria-label="Categoria" class="campo-inline w-full capitalize">
                                    @foreach ($categorie as $c)
                                        <option value="{{ $c }}" @selected($a->categoria === $c)>{{ ucfirst($c) }}</option>
                                    @endforeach
                                </select>

                                <input name="prezzo" type="number" step="0.01" min="0" value="{{ $a->prezzo }}" required aria-label="Prezzo in euro"
                                       class="campo-inline campo-inline--num w-full">

                                <input name="aliquota_iva" type="number" step="0.01" min="0" max="100" value="{{ (float) $a->aliquota_iva }}" required aria-label="Aliquota IVA"
                                       class="campo-inline campo-inline--num w-full">

                                <select name="magazzino_articolo_id" aria-label="Articolo di magazzino collegato" class="campo-inline w-full">
                                    <option value="">— non collegato</option>
                                    @foreach ($magazzino as $m)
                                        <option value="{{ $m->id }}" @selected((int) $a->magazzino_articolo_id === (int) $m->id)>{{ $m->nome }}</option>
                                    @endforeach
                                </select>

                                <span class="interruttore" title="{{ $a->attivo ? 'Attivo: si vende' : 'Disattivato: non si vende più' }}">
                                    <input type="checkbox" name="attivo" value="1" @checked($a->attivo) aria-label="Attivo">
                                    <span class="interruttore__traccia"></span>
                                </span>

                                <button class="justify-self-end text-sm font-medium text-fdBlue hover:underline">Salva</button>
                            </form>
                        @endforeach
                    </div>

                </div>
            </div>
        @endif
    </div>

    {{-- ==========================================
         TAB 2: DOTAZIONE PER TIPOLOGIA
         ========================================== --}}
    <div x-show="tab === 'dotazioni'" x-cloak x-transition.opacity>

        <h2 class="text-lg font-medium mb-1">Dotazione standard</h2>
        <p class="text-sm text-slate-500 mb-5 max-w-3xl">Quanti pezzi di ogni articolo ci devono essere in camera. La governante vede questa lista nel controllo e segna quanti ne mancano.</p>

        @if ($attivi->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-200 p-6 text-sm text-slate-500">
                Prima di impostare le dotazioni serve almeno un articolo attivo nel
                <button type="button" @click="tab = 'listino'" class="text-fdBlue hover:underline">listino</button>.
            </div>
        @else
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                @forelse ($tipologie as $t)
                    @php $totale = collect($dotazione[$t->id] ?? [])->sum(); @endphp

                    <form method="POST" action="{{ route('minibar.dotazione.salva', $t->id) }}" class="bg-white rounded-2xl border border-gray-200 flex flex-col">
                        @csrf

                        <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-100">
                            <h3 class="text-base font-medium">{{ $t->nome }}</h3>
                            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-paper text-slate-600">{{ $totale }} {{ $totale === 1 ? 'pezzo' : 'pezzi' }}</span>
                        </div>

                        <div class="px-6 py-1 divide-y divide-gray-100">
                            @foreach ($attivi as $a)
                                <label class="flex items-center justify-between gap-3 py-2.5">
                                    <span class="min-w-0 text-[15px]">
                                        {{ $a->nome }}
                                        <span class="ml-1 text-xs text-slate-400">€ {{ $eur($a->prezzo) }}</span>
                                    </span>
                                    <input type="number" name="quantita[{{ $a->id }}]" min="0" max="20" value="{{ $dotazione[$t->id][$a->id] ?? 0 }}"
                                           class="w-20 shrink-0 border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-right focus:outline-none focus:ring-2 focus:ring-fdBlue">
                                </label>
                            @endforeach
                        </div>

                        <div class="flex justify-end px-6 py-4 mt-auto border-t border-gray-100">
                            <button class="bg-fdBlue hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Salva dotazione</button>
                        </div>
                    </form>
                @empty
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 text-sm text-slate-500 xl:col-span-2">
                        Nessuna tipologia di camera attiva: la dotazione si imposta per ogni tipologia.
                    </div>
                @endforelse
            </div>
        @endif
    </div>

</div>

@endsection
