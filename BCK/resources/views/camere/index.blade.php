@php
    app()->setLocale(session('locale', 'it'));
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Impostazioni')
@section('page_title', 'Impostazioni di Sistema')
@section('page_color', '#64748B')

@section('content')

<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-deepNavy">Impostazioni di Sistema</h1>
        <p class="text-slateBlue text-sm mt-1">Gestisci la struttura, gli utenti e i parametri del software.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        {{-- Card Configurazioni Hotel --}}
        <a href="{{ route('configurazioni') }}" class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:border-fdBlue transition flex items-start gap-4 no-underline group">
            <div class="w-12 h-12 rounded-xl bg-[#4F86B0]/10 text-[#4F86B0] flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900 group-hover:text-fdBlue transition-colors">Configurazioni Hotel</h3>
                <p class="text-sm text-slate-500 mt-1">Dati fiscali, camere, tariffe, servizi e policy della struttura.</p>
            </div>
        </a>

        {{-- Card Minibar --}}
        <a href="{{ route('minibar.listino') }}" class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:border-fdBlue transition flex items-start gap-4 no-underline group">
            <div class="w-12 h-12 rounded-xl bg-[#D97706]/10 text-[#D97706] flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 22h8"/><path d="M12 11v11"/><path d="M19 3H5l7 9 7-9Z"/></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900 group-hover:text-fdBlue transition-colors">Minibar</h3>
                <p class="text-sm text-slate-500 mt-1">Listino prezzi, articoli e dotazioni base per tipologia di camera.</p>
            </div>
        </a>

        {{-- Card Operatori --}}
        <a href="{{ route('operatori.index') }}" class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:border-fdBlue transition flex items-start gap-4 no-underline group">
            <div class="w-12 h-12 rounded-xl bg-[#7C3AED]/10 text-[#7C3AED] flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900 group-hover:text-fdBlue transition-colors">Operatori & Permessi</h3>
                <p class="text-sm text-slate-500 mt-1">Gestisci gli account del personale e i loro livelli di accesso al sistema.</p>
            </div>
        </a>

        {{-- Card Configurazioni Software --}}
        <a href="{{ url('/configurazioni-software') }}" class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:border-fdBlue transition flex items-start gap-4 no-underline group">
            <div class="w-12 h-12 rounded-xl bg-slate-500/10 text-slate-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900 group-hover:text-fdBlue transition-colors">Configurazioni Software</h3>
                <p class="text-sm text-slate-500 mt-1">Parametri tecnici, log di sistema, backup e integrazioni avanzate.</p>
            </div>
        </a>

    </div>
</div>

@endsection