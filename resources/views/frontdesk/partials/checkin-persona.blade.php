{{--
    Campi di UNA persona nel check-in: anagrafica, documento (con scansione) e privacy, tutto in un blocco.
    Fanno parte del form unico della pagina di check-in: non hanno un loro pulsante Salva.

    Variabili:
      $prefisso  nome dei campi nel form, es. "ospiti[12]" (persona esistente) o "nuovi[0]" (persona da registrare)
      $chiave    lo stesso in notazione a punti, per old() ed errori: "ospiti.12" / "nuovi.0"
      $o         l'ospite (null per una persona ancora da registrare)
      $bambino   true se è un bambino: documento facoltativo, privacy non richiesta
      $nuovo     true per una persona ancora da registrare (nome e cognome non obbligatori finché non ne inserisci uno)
--}}
@php
    $campo = 'w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm';
    $etichetta = 'block text-xs text-slate-500 mb-1';
    $val = fn (string $nome, $default = null) => old($chiave . '.' . $nome, $default);
    $err = fn (string $nome) => $errors->first($chiave . '.' . $nome);

    $tipiDocumento = ['carta_identita' => "Carta d'identità", 'passaporto' => 'Passaporto', 'patente' => 'Patente'];
    $tipoAttuale = $val('tipo_documento', $o?->tipo_documento);
    $privacyAccettata = (bool) ($o?->privacy_accettata);
    $ieri = now()->subDay()->format('Y-m-d');
@endphp

