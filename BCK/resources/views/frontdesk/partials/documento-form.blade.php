{{--
    Modulo documento di UNA persona: tipo, numero e scansione (drag & drop).
    Variabili: $prenotazione, $ospite, $torna ('checkin' | 'scheda'), $annullabile (default true: mostra "Annulla" se il modulo si apre da un pulsante Modifica)
--}}
@php
    $mio = (string) old('_ospite') === (string) $ospite->id;   // gli errori di validazione valgono solo per questa persona
    $tipoAttuale = $mio ? old('tipo_documento') : $ospite->tipo_documento;
    $numeroAttuale = $mio ? old('numero_documento') : $ospite->numero_documento;
    $luogoRilascioAttuale = $mio ? old('luogo_rilascio') : ($ospite->luogo_rilascio ?? $ospite->ente_rilascio);
    $provRilascioAttuale  = $mio ? old('luogo_rilascio_provincia') : $ospite->luogo_rilascio_provincia;
    $scadenzaAttuale = $mio ? old('scadenza_documento') : $ospite->scadenza_documento?->format('Y-m-d');
@endphp

<form method="POST" action="{{ route('prenotazioni.ospiti.documento', [$prenotazione, $ospite]) }}"
      enctype="multipart/form-data" data-salva-una-volta class="space-y-3">
    @csrf
    <input type="hidden" name="torna" value="{{ $torna }}">
    <input type="hidden" name="_ospite" value="{{ $ospite->id }}">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <select name="tipo_documento" required aria-label="Tipo documento"
                class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white">
            <option value="">Tipo documento</option>
            @foreach (['carta_identita' => "Carta d'identità", 'passaporto' => 'Passaporto', 'patente' => 'Patente'] as $valore => $etichetta)
                <option value="{{ $valore }}" @selected($tipoAttuale === $valore)>{{ $etichetta }}</option>
            @endforeach
        </select>
        <input name="numero_documento" placeholder="Numero documento" required maxlength="50" aria-label="Numero documento"
               value="{{ $numeroAttuale }}" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
        <input name="luogo_rilascio" placeholder="Luogo di rilascio (comune o stato)" maxlength="100" aria-label="Luogo di rilascio"
               value="{{ $luogoRilascioAttuale }}" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
        <input name="luogo_rilascio_provincia" placeholder="Provincia (solo IT, es. MI)" maxlength="2" aria-label="Provincia di rilascio"
               value="{{ $provRilascioAttuale }}" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm uppercase">
        <input type="date" name="scadenza_documento" aria-label="Scadenza documento" title="Scadenza documento (controllo interno)"
               value="{{ $scadenzaAttuale }}" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
    </div>

    <div>
        <div class="dropzone" data-dropzone tabindex="0" role="button"
             aria-label="Carica il documento: trascina qui un file oppure premi Invio per sceglierlo">
            <input class="dropzone__input" type="file" name="file" tabindex="-1"
                   accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">

            {{-- stato vuoto --}}
            <div class="dropzone__vuoto">
                <span class="dropzone__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 16V4"/><path d="M7 9l5-5 5 5"/><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                    </svg>
                </span>
                <span class="dropzone__testo">
                    <span class="block dropzone__titolo">Trascina qui il documento oppure <u>sfoglia</u></span>
                    <span class="block dropzone__aiuto">
                        {{ $ospite->documento_file ? 'Facoltativo: carica un nuovo file per sostituire la scansione attuale · ' : 'Da telefono puoi scattare una foto · ' }}JPG, PNG o PDF, max 5 MB
                    </span>
                </span>
            </div>

            {{-- stato con file scelto --}}
            <div class="dropzone__file">
                <img class="dropzone__anteprima" data-dz-anteprima alt="Anteprima documento" hidden>
                <span class="dropzone__pdf" data-dz-pdf hidden>PDF</span>
                <span class="dropzone__testo">
                    <span class="block dropzone__nome" data-dz-nome></span>
                    <span class="block dropzone__peso" data-dz-peso></span>
                </span>
                <button type="button" class="dropzone__rimuovi" data-dz-rimuovi>Rimuovi</button>
            </div>
        </div>
        <p class="dropzone__errore" data-dz-errore hidden></p>
    </div>

    <div class="flex items-center justify-between gap-3">
        <p class="text-xs text-slate-400">
            Il file è un allegato: nessuna lettura automatica dei dati, va controllato a vista.
            @if ($ospite->documento_file)
                <a href="{{ asset('storage/' . $ospite->documento_file) }}" target="_blank" class="text-fdBlue hover:underline">Vedi scansione attuale</a>
            @endif
        </p>
        <div class="flex items-center gap-2 shrink-0">
            @if ($ospite->numero_documento && ($annullabile ?? true))
                <button type="button" data-nascondi="documento-{{ $ospite->id }}" class="text-sm font-medium text-slate-500 hover:text-deepNavy px-3 py-2">Annulla</button>
            @endif
            <button type="submit" class="bg-fdBlue hover:opacity-90 text-white text-sm font-medium px-5 py-2.5 rounded-lg">Salva</button>
        </div>
    </div>
</form>

@once
    @push('scripts')
        <script src="{{ asset('js/dropzone.js') }}"></script>
    @endpush
@endonce
