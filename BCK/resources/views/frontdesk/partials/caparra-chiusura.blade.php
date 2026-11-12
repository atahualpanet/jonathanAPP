{{--
    Cosa fare della caparra quando si annulla una prenotazione o si segna il no-show.
    Si mostra solo se la prenotazione ha una caparra. Va dentro il form di annullamento / no-show.
    Variabili: $prenotazione, $prefisso (id univoco dei campi nella pagina, es. "annulla" / "noshow"), $campo (classi dell'input)
--}}
@php
    $importoCaparra = round((float) $prenotazione->importo_caparra, 2);
    $esitoAttuale = old('caparra_esito');
@endphp

@if ($importoCaparra > 0)
    <fieldset class="space-y-2 rounded-lg border border-gray-200 p-3"
              x-data="{ esito: @js($esitoAttuale) }">
        <legend class="px-1 text-sm text-slate-500">
            Caparra versata: € {{ number_format($importoCaparra, 2, ',', '.') }}
        </legend>

        <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="radio" name="caparra_esito" value="rimborsa" x-model="esito" required
                   class="w-4 h-4 border-gray-300">
            <span>Rimborsa tutta la caparra all'ospite</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="radio" name="caparra_esito" value="trattieni" x-model="esito"
                   class="w-4 h-4 border-gray-300">
            <span>Trattieni tutta la caparra</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="radio" name="caparra_esito" value="parziale" x-model="esito"
                   class="w-4 h-4 border-gray-300">
            <span>Trattieni solo una parte, il resto si rimborsa</span>
        </label>

        <div x-show="esito === 'parziale'" x-cloak class="pt-1">
            <label for="{{ $prefisso }}-trattenuta" class="block text-xs text-slate-500 mb-1">Importo da trattenere (€)</label>
            <input type="number" step="0.01" min="0" max="{{ $importoCaparra }}"
                   name="caparra_trattenuta" id="{{ $prefisso }}-trattenuta"
                   value="{{ old('caparra_trattenuta') }}"
                   :required="esito === 'parziale'" :disabled="esito !== 'parziale'"
                   class="{{ $campo }}">
        </div>

        <p class="text-xs text-slate-400">
            Il sistema registra l'esito (sulla prenotazione e nel conto, se aperto): la restituzione vera e propria dei soldi resta da fare a parte.
        </p>
    </fieldset>
@endif
