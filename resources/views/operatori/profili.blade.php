@extends('layouts.shell')

@section('title', 'Jonathan PMS — Profili')
@section('page_title', 'Gestisci profili')
@section('page_color', '#7A3B4A')

@section('content')
<div class="w-full max-w-3xl">

    @include('frontdesk.partials.messaggi')

    <div class="mb-5 flex items-center gap-6">
        <a href="{{ route('operatori.index') }}" class="text-sm text-fdBlue hover:underline">&larr; Utenti e permessi</a>
        <a href="{{ route('permessi.index') }}" class="text-sm text-fdBlue hover:underline">Permessi per profilo &rarr;</a>
    </div>

    <form method="POST" action="{{ route('profili.salva') }}" id="form-profili">
        @csrf
        <div class="bg-white rounded-2xl border border-gray-200 p-5 space-y-4">

            @foreach ($ruoli as $r)
                @php $usati = $utentiPerRuolo[$r->chiave] ?? 0; @endphp
                <div class="border-b border-gray-100 last:border-b-0 pb-4 last:pb-0">
                    <div class="flex items-center gap-3">
                        <input name="profili[{{ $r->id }}][nome]" value="{{ old('profili.'.$r->id.'.nome', $r->nome) }}" required maxlength="100"
                               aria-label="Nome del profilo"
                               class="flex-1 border border-gray-300 rounded px-3 py-2 text-[15px] text-slate-700">

                        <button type="submit" formaction="{{ route('profili.elimina', $r->id) }}"
                                @disabled($usati > 0)
                                onclick="return confirm('Eliminare il profilo {{ e($r->nome) }}?')"
                                title="{{ $usati > 0 ? "Assegnato a $usati utenti: riassegnali prima" : 'Elimina profilo' }}"
                                aria-label="Elimina {{ $r->nome }}"
                                class="w-[18px] h-[18px] rounded-full bg-[#E5635B] flex items-center justify-center shrink-0 {{ $usati > 0 ? 'opacity-30 cursor-not-allowed' : 'hover:opacity-80' }}">
                            <span class="block w-2 h-[2px] bg-white"></span>
                        </button>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-slate-600 pl-1">
                        <span class="text-xs text-slate-400">Moduli:</span>
                        @foreach ($moduli as $mk => $m)
                            <label class="flex items-center gap-1.5">
                                <input type="checkbox" name="profili[{{ $r->id }}][moduli][]" value="{{ $mk }}"
                                       @checked(in_array($mk, $r->moduli ?? [], true)) class="rounded">
                                <span class="w-2.5 h-2.5 rounded-[3px] bg-[{{ $m['colore'] }}]"></span>{{ $m['nome'] }}
                            </label>
                        @endforeach
                        <label class="flex items-center gap-1.5 ml-auto">
                            <input type="checkbox" name="profili[{{ $r->id }}][gestore]" value="1" @checked($r->gestore) class="rounded">
                            Può gestire utenti e permessi
                        </label>
                        <span class="text-xs text-slate-400">{{ $usati }} {{ $usati === 1 ? 'utente' : 'utenti' }}</span>
                    </div>
                </div>
            @endforeach

            <div class="pt-2 flex items-center gap-3">
                <input name="nuovo_nome" value="{{ old('nuovo_nome') }}" maxlength="100" placeholder="Nuovo profilo (es. Cameriere)"
                       aria-label="Nome del nuovo profilo"
                       class="flex-1 border border-dashed border-gray-300 rounded px-3 py-2 text-[15px]">
                <span class="w-[18px] h-[18px] rounded-full bg-okGreen flex items-center justify-center text-white text-xs leading-none shrink-0">+</span>
            </div>
        </div>

        <div class="mt-5">
            <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Salva profili</button>
        </div>
        <p class="mt-3 text-sm text-slate-400">Rinomina cambia solo l'etichetta: gli utenti restano collegati al profilo. Le funzioni consentite si spuntano in «Permessi per profilo».</p>
    </form>
</div>
@endsection
