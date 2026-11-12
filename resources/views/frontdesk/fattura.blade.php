@php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.');
    $dataEmissione = \Carbon\Carbon::parse($fattura->data_emissione);
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Fattura ' . $fattura->numero)
@section('page_title', 'Fattura ' . $fattura->numero)
@section('page_color', '#4F86B0')

@section('content')

<div class="no-stampa flex items-center justify-between gap-4 mb-5">
    <a href="{{ route('prenotazioni.folio', $prenotazione) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy">&larr; Torna al folio</a>
    <button type="button" id="btn-stampa" class="bg-fdBlue hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Stampa / salva PDF</button>
</div>

@include('frontdesk.partials.messaggi')

<div class="fattura bg-white rounded-2xl border border-gray-200 p-8">

    <div class="flex flex-wrap justify-between gap-8 mb-8">
        <div class="text-[15px] leading-relaxed">
            <p class="text-lg font-medium">{{ $emittente['ragione_sociale'] ?? ($hotel['nome'] ?? 'Hotel') }}</p>
            <p>{{ $emittente['indirizzo'] ?? '' }}@if (! empty($emittente['cap'])) · {{ $emittente['cap'] }} {{ $emittente['citta'] ?? '' }} {{ ! empty($emittente['provincia']) ? '(' . $emittente['provincia'] . ')' : '' }}@endif</p>
            @if (! empty($emittente['partita_iva']))<p>P.IVA {{ $emittente['partita_iva'] }}@if (! empty($emittente['codice_fiscale'])) · C.F. {{ $emittente['codice_fiscale'] }}@endif</p>@endif
            @if (! empty($emittente['pec']))<p class="text-slate-500">PEC {{ $emittente['pec'] }}</p>@endif
        </div>
        <div class="text-[15px] leading-relaxed sm:text-right">
            <p class="text-2xl font-medium">Fattura n. {{ $fattura->numero }}</p>
            <p class="text-slate-500">del {{ $dataEmissione->format('d/m/Y') }}</p>
            <p class="text-slate-500">Prenotazione {{ $prenotazione->codice }}</p>
        </div>
    </div>

    <div class="mb-8 text-[15px] leading-relaxed">
        <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Intestata a</p>
        <p class="font-medium">{{ $fattura->intestatario }}</p>
        @if ($fattura->indirizzo)<p>{{ $fattura->indirizzo }}</p>@endif
        @if ($fattura->partita_iva)<p>P.IVA {{ $fattura->partita_iva }}</p>@endif
        @if ($fattura->codice_fiscale)<p>C.F. {{ $fattura->codice_fiscale }}</p>@endif
        @if ($fattura->codice_destinatario)<p class="text-slate-500">Codice destinatario {{ $fattura->codice_destinatario }}</p>@endif
        @if ($fattura->pec)<p class="text-slate-500">PEC {{ $fattura->pec }}</p>@endif
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-[15px] border-collapse">
            <thead>
                <tr class="text-left text-slate-500 border-b border-gray-200">
                    <th class="py-2 font-normal">Descrizione</th>
                    <th class="py-2 font-normal text-right">IVA</th>
                    <th class="py-2 font-normal text-right">Importo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($righe as $r)
                    <tr class="border-b border-gray-100">
                        <td class="py-2.5">{{ $r->descrizione }}</td>
                        <td class="py-2.5 text-right text-slate-500">{{ $r->aliquota > 0 ? rtrim(rtrim(number_format($r->aliquota, 2, ',', ''), '0'), ',') . '%' : 'F.c. IVA' }}</td>
                        <td class="py-2.5 text-right">€ {{ $eur($r->lordo) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex justify-end mt-6">
        <dl class="w-full sm:w-96 text-[15px] space-y-1.5">
            @foreach ($riepilogoIva as $i)
                <div class="flex justify-between text-slate-500">
                    <dt>{{ $i->aliquota > 0 ? 'Imponibile ' . rtrim(rtrim(number_format($i->aliquota, 2, ',', ''), '0'), ',') . '%' : 'Fuori campo IVA (art. 15 DPR 633/72)' }}</dt>
                    <dd>€ {{ $eur($i->imponibile) }}</dd>
                </div>
                @if ($i->aliquota > 0)
                    <div class="flex justify-between text-slate-500"><dt>IVA {{ rtrim(rtrim(number_format($i->aliquota, 2, ',', ''), '0'), ',') }}%</dt><dd>€ {{ $eur($i->imposta) }}</dd></div>
                @endif
            @endforeach
            <div class="flex justify-between text-lg font-medium border-t border-gray-100 pt-3"><dt>Totale fattura</dt><dd>€ {{ $eur($totale) }}</dd></div>
            @if ($incassato > 0)
                <div class="flex justify-between text-okGreen"><dt>Già incassato</dt><dd>- € {{ $eur($incassato) }}</dd></div>
            @endif
            <div class="bg-[#EAF0F8] rounded-xl px-4 py-3 flex justify-between items-center text-lg font-medium"><dt>Da pagare</dt><dd>€ {{ $eur($daPagare) }}</dd></div>
        </dl>
    </div>

    @if (! empty($emittente['iban']))
        <p class="mt-8 text-sm text-slate-500">Pagamento a mezzo bonifico: {{ $emittente['intestatario_conto'] ?? ($emittente['ragione_sociale'] ?? '') }} · {{ $emittente['banca'] ?? '' }} · IBAN {{ $emittente['iban'] }}</p>
    @endif
    <p class="mt-3 text-xs text-slate-400">Copia di cortesia per l'ospite. I prezzi del folio sono considerati IVA inclusa (aliquote da configurazione hotel). L'invio al Sistema di Interscambio non è collegato: la fattura elettronica XML va ancora generata e trasmessa dal gestionale fiscale.</p>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('btn-stampa').addEventListener('click', () => window.print());
</script>
@endpush
