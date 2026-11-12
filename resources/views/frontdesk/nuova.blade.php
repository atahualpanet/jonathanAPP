@php
    app()->setLocale(session('locale', 'it'));
    // Se si arriva dal clic su una cella vuota del tableau la camera è già scelta: niente assegnazione automatica
    $automaticaAttiva = old() ? (bool) old('automatica') : ! $cameraScelta;
    $tipologiaScelta  = old('tipologia_camera_id', $cameraScelta?->tipologia_camera_id);
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Nuova prenotazione')
@section('page_title', 'Nuova prenotazione')
@section('page_color', '#4F86B0')

@section('content')

<a href="{{ route('frontdesk.prenotazioni') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">
    &larr; Torna al tableau
</a>

@include('frontdesk.partials.messaggi')

<form method="POST" action="{{ route('prenotazioni.nuova.crea') }}" id="form-nuova" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @csrf

    {{-- ===== DATI PRENOTAZIONE ===== --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
               <h2 class="text-lg font-medium mb-1">Ospite</h2>
        <div class="grid grid-cols-2 gap-3 relative">
            <div class="col-span-2">
                <label class="block text-xs text-slate-500 mb-1">Cerca un ospite esistente (opzionale)</label>
                <input type="text" id="cerca-ospite" placeholder="Inizia a digitare nome o cognome..."
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-fdBlue focus:outline-none">
                {{-- Dropdown dei risultati di ricerca --}}
                <div id="risultati-ricerca" class="hidden absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto"></div>
            </div>
            
            <input name="ospite_nome" id="ospite_nome" placeholder="Nome" value="{{ old('ospite_nome') }}" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
            <input name="ospite_cognome" id="ospite_cognome" placeholder="Cognome" value="{{ old('ospite_cognome') }}" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
            <input type="email" name="ospite_email" id="ospite_email" placeholder="Email (facoltativa)" value="{{ old('ospite_email') }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
            <input name="ospite_telefono" id="ospite_telefono" placeholder="Telefono (facoltativo)" value="{{ old('ospite_telefono') }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
        </div>
        <p class="text-xs text-slate-400" id="msg-ospite-esistente">Se esiste già un ospite con questo nome e cognome, la prenotazione viene collegata a lui invece di crearne uno nuovo.</p>            <input type="email" name="ospite_email" placeholder="Email (facoltativa)" value="{{ old('ospite_email') }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">

        <h2 class="text-lg font-medium pt-2">Soggiorno</h2>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-slate-500 mb-1">Arrivo</label>
                <input type="date" name="data_arrivo" id="data_arrivo" value="{{ old('data_arrivo', $arrivoDefault) }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Partenza</label>
                <input type="date" name="data_partenza" id="data_partenza" value="{{ old('data_partenza', $partenzaDefault) }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Adulti</label>
                <input type="number" name="adulti" id="adulti" min="1" max="10" value="{{ old('adulti', 2) }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Bambini</label>
                <input type="number" name="bambini" min="0" max="10" value="{{ old('bambini', 0) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Canale</label>
                <select name="canale" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <option value="diretto">Diretto</option>
                    <option value="telefono">Telefono</option>
                    <option value="walk_in">Walk-in</option>
                    <option value="ota">OTA</option>
                    <option value="altro">Altro</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Tipologia</label>
                <select name="tipologia_camera_id" id="tipologia" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">Scegli…</option>
                    @foreach ($tipologie as $t)
                        <option value="{{ $t->id }}" data-prezzo="{{ $t->prezzo_base }}" @selected($tipologiaScelta == $t->id)>
                            {{ $t->nome }} · € {{ number_format($t->prezzo_base, 2, ',', '.') }}/notte
                        </option>
                    @endforeach
                </select>
                <p id="msg-disponibilita" class="text-xs mt-1.5"></p>
            </div>
        </div>

        @if ($cameraScelta)
            <p class="text-xs text-fdBlue font-medium">Camera {{ $cameraScelta->numero }} scelta dal tableau.</p>
        @endif

        <div class="pt-2">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="automatica" id="automatica" value="1" @checked($automaticaAttiva)
                       class="rounded border-gray-300 text-fdBlue focus:ring-fdBlue">
                Assegnazione camera automatica
            </label>

            <div id="blocco-camera-manuale" class="hidden mt-3">
                <label class="block text-xs text-slate-500 mb-1">Camera</label>
                <select name="camera_id" id="camera_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">Scegli prima la tipologia…</option>
                </select>
            </div>
        </div>

        <div class="flex gap-2 pt-3">
            <button class="bg-fdBlue hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-lg text-sm">Crea prenotazione</button>
            <a href="{{ route('frontdesk.prenotazioni') }}" class="bg-white border border-gray-200 hover:bg-gray-50 px-5 py-2.5 rounded-lg text-sm font-medium">Annulla</a>
        </div>
    </div>

    {{-- ===== RIEPILOGO (calcolato live) ===== --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 h-fit">
        <h2 class="text-lg font-medium mb-4">Riepilogo</h2>
        <dl class="space-y-2 text-[15px]" id="riepilogo">
            <div class="flex justify-between"><dt class="text-slate-500" id="riepilogo-notti-label">— notti × €0</dt><dd id="riepilogo-soggiorno">€ 0,00</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Tassa di soggiorno</dt><dd id="riepilogo-tassa">€ 0,00</dd></div>
            <div class="border-t border-gray-100 pt-2 flex justify-between font-medium"><dt>Totale</dt><dd id="riepilogo-totale">€ 0,00</dd></div>
        </dl>

        <div class="bg-cream rounded-xl px-5 py-4 mt-6 border border-warmGold/20">
            <label for="caparra-input" class="block text-sm font-semibold text-deepNavy mb-2">Caparra</label>
            <p class="text-xs text-slate-500 mb-3">Inserisci un importo manuale o usa il calcolo automatico del 30%.</p>
            
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium">€</span>
                    <input type="number" step="0.01" min="0" name="caparra" id="caparra-input"
                           placeholder="0,00"
                           class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-fdBlue focus:outline-none">
                </div>
                <button type="button" id="btn-caparra-30"
                        class="bg-white hover:bg-slate-50 text-deepNavy font-semibold px-4 py-2 rounded-lg border border-gray-300 transition-colors text-sm shadow-sm whitespace-nowrap">
                    Usa 30%
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const camerePerTipologiaStatico = @json($camere->map(fn ($g) => $g->map(fn ($c) => ['id' => $c->id, 'numero' => $c->numero]))->toArray());
    const tassaCfg = @json($tassaCfg);
    const urlDisponibilita = @json(route('prenotazioni.nuova.disponibilita'));
    let cameraDesiderata = @json((string) old('camera_id', $cameraScelta?->id));

    const $ = id => document.getElementById(id);
    const arrivo = $('data_arrivo'), partenza = $('data_partenza'), adulti = $('adulti'), tipologia = $('tipologia');
    const automatica = $('automatica'), blocco = $('blocco-camera-manuale'), cameraSel = $('camera_id');
    const caparraInput = $('caparra-input'), msgDisponibilita = $('msg-disponibilita');

    function notti() {
        if (!arrivo.value || !partenza.value) return 0;
        const a = new Date(arrivo.value), p = new Date(partenza.value);
        const n = Math.round((p - a) / 86400000);
        return n > 0 ? n : 0;
    }

    function ricalcola() {
        const n = notti();
        const opt = tipologia.selectedOptions[0];
        const prezzo = opt ? parseFloat(opt.dataset.prezzo || 0) : 0;
        const soggiorno = n * prezzo;

        let tassa = 0;
        if (tassaCfg.attiva) {
            const nottiTassabili = Math.min(n, tassaCfg.max_notti ?? n);
            tassa = (parseInt(adulti.value || 0, 10)) * (tassaCfg.importo_per_persona_notte || 0) * nottiTassabili;
        }
        const totale = soggiorno + tassa;
        const caparraSuggerita = Math.round(totale * 0.30 * 100) / 100;

        const euro = v => '€ ' + v.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        $('riepilogo-notti-label').textContent = n + (n === 1 ? ' notte × €' + prezzo.toFixed(2) : ' notti × €' + prezzo.toFixed(2));
        $('riepilogo-soggiorno').textContent = euro(soggiorno);
        $('riepilogo-tassa').textContent = euro(tassa);
        $('riepilogo-totale').textContent = euro(totale);
        caparraInput.placeholder = 'Es. ' + euro(caparraSuggerita) + ' (opzionale)';
    }

    // Funzione per il pulsante 30% (ora è dentro lo stesso scope, non va più in errore)
    $('btn-caparra-30').addEventListener('click', function() {
        let totaleStr = $('riepilogo-totale').textContent;
        let totale = parseFloat(totaleStr.replace('€', '').trim().replace('.', '').replace(',', '.')) || 0;
        let caparra30 = (totale * 0.30).toFixed(2);
        caparraInput.value = caparra30;
    });

    function popolaCameraManuale(elenco) {
        cameraSel.innerHTML = elenco.length
            ? elenco.map(c => `<option value="${c.id}"${String(c.id) === cameraDesiderata ? ' selected' : ''}>Camera ${c.numero}</option>`).join('')
            : '<option value="">Nessuna camera libera per queste date</option>';
    }

    function aggiornaModoAssegnazione() {
        blocco.classList.toggle('hidden', automatica.checked);
        cameraSel.required = ! automatica.checked;
    }

    let timer = null;
    function controllaDisponibilita() {
        clearTimeout(timer);
        const tipologiaId = tipologia.value;

        if (! tipologiaId) {
            msgDisponibilita.textContent = '';
            popolaCameraManuale(camerePerTipologiaStatico[''] || []);
            return;
        }
        if (notti() === 0) {
            msgDisponibilita.textContent = '';
            popolaCameraManuale(camerePerTipologiaStatico[tipologiaId] || []);
            return;
        }

        msgDisponibilita.textContent = 'Verifica disponibilità…';
        msgDisponibilita.className = 'text-xs mt-1.5 text-slate-400';

        timer = setTimeout(() => {
            const url = urlDisponibilita + '?tipologia_camera_id=' + encodeURIComponent(tipologiaId)
                + '&data_arrivo=' + encodeURIComponent(arrivo.value)
                + '&data_partenza=' + encodeURIComponent(partenza.value);

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(dati => {
                    if (! dati.pronto) { msgDisponibilita.textContent = ''; return; }

                    popolaCameraManuale(dati.camere);

                    if (dati.camere.length === 0) {
                        const nomeTipologia = tipologia.selectedOptions[0]?.textContent.split(' · ')[0] ?? 'questo tipo';
                        msgDisponibilita.textContent = 'Nessuna camera di tipo "' + nomeTipologia + '" libera per queste date.';
                        msgDisponibilita.className = 'text-xs mt-1.5 text-[#B5563C] font-medium';
                    } else {
                        msgDisponibilita.textContent = dati.camere.length + (dati.camere.length === 1 ? ' camera libera' : ' camere libere') + ' per queste date.';
                        msgDisponibilita.className = 'text-xs mt-1.5 text-okGreen font-medium';
                    }
                })
                .catch(() => { msgDisponibilita.textContent = ''; });
        }, 300);
    }

    [arrivo, partenza, adulti, tipologia].forEach(el => el.addEventListener('input', ricalcola));
    [arrivo, partenza, tipologia].forEach(el => el.addEventListener('input', controllaDisponibilita));
    automatica.addEventListener('change', aggiornaModoAssegnazione);
    cameraSel.addEventListener('change', () => { cameraDesiderata = cameraSel.value; });

    arrivo.addEventListener('change', () => {
        if (!partenza.value) {
            const a = new Date(arrivo.value);
            a.setDate(a.getDate() + 1);
            partenza.value = a.toISOString().slice(0, 10);
            ricalcola();
            controllaDisponibilita();
        }
    });

    aggiornaModoAssegnazione();
    ricalcola();
    controllaDisponibilita();
})();

// --- RICERCA OSPITE INTELLIGENTE ---
(function () {
    const inputRicerca = document.getElementById('cerca-ospite');
    const risultatoBox = document.getElementById('risultati-ricerca');
    const inputNome = document.getElementById('ospite_nome');
    const inputCognome = document.getElementById('ospite_cognome');
    const inputEmail = document.getElementById('ospite_email');
    const inputTelefono = document.getElementById('ospite_telefono');
    const msgOspite = document.getElementById('msg-ospite-esistente');
    
    let timer;

    inputRicerca.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        
        if (q.length < 2) {
            risultatoBox.classList.add('hidden');
            return;
        }

        timer = setTimeout(() => {
            fetch('{{ route('ospiti.cerca') }}?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(dati => {
                risultatoBox.innerHTML = '';
                if (dati.length === 0) {
                    risultatoBox.innerHTML = '<div class="p-3 text-sm text-slate-500">Nessun ospite trovato.</div>';
                } else {
                    dati.forEach(ospite => {
                        const div = document.createElement('div');
                        div.className = 'p-3 hover:bg-slate-50 cursor-pointer text-sm border-b border-gray-100 last:border-0';
                        div.innerHTML = `<strong>${ospite.cognome} ${ospite.nome}</strong><br><span class="text-xs text-slate-500">${ospite.email || 'Nessuna email'} · ${ospite.telefono || 'Nessun telefono'}</span>`;
                        
                        div.addEventListener('click', () => {
                            inputNome.value = ospite.nome;
                            inputCognome.value = ospite.cognome;
                            inputEmail.value = ospite.email || '';
                            inputTelefono.value = ospite.telefono || '';
                            
                            // Blocca i campi per evitare modifiche involontarie
                            inputNome.readOnly = true;
                            inputCognome.readOnly = true;
                            inputEmail.readOnly = true;
                            inputTelefono.readOnly = true;
                            
                            // Mostra un messaggio di conferma
                            msgOspite.innerHTML = `<span class="text-emerald-600 font-semibold">Ospite collegato: ${ospite.cognome} ${ospite.nome}</span>`;
                            msgOspite.className = 'text-xs text-slate-400';
                            
                            // Pulisci la ricerca
                            inputRicerca.value = '';
                            risultatoBox.classList.add('hidden');
                        });
                        
                        risultatoBox.appendChild(div);
                    });
                }
                risultatoBox.classList.remove('hidden');
            });
        }, 300);
    });

    // Chiudi il menu se si clicca altrove
    document.addEventListener('click', function (e) {
        if (!inputRicerca.contains(e.target) && !risultatoBox.contains(e.target)) {
            risultatoBox.classList.add('hidden');
        }
    });
})();
</script>
@endpush
@endsection