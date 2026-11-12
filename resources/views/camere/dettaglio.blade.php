@php
    app()->setLocale(session('locale', 'it'));
@endphp
@extends('layouts.shell')

@section('title', 'Jonathan PMS — Dettaglio Camera')
@section('page_title', 'Dettaglio Camera')
@section('page_color', '#4F86B0')

@section('content')

<div class="wi-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-deepNavy">Camera {{ $camera->numero }}</h1>
            <p class="text-slateBlue text-sm mt-1">{{ $camera->tipologia->nome ?? 'Tipologia N/D' }} · Piano {{ $camera->piano }}</p>
        </div>
        <a href="{{ url()->previous() }}" class="text-sm text-slate-500 hover:text-deepNavy font-medium flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Torna indietro
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-lg shadow-sm text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('camere.dettaglio.salva', $camera->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Colonna Sinistra: Foto e Piantina con Drag & Drop --}}
            <div class="lg:col-span-1 space-y-6">
                
                {{-- Foto Camera --}}
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
                    <h3 class="text-sm font-bold text-slate-700 mb-3">Foto della Camera</h3>
                    
                    <div class="aspect-video bg-slate-100 rounded-xl overflow-hidden mb-3 flex items-center justify-center border border-gray-200">
                        @if($camera->foto_path)
                            <img src="{{ asset('storage/' . $camera->foto_path) }}" alt="Foto camera" class="w-full h-full object-cover">
                        @else
                            <span class="text-xs text-slate-400">Nessuna foto caricata</span>
                        @endif
                    </div>

                    <label for="foto-input" id="dropzone-foto" class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-fdBlue hover:bg-slate-50 transition text-center px-4">
                        <svg class="w-6 h-6 text-slate-400 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
                        <span class="text-xs text-slate-500">Trascina qui o <span class="font-semibold text-fdBlue">clicca</span></span>
                        <input type="file" name="foto" id="foto-input" accept="image/*" class="hidden">
                    </label>
                    <div id="anteprima-foto" class="hidden mt-2 text-xs text-emerald-600 font-semibold text-center"></div>
                </div>

                {{-- Piantina --}}
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
                    <h3 class="text-sm font-bold text-slate-700 mb-3">Piantina / Posizione</h3>
                    
                    <div class="aspect-square bg-slate-100 rounded-xl overflow-hidden mb-3 flex items-center justify-center border border-gray-200">
                        @if($camera->piantina_path)
                            <img src="{{ asset('storage/' . $camera->piantina_path) }}" alt="Piantina" class="w-full h-full object-contain p-2">
                        @else
                            <span class="text-xs text-slate-400">Nessuna piantina caricata</span>
                        @endif
                    </div>

                    <label for="piantina-input" id="dropzone-piantina" class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-fdBlue hover:bg-slate-50 transition text-center px-4">
                        <svg class="w-6 h-6 text-slate-400 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
                        <span class="text-xs text-slate-500">Trascina qui o <span class="font-semibold text-fdBlue">clicca</span></span>
                        <input type="file" name="piantina" id="piantina-input" accept="image/*" class="hidden">
                    </label>
                    <div id="anteprima-piantina" class="hidden mt-2 text-xs text-emerald-600 font-semibold text-center"></div>
                </div>
            </div>

            {{-- Colonna Destra: Dettagli e Caratteristiche --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-deepNavy mb-4">Caratteristiche</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-slate-50 p-3 rounded-lg border border-gray-100">
                            <p class="text-xs text-slate-500">Capacità Adulti</p>
                            <p class="text-lg font-bold text-slate-800">{{ $camera->tipologia->capacita_adulti ?? 'N/D' }}</p>
                        </div>
                        <div class="bg-slate-50 p-3 rounded-lg border border-gray-100">
                            <p class="text-xs text-slate-500">Capacità Bambini</p>
                            <p class="text-lg font-bold text-slate-800">{{ $camera->tipologia->capacita_bambini ?? 'N/D' }}</p>
                        </div>
                        <div class="bg-slate-50 p-3 rounded-lg border border-gray-100">
                            <p class="text-xs text-slate-500">Prezzo Base</p>
                            <p class="text-lg font-bold text-emerald-600">€ {{ number_format($camera->tipologia->prezzo_base ?? 0, 2, ',', '.') }}</p>
                        </div>
                        <div class="bg-slate-50 p-3 rounded-lg border border-gray-100">
                            <p class="text-xs text-slate-500">Stato Pulizia</p>
                            <p class="text-sm font-bold text-slate-800 mt-1">{{ ucfirst($camera->stato_pulizia) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Note e Descrizione --}}
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-deepNavy mb-4">Note e Descrizione</h3>
                    <textarea name="note" rows="4" class="w-full border border-gray-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-fdBlue focus:outline-none" placeholder="Inserisci eventuali note o descrizione della camera...">{{ $camera->note }}</textarea>
                </div>
            </div>
        </div>

        {{-- Pulsante Salva --}}
        <div class="mt-6 flex justify-end">
            <button type="submit" class="bg-fdBlue hover:bg-opacity-90 text-white font-semibold px-6 py-2.5 rounded-lg shadow transition-all text-sm">
                Salva Modifiche
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
(function () {
    // Funzione per gestire il drag&drop e l'anteprima di un file
    function setupDropzone(inputId, dropzoneId, anteprimaId) {
        const input = document.getElementById(inputId);
        const drop = document.getElementById(dropzoneId);
        const anteprima = document.getElementById(anteprimaId);

        if (!input || !drop) return;

        input.addEventListener('change', () => {
            if (input.files.length > 0) {
                anteprima.textContent = 'File selezionato: ' + input.files[0].name;
                anteprima.classList.remove('hidden');
            }
        });

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
                input.files = files;
                anteprima.textContent = 'File selezionato: ' + files[0].name;
                anteprima.classList.remove('hidden');
            }
        });
    }

    // Inizializza i due box
    setupDropzone('foto-input', 'dropzone-foto', 'anteprima-foto');
    setupDropzone('piantina-input', 'dropzone-piantina', 'anteprima-piantina');
})();
</script>
@endpush