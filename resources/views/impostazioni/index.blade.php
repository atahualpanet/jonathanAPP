@php
    app()->setLocale(session('locale', 'it'));

    $voci = [
        [
            'titolo' => 'Configurazioni Hotel',
            'testo'  => 'Dati fiscali, camere e tariffe, servizi e policy della struttura.',
            'url'    => route('configurazioni'),
            'icona'  => '<path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        ],
        [
            'titolo' => 'Minibar',
            'testo'  => 'Listino articoli e dotazione standard per tipologia di camera.',
            'url'    => route('minibar.listino'),
            'icona'  => '<path d="M8 22h8"/><path d="M12 11v11"/><path d="M19 3H5l7 9 7-9Z"/>',
        ],
        [
            'titolo' => 'Listino Bistro',
            'testo'  => 'Articoli del ristorante e tavoli di sala, terrazza e bar.',
            'url'    => route('bistro.listino'),
            'icona'  => '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>',
        ],
        [
            'titolo' => 'Utenti e permessi',
            'testo'  => 'Account del personale, profili e moduli a cui ciascuno può accedere.',
            'url'    => route('operatori.index'),
            'icona'  => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        ],
        [
            'titolo' => 'Configurazioni software',
            'testo'  => 'Parametri tecnici, backup, sicurezza e credenziali di sistema.',
            'url'    => '/impostazioni/configurazioni-software',
            'icona'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        ],
        [
            'titolo' => 'Licenze e Moduli',
            'testo'  => 'Stato dei moduli attivi, scadenze delle licenze e aggiornamenti.',
            'url'    => '/impostazioni/licenze',
            'icona'  => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        ],
    ];

    // Nasconde le card tecniche agli utenti che non sono l'ID 3
    if (auth()->id() !== 3) {
        $voci = array_values(array_filter($voci, function ($v) {
            return ! in_array($v['titolo'], ['Configurazioni software'], true);
        }));
    }
@endphp


@extends('layouts.shell')

@section('title', 'Jonathan PMS — Impostazioni')
@section('page_title', 'Impostazioni di Sistema')
@section('page_color', '#7A3B4A')

@section('content')

<div class="w-full">

    @include('frontdesk.partials.messaggi')

    <p class="text-sm text-slate-500 mb-6">Struttura, listino minibar, personale e parametri del software.</p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach ($voci as $v)
            @php $attiva = filled($v['url']); @endphp

            <{{ $attiva ? 'a' : 'div' }}
                @if ($attiva) href="{{ $v['url'] }}" @endif
                class="flex items-center gap-5 rounded-2xl border p-6 transition {{ $attiva ? 'group bg-white border-gray-200 hover:border-fdBlue no-underline' : 'bg-white/60 border-dashed border-gray-300' }}">

                <span class="w-12 h-12 shrink-0 rounded-xl flex items-center justify-center {{ $attiva ? 'bg-deepNavy text-warmGold' : 'bg-slate-200 text-slate-400' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">{!! $v['icona'] !!}</svg>
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block text-lg font-medium {{ $attiva ? 'text-deepNavy group-hover:text-fdBlue transition-colors' : 'text-slate-500' }}">{{ $v['titolo'] }}</span>
                    <span class="block text-sm text-slate-500 mt-1">{{ $v['testo'] }}</span>
                </span>

                @if ($attiva)
                    <svg class="w-5 h-5 shrink-0 text-slate-300 group-hover:text-fdBlue transition-colors" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                @else
                    <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full bg-slate-200 text-slate-600">In arrivo</span>
                @endif

            </{{ $attiva ? 'a' : 'div' }}>
        @endforeach
    </div>

</div>

@endsection