<div class="space-y-4">

    {{-- Anagrafica (tutti i campi richiesti da Alloggiati Web) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="{{ $etichetta }}">Nome</label>
            <input name="{{ $prefisso }}[nome]" value="{{ $val('nome', $o?->nome) }}" maxlength="100"
                   @required(! $nuovo) class="{{ $campo }}">
            @if ($err('nome'))<p class="text-xs text-[#B5563C] mt-1">{{ $err('nome') }}</p>@endif
        </div>
        <div>
            <label class="{{ $etichetta }}">Cognome</label>
            <input name="{{ $prefisso }}[cognome]" value="{{ $val('cognome', $o?->cognome) }}" maxlength="100"
                   @required(! $nuovo) class="{{ $campo }}">
            @if ($err('cognome'))<p class="text-xs text-[#B5563C] mt-1">{{ $err('cognome') }}</p>@endif
        </div>
        <div>
            <label class="{{ $etichetta }}">Sesso</label>
            <select name="{{ $prefisso }}[sesso]" class="{{ $campo }} bg-white">
                <option value="">—</option>
                <option value="M" @selected($val('sesso', $o?->sesso) === 'M')>Maschio</option>
                <option value="F" @selected($val('sesso', $o?->sesso) === 'F')>Femmina</option>
            </select>
            @if ($err('sesso'))<p class="text-xs text-[#B5563C] mt-1">{{ $err('sesso') }}</p>@endif
        </div>
        <div>
            <label class="{{ $etichetta }}">Data di nascita</label>
            <input type="date" name="{{ $prefisso }}[data_nascita]" max="{{ $ieri }}"
                   value="{{ $val('data_nascita', $o?->data_nascita?->format('Y-m-d')) }}" class="{{ $campo }}">
            @if ($err('data_nascita'))<p class="text-xs text-[#B5563C] mt-1">{{ $err('data_nascita') }}</p>@endif
        </div>
        <div>
            <label class="{{ $etichetta }}">Luogo di nascita <span class="text-slate-400">(comune o stato)</span></label>
            <input name="{{ $prefisso }}[luogo_nascita]" value="{{ $val('luogo_nascita', $o?->luogo_nascita) }}"
                   maxlength="100" placeholder="Es. Milano oppure Francia" class="{{ $campo }}">
            @if ($err('luogo_nascita'))<p class="text-xs text-[#B5563C] mt-1">{{ $err('luogo_nascita') }}</p>@endif
        </div>
        <div>
            <label class="{{ $etichetta }}">Provincia di nascita <span class="text-slate-400">(solo Italia)</span></label>
            <input name="{{ $prefisso }}[luogo_nascita_provincia]" value="{{ $val('luogo_nascita_provincia', $o?->luogo_nascita_provincia) }}"
                   maxlength="2" placeholder="MI" pattern="[A-Za-z]{2}" class="{{ $campo }} uppercase">
            @if ($err('luogo_nascita_provincia'))<p class="text-xs text-[#B5563C] mt-1">{{ $err('luogo_nascita_provincia') }}</p>@endif
        </div>
        <div>
            <label class="{{ $etichetta }}">Cittadinanza <span class="text-slate-400">(codice di 2 lettere)</span></label>
            <input name="{{ $prefisso }}[nazionalita]" value="{{ $val('nazionalita', $o?->nazionalita) }}" maxlength="2"
                   placeholder="IT" pattern="[A-Za-z]{2}" class="{{ $campo }} uppercase">
            @if ($err('nazionalita'))<p class="text-xs text-[#B5563C] mt-1">{{ $err('nazionalita') }}</p>@endif
        </div>
    </div>

    {{-- Documento (in app obbligatorio per gli adulti; per i minori facoltativo) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="{{ $etichetta }}">{{ $bambino ? 'Documento (facoltativo)' : "Documento d'identità" }}</label>
            <select name="{{ $prefisso }}[tipo_documento]" aria-label="Tipo documento" class="{{ $campo }} bg-white">
                <option value="">Tipo documento</option>
                @foreach ($tipiDocumento as $valore => $nomeTipo)
                    <option value="{{ $valore }}" @selected($tipoAttuale === $valore)>{{ $nomeTipo }}</option>
                @endforeach
            </select>
            @if ($err('tipo_documento'))<p class="text-xs text-[#B5563C] mt-1">{{ $err('tipo_documento') }}</p>@endif
        </div>
        <div>
            <label class="{{ $etichetta }}">Numero documento</label>
            <input name="{{ $prefisso }}[numero_documento]" value="{{ $val('numero_documento', $o?->numero_documento) }}"
                   maxlength="50" class="{{ $campo }}">
            @if ($err('numero_documento'))<p class="text-xs text-[#B5563C] mt-1">{{ $err('numero_documento') }}</p>@endif
        </div>
        <div>
            <label class="{{ $etichetta }}">Luogo di rilascio <span class="text-slate-400">(comune o stato)</span></label>
            <input name="{{ $prefisso }}[luogo_rilascio]" value="{{ $val('luogo_rilascio', $o?->luogo_rilascio ?? $o?->ente_rilascio) }}"
                   maxlength="100" placeholder="Es. Milano oppure Francia" class="{{ $campo }}">
            @if ($err('luogo_rilascio'))<p class="text-xs text-[#B5563C] mt-1">{{ $err('luogo_rilascio') }}</p>@endif
        </div>
        <div>
            <label class="{{ $etichetta }}">Provincia di rilascio <span class="text-slate-400">(solo Italia)</span></label>
            <input name="{{ $prefisso }}[luogo_rilascio_provincia]" value="{{ $val('luogo_rilascio_provincia', $o?->luogo_rilascio_provincia) }}"
                   maxlength="2" placeholder="MI" pattern="[A-Za-z]{2}" class="{{ $campo }} uppercase">
            @if ($err('luogo_rilascio_provincia'))<p class="text-xs text-[#B5563C] mt-1">{{ $err('luogo_rilascio_provincia') }}</p>@endif
        </div>
        <div>
            <label class="{{ $etichetta }}">Scadenza documento <span class="text-slate-400">(controllo interno)</span></label>
            <input type="date" name="{{ $prefisso }}[scadenza_documento]"
                   value="{{ $val('scadenza_documento', $o?->scadenza_documento?->format('Y-m-d')) }}" class="{{ $campo }}">
            @if ($err('scadenza_documento'))<p class="text-xs text-[#B5563C] mt-1">{{ $err('scadenza_documento') }}</p>@endif
        </div>
    </div>

    <div>
        <div class="dropzone" data-dropzone tabindex="0" role="button"
             aria-label="Carica il documento: trascina qui un file oppure premi Invio per sceglierlo">
            <input class="dropzone__input" type="file" name="{{ $prefisso }}[file]" tabindex="-1"
                   accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">

            {{-- stato vuoto --}}
            <div class="dropzone__vuoto">
                <span class="dropzone__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 16V4"/><path d="M7 9l5-5 5 5"/><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                    </svg>
                </span>
                <span class="dropzone__testo">
                    <span class="block dropzone__titolo">Scansione del documento: trascina qui il file oppure <u>sfoglia</u></span>
                    <span class="block dropzone__aiuto">
                        {{ $o?->documento_file ? 'Facoltativo: carica un nuovo file per sostituire la scansione attuale · ' : 'Da telefono puoi scattare una foto · ' }}JPG, PNG o PDF, max 5 MB
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
        @if ($err('file'))<p class="text-xs text-[#B5563C] mt-1">{{ $err('file') }}</p>@endif
        @if ($o?->documento_file)
            <p class="text-xs text-slate-400 mt-1">
                Scansione già caricata: <a href="{{ asset('storage/' . $o->documento_file) }}" target="_blank" class="text-fdBlue hover:underline">vedi scansione attuale</a>
            </p>
        @endif
    </div>

    {{-- Privacy (la firma l'adulto, per i bambini non serve) --}}
    @unless ($bambino)
        @if ($privacyAccettata)
            <p class="text-sm text-okGreen font-medium">
                Informativa privacy accettata il {{ $o->privacy_accettata_il?->format('d/m/Y H:i') }}
            </p>
        @else
            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" name="{{ $prefisso }}[privacy]" value="1" data-privacy
                       @checked($val('privacy')) class="w-4 h-4 rounded border-gray-300">
                <span>Informativa privacy letta e accettata</span>
            </label>
        @endif
    @endunless
</div>
