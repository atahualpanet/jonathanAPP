@php
    app()->setLocale(session('locale', 'it'));
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $colore = config('permessi.moduli.bistro.colore', '#C9822B');
    $campo = 'border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#C9822B]';
    
    // Filtra gli articoli sotto soglia per l'avviso in alto (solo quelli collegati al magazzino)
    $sotto = $articoli->filter(fn ($a) => !empty($a->magazzino_id) && $a->soglia !== null && (float) ($a->scorta ?? 0) <= (float) $a->soglia);
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Scorte Magazzino')
@section('page_title', 'Scorte Magazzino Centrale')
@section('page_color', $colore)

@section('content')
<div class="w-full max-w-5xl">

    <a href="{{ route('bistro.mappa') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">&larr; Torna ai tavoli</a>

    @include('frontdesk.partials.messaggi')

    <!-- Selettore Sezione (Bar / Ristorante) -->
    <div class="flex flex-wrap gap-2 mb-3">
        @foreach ($sezioni as $nomeSezione => $cats)
            <a href="{{ route('bistro.scorte', ['sezione' => $nomeSezione]) }}" class="px-5 py-2 rounded-full text-sm font-medium border {{ $sezione === $nomeSezione ? 'bg-[#C9822B] border-[#C9822B] text-white' : 'bg-white border-gray-200 text-slate-600 hover:border-[#C9822B]' }}">
                {{ $nomeSezione }}
            </a>
        @endforeach
    </div>

    <!-- Filtro Categorie della Sezione -->
    <div class="flex flex-wrap gap-2 mb-5">
        <a href="{{ route('bistro.scorte', ['sezione' => $sezione, 'categoria' => 'tutte']) }}" class="px-3.5 py-1.5 rounded-lg text-xs font-medium border {{ $categoriaAttiva === 'tutte' ? 'bg-deepNavy text-white border-deepNavy' : 'bg-white border-gray-200 text-slate-600 hover:border-deepNavy' }}">
            Tutte le categorie
        </a>
        @foreach ($categorie as $chiaveCat => $nomeCat)
            <a href="{{ route('bistro.scorte', ['sezione' => $sezione, 'categoria' => $chiaveCat]) }}" class="px-3.5 py-1.5 rounded-lg text-xs font-medium border {{ $categoriaAttiva === $chiaveCat ? 'bg-[#C9822B] text-white border-[#C9822B]' : 'bg-white border-gray-200 text-slate-600 hover:border-[#C9822B]' }}">
                {{ $nomeCat }}
            </a>
        @endforeach
    </div>

    <!-- Avviso Articoli Sotto Soglia -->
    @if ($sotto->isNotEmpty())
        <div class="bg-[#B5563C]/10 border border-[#B5563C]/30 rounded-2xl px-5 py-4 mb-5 text-sm text-[#8E3F2A]">
            <b>⚠️ Attenzione: articoli sotto soglia nel magazzino centrale:</b> {{ $sotto->map(fn ($a) => $a->nome . ' (' . $n($a->scorta) . ' ' . $a->unita_misura . ')')->implode(', ') }}
        </div>
    @endif

    <!-- Tabella Scorte -->
    <div class="bg-white rounded-2xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-[15px] min-w-[760px]">
            <thead class="text-left text-sm text-slate-500 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 font-normal">Articolo Listino</th>
                    <th class="px-3 py-3 font-normal">Collegamento Magazzino</th>
                    <th class="px-3 py-3 font-normal text-right">Scorta Centrale</th>
                    <th class="px-3 py-3 font-normal text-right">Soglia</th>
                    <th class="px-3 py-3 font-normal">Stato</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($articoli as $a)
                    @php 
                        $collegato = !empty($a->magazzino_id);
                        $basso = $collegato && $a->soglia !== null && (float) ($a->scorta ?? 0) <= (float) $a->soglia;
                        $esaurito = $collegato && (float) ($a->scorta ?? 0) <= 0;
                    @endphp
                    <tr>
                        <td class="px-5 py-3 font-medium">
                            {{ $a->nome }} 
                            <span class="text-xs text-slate-400 block">{{ $a->categoria }}</span>
                        </td>
                        <td class="px-3 py-3 text-sm">
                            @if ($a->magazzino_id)
                                <span class="text-green-600 font-medium">✓ {{ $a->magazzino_nome }}</span>
                            @else
                                <span class="text-[#B5563C] font-medium">✗ Non collegato</span>
                                <span class="block text-xs text-slate-400">Non scalerà le scorte</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right {{ $basso || $esaurito ? 'text-[#B5563C] font-bold' : '' }}">
                            {{ $a->magazzino_id ? $n($a->scorta ?? 0) . ' ' . $a->unita_misura : '—' }}
                        </td>
                        <td class="px-3 py-3 text-right text-slate-500">
                            {{ $a->soglia !== null ? $n($a->soglia) : '—' }}
                        </td>
                        <td class="px-3 py-3">
                            @if (!$a->magazzino_id)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">No Scorta</span>
                            @elseif ($esaurito)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Esaurito</span>
                            @elseif ($basso)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Sotto Soglia</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">OK</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                            Nessun articolo in questa categoria. Aggiungi articoli dal listino.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-slate-400 mt-4">
        ℹ️ <b>Nota operativa:</b> Bar e Ristorante attingono direttamente dal <b>Magazzino Centrale</b>. 
        Assicurati che ogni articolo del listino sia collegato a un articolo di magazzino (da "Listino e tavoli"). 
        La scorta si aggiorna e si controlla automaticamente a ogni incasso.
    </p>
</div>
@endsection