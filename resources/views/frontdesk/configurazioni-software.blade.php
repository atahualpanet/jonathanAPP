@extends('layouts.shell')
@section('title', 'Jonathan PMS — Configurazioni Software')
@section('page_title', 'Configurazioni Software')
@section('page_color', '#7A3B4A')

@section('content')
<div class="w-full">
    @include('frontdesk.partials.messaggi')

    <a href="{{ route('impostazioni.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">
        &larr; Torna alle Impostazioni
    </a>

    <form method="POST" action="{{ route('configurazioni.software.salva') }}" class="space-y-6">
        @csrf

        {{-- ===== RETE E AMBIENTE ===== --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-lg font-medium mb-4">Rete e Ambiente</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">URL base applicazione</label>
                    <input type="text" name="app_url" value="{{ old('app_url', $sistema['app_url'] ?? '') }}" placeholder="http://127.0.0.1:8000" class="w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Ambiente</label>
                    <select name="ambiente" class="w-full rounded-lg border-gray-300">
                        <option value="locale" {{ ($sistema['ambiente'] ?? 'locale') === 'locale' ? 'selected' : '' }}>Locale (sviluppo)</option>
                        <option value="produzione" {{ ($sistema['ambiente'] ?? '') === 'produzione' ? 'selected' : '' }}>Produzione</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Timeout sessione (minuti)</label>
                    <input type="number" name="timeout_sessione" value="{{ old('timeout_sessione', $sistema['timeout_sessione'] ?? 30) }}" min="1" max="1440" class="w-full rounded-lg border-gray-300">
                </div>
            </div>
        </div>

        {{-- ===== SICUREZZA ===== --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-lg font-medium mb-4">Sicurezza</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Tentativi di login massimi</label>
                    <input type="number" name="tentativi_login" value="{{ old('tentativi_login', $sicurezza['tentativi_login'] ?? 5) }}" min="1" max="20" class="w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">IP Whitelist (uno per riga)</label>
                    <textarea name="ip_whitelist" rows="3" class="w-full rounded-lg border-gray-300">{{ old('ip_whitelist', $sicurezza['ip_whitelist'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ===== MANUTENZIONE E BACKUP ===== --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-lg font-medium mb-4">Manutenzione e Backup</h2>
            <div class="space-y-3">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="manutenzione" value="1" {{ old('manutenzione', $sistema['manutenzione'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300">
                    <span class="text-sm">Modalità manutenzione attiva</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="backup_attivo" value="1" {{ old('backup_attivo', $sistema['backup_attivo'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300">
                    <span class="text-sm">Backup automatico del database attivo</span>
                </label>
                <div>
                    <label class="block text-sm font-medium mb-1">Giorni di conservazione log</label>
                    <input type="number" name="giorni_log" value="{{ old('giorni_log', $sistema['giorni_log'] ?? 30) }}" min="1" max="365" class="w-full md:w-40 rounded-lg border-gray-300">
                </div>
            </div>
        </div>

        {{-- ===== ALLOGGIATI WEB ===== --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-lg font-medium mb-4">Credenziali Alloggiati Web</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Codice struttura</label>
                    <input type="text" name="alloggiati_codice" value="{{ old('alloggiati_codice', $alloggiati['codice_struttura'] ?? '') }}" class="w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Utente portale</label>
                    <input type="text" name="alloggiati_utente" value="{{ old('alloggiati_utente', $alloggiati['utente_portale'] ?? '') }}" class="w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Password portale</label>
                    <input type="password" name="alloggiati_password" value="{{ old('alloggiati_password', $alloggiati['password_portale'] ?? '') }}" class="w-full rounded-lg border-gray-300">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-fdBlue hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">
                Salva configurazioni
            </button>
        </div>
    </form>
</div>
@endsection