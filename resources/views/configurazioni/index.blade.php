@php
    app()->setLocale(session('locale', 'it'));
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Configurazioni Hotel')
@section('page_title', 'Configurazioni Hotel')
@section('page_color', '#7A3B4A')

@section('content')

<div class="w-full" x-data="{ tabAttiva: 'generali' }">

    @if(session('success'))
        <div class="mb-6 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-lg shadow-sm text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-800 p-4 rounded-lg shadow-sm text-sm">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- TABS DI NAVIGAZIONE --}}
    <div class="border-b border-gray-200 mb-6 flex overflow-x-auto whitespace-nowrap">
        <button @click="tabAttiva = 'generali'" 
                :class="tabAttiva === 'generali' ? 'border-fdBlue text-fdBlue' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300'"
                class="py-3 px-6 border-b-2 font-medium text-sm transition-colors focus:outline-none">
            Dati Generali & Fiscali
        </button>
        <button @click="tabAttiva = 'camere'" 
                :class="tabAttiva === 'camere' ? 'border-fdBlue text-fdBlue' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300'"
                class="py-3 px-6 border-b-2 font-medium text-sm transition-colors focus:outline-none">
            Camere & Tariffe
        </button>
        <button @click="tabAttiva = 'servizi'" 
                :class="tabAttiva === 'servizi' ? 'border-fdBlue text-fdBlue' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300'"
                class="py-3 px-6 border-b-2 font-medium text-sm transition-colors focus:outline-none">
            Servizi & Policy
        </button>
        <button @click="tabAttiva = 'integrazioni'" 
                :class="tabAttiva === 'integrazioni' ? 'border-fdBlue text-fdBlue' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300'"
                class="py-3 px-6 border-b-2 font-medium text-sm transition-colors focus:outline-none">
            Integrazioni & Canali
        </button>
    </div>

    {{-- ==========================================
         TAB 1: DATI GENERALI & FISCALI
         ========================================== --}}
    <div x-show="tabAttiva === 'generali'" x-transition.opacity>
        
        {{-- FORM DEL LOGO (separato dal form principale) --}}
        <div class="bg-white shadow-sm rounded-t-2xl border border-b-0 border-gray-200 p-6">
            <h2 class="text-lg font-bold text-deepNavy mb-2">Logo della Struttura</h2>
            <p class="text-xs text-slateBlue mb-4">Trascina qui il logo o clicca per selezionarlo (PNG, JPG, SVG — max 2MB).</p>

            <div class="flex flex-col md:flex-row items-start gap-6">
                <div class="w-24 h-24 rounded-xl bg-slate-100 border border-gray-200 flex items-center justify-center overflow-hidden shrink-0">
                    @if(!empty($config['hotel']['logo_caricato']))
                        <img src="{{ asset($config['hotel']['logo_caricato']) }}" alt="Logo" class="w-full h-full object-contain p-2">
                    @else
                        <span class="text-xs text-slate-400 text-center px-2">Nessun logo</span>
                    @endif
                </div>

                <div class="flex-1 w-full">
                    <form action="{{ route('configurazioni.logo') }}" method="POST" enctype="multipart/form-data" id="form-logo">
                        @csrf
                        <label for="logo-input" id="dropzone" class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-fdBlue hover:bg-slate-50 transition text-center px-4">
                            <svg class="w-6 h-6 text-slate-400 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
                            <span class="text-xs text-slate-500">Trascina qui o <span class="font-semibold text-fdBlue">clicca</span></span>
                            <input type="file" name="logo" id="logo-input" accept="image/*" class="hidden">
                        </label>

                        <div id="anteprima-nuovo-logo" class="hidden mt-2 flex items-center gap-2">
                            <span id="nome-file" class="text-xs text-emerald-600 font-semibold truncate"></span>
                            <button type="submit" class="ml-auto bg-fdBlue hover:opacity-90 text-white font-semibold px-3 py-1 rounded text-xs">Carica</button>
                        </div>
                    </form>

                    @if(!empty($config['hotel']['logo_caricato']))
                        <form action="{{ route('configurazioni.logo.rimuovi') }}" method="POST" class="mt-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:underline">Rimuovi logo</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- FORM PRINCIPALE (DATI HOTEL) --}}
        <form action="{{ route('configurazioni.salva') }}" method="POST">
            @csrf
            <div class="bg-white shadow-xl rounded-b-2xl border border-gray-200 divide-y divide-gray-200">
                
                {{-- Dati Struttura --}}
                <div class="p-6 bg-gray-50/30">
                    <h2 class="text-lg font-bold text-deepNavy mb-4">Dati Struttura</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Nome Commerciale</label>
                            <input type="text" name="nome_commerciale" value="{{ old('nome_commerciale', $config['hotel']['nome_commerciale'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Ragione Sociale</label>
                            <input type="text" name="nome" value="{{ old('nome', $config['hotel']['nome'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Codice Struttura</label>
                            <input type="text" name="codice_struttura" value="{{ old('codice_struttura', $config['hotel']['codice_struttura'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Stelle</label>
                            <input type="number" name="stella" min="1" max="5" value="{{ old('stella', $config['hotel']['stella'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Sito Web</label>
                            <input type="url" name="sito_web" value="{{ old('sito_web', $config['hotel']['sito_web'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Email Generale</label>
                            <input type="email" name="email_generale" value="{{ old('email_generale', $config['hotel']['email_generale'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Telefono</label>
                            <input type="text" name="telefono_generale" value="{{ old('telefono_generale', $config['hotel']['telefono_generale'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Valuta (ISO)</label>
                            <input type="text" name="valuta" maxlength="3" value="{{ old('valuta', $config['hotel']['valuta'] ?? 'EUR') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none uppercase">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Lingua</label>
                            <select name="locale" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                                <option value="it" {{ ($linguaCorrente ?? 'it') == 'it' ? 'selected' : '' }}>Italiano</option>
                                <option value="en" {{ ($linguaCorrente ?? 'it') == 'en' ? 'selected' : '' }}>English</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Dati Fiscali --}}
                <div class="p-6">
                    <h2 class="text-lg font-bold text-deepNavy mb-4">Dati Fiscali & Fatturazione</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Partita IVA</label>
                            <input type="text" name="partita_iva" value="{{ old('partita_iva', $config['fatturazione']['partita_iva'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Codice Fiscale</label>
                            <input type="text" name="codice_fiscale" value="{{ old('codice_fiscale', $config['fatturazione']['codice_fiscale'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Codice SDI (Univoco)</label>
                            <input type="text" name="codice_univoco" value="{{ old('codice_univoco', $config['fatturazione']['codice_univoco'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">PEC</label>
                            <input type="email" name="pec" value="{{ old('pec', $config['fatturazione']['pec'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div class="md:col-span-2 lg:col-span-3">
                            <label class="block font-semibold text-gray-700 mb-1">Indirizzo (Sede Legale)</label>
                            <input type="text" name="fatt_indirizzo" value="{{ old('fatt_indirizzo', $config['fatturazione']['indirizzo'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">CAP</label>
                            <input type="text" name="fatt_cap" value="{{ old('fatt_cap', $config['fatturazione']['cap'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Città</label>
                            <input type="text" name="fatt_citta" value="{{ old('fatt_citta', $config['fatturazione']['citta'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Provincia</label>
                            <input type="text" name="fatt_provincia" value="{{ old('fatt_provincia', $config['fatturazione']['provincia'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                        <div class="md:col-span-2 lg:col-span-3">
                            <label class="block font-semibold text-gray-700 mb-1">IBAN</label>
                            <input type="text" name="iban" value="{{ old('iban', $config['fatturazione']['iban'] ?? '') }}" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pulsante Salva Configurazioni Generali --}}
            <div class="mt-6 flex justify-end">
                <button type="submit" class="bg-fdBlue hover:bg-opacity-90 text-white font-semibold px-6 py-2.5 rounded-lg shadow transition-all text-sm">
                    Salva Configurazioni Generali
                </button>
            </div>
        </form>
    </div>

    {{-- ==========================================
         TAB 2: CAMERE & TARIFFE
         ========================================== --}}
    <div x-show="tabAttiva === 'camere'" x-transition.opacity class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-200" x-cloak>
        
        {{-- Intestazione e Pulsante Nuova Camera --}}
        <div class="p-6 border-b border-gray-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50/50">
           <button type="button" onclick="document.getElementById('form-nuova-camera').classList.toggle('hidden')" class="bg-fdBlue hover:bg-opacity-90 text-white font-semibold px-4 py-2 rounded-lg shadow transition-all text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Aggiungi Camera
            </button>
        </div>

        {{-- Form Nuova Camera (nascosto di default) --}}
        <div id="form-nuova-camera" class="hidden p-6 bg-blue-50/30 border-b border-gray-200">
            <form action="{{ route('configurazioni.camere.salva') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                @csrf
                <div>
                    <label class="block font-semibold text-gray-700 mb-1 text-sm">Numero Camera</label>
                    <input type="text" name="numero" placeholder="Es. 101" required class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-fdBlue focus:outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1 text-sm">Piano</label>
                    <input type="number" name="piano" value="0" required class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-fdBlue focus:outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1 text-sm">Tipologia (Prezzo)</label>
                    <select name="tipologia_camera_id" required class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        <option value="">Seleziona...</option>
                        @foreach($tipologie as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->nome }} (€ {{ number_format($tipo->prezzo_base, 2, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1 text-sm">Stato Iniziale</label>
                    <select name="stato_pulizia" required class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-fdBlue focus:outline-none">
                        <option value="pulita">Pulita</option>
                        <option value="sporca">Sporca</option>
                        <option value="manutenzione">Manutenzione</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-lg shadow text-sm transition">
                        Salva Camera
                    </button>
                </div>
            </form>
        </div>

        {{-- Tabella Camere --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-gray-200 text-xs uppercase tracking-wider text-slate-500 font-semibold">
                        <th class="p-4">Numero</th>
                        <th class="p-4">Tipologia</th>
                        <th class="p-4">Piano</th>
                        <th class="p-4">Stato Pulizia</th>
                        <th class="p-4">Prezzo Base</th>
                        <th class="p-4">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($camere as $camera)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="p-4 text-sm font-bold text-deepNavy">{{ $camera->numero }}</td>
                            <td class="p-4 text-sm text-slate-600 font-medium">{{ $camera->tipologia->nome ?? 'N/D' }}</td>
                            <td class="p-4 text-sm text-slate-500">{{ $camera->piano }}</td>
                            <td class="p-4 text-sm">
                                @php
                                    $coloreStato = match($camera->stato_pulizia) {
                                        'pulita', 'ispezionata' => 'bg-emerald-100 text-emerald-800',
                                        'sporca' => 'bg-red-100 text-red-800',
                                        'manutenzione' => 'bg-amber-100 text-amber-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $coloreStato }}">
                                    {{ ucfirst($camera->stato_pulizia) }}
                                </span>
                            </td>
                            <td class="p-4 text-sm font-bold text-deepNavy">
                                € {{ number_format($camera->tipologia->prezzo_base ?? 0, 2, ',', '.') }}
                            </td>
                            <td class="p-4 text-sm">
                                <a href="{{ route('camere.dettaglio', $camera->id) }}" class="text-fdBlue hover:underline font-semibold flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    Dettaglio
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500 text-sm">
                                Nessuna camera inserita. Clicca su "Aggiungi Camera" per iniziare.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==========================================
         TAB 3: SERVIZI & POLICY (Segnaposto)
         ========================================== --}}
    <div x-show="tabAttiva === 'servizi'" x-transition.opacity class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-200 p-12 text-center" x-cloak>
        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h2 class="text-xl font-bold text-deepNavy">Servizi e Policy</h2>
        <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">Qui potrai configurare gli orari di check-in/out, la tassa di soggiorno, la colazione e i servizi extra (parcheggio, animali, ecc.).</p>
        <p class="text-xs text-amber-600 font-semibold mt-4">Modulo in fase di sviluppo</p>
    </div>

    {{-- ==========================================
         TAB 4: INTEGRAZIONI & CANALI (Segnaposto)
         ========================================== --}}
    <div x-show="tabAttiva === 'integrazioni'" x-transition.opacity class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-200 p-12 text-center" x-cloak>
        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
        </div>
        <h2 class="text-xl font-bold text-deepNavy">Integrazioni e Canali</h2>
        <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">Qui potrai collegare Booking.com, Expedia, Siteminder e configurare l'invio telematico all'Alloggiati Web e all'ISTAT.</p>
        <p class="text-xs text-amber-600 font-semibold mt-4">Modulo in fase di sviluppo</p>
    </div>

</div>

@endsection

@push('scripts')
<script>
(function () {
    const input   = document.getElementById('logo-input');
    const drop    = document.getElementById('dropzone');
    const box     = document.getElementById('anteprima-nuovo-logo');
    const nome    = document.getElementById('nome-file');

    if (!input) return;

    function mostraAnteprima(file) {
        if (!file || !file.type.startsWith('image/')) return;
        nome.textContent = file.name;
        box.classList.remove('hidden');
    }

    input.addEventListener('change', () => mostraAnteprima(input.files[0]));

    ['dragenter', 'dragover'].forEach(evt =>
        drop.addEventListener(evt, e => {
            e.preventDefault();
            drop.classList.add('border-fdBlue', 'bg-slate-50');
        })
    );
    ['dragleave', 'drop'].forEach(evt =>
        drop.addEventListener(evt, e => {
            e.preventDefault();
            drop.classList.remove('border-fdBlue', 'bg-slate-50');
        })
    );

    drop.addEventListener('drop', e => {
        e.preventDefault();
        const files = e.dataTransfer.files;
        if (files.length) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(files[0]);
            input.files = dataTransfer.files;
            mostraAnteprima(files[0]);
        }
    });
})();
</script>
@endpush