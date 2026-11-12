@php
    app()->setLocale(session('locale', 'it'));
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.');
    $isCredito = fn ($v) => in_array($v->tipo, ['pagamento', 'caparra'], true);
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Folio')
@section('page_title', 'Folio · ' . ($prenotazione->camere->first()->numero ?? '—'))
@section('page_color', '#4F86B0')

@section('content')

<a href="{{ route('prenotazioni.scheda', $prenotazione) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">
    &larr; Torna alla prenotazione
</a>

@include('frontdesk.partials.messaggi')

@unless ($conto)
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <p class="text-slate-600 mb-4">Nessun conto aperto per questa prenotazione.</p>
        <form method="POST" action="{{ route('prenotazioni.folio.apri', $prenotazione) }}">
            @csrf
            <button class="bg-fdBlue hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Apri conto</button>
        </form>
    </div>
@else
    @php $aperto = $conto->stato === 'aperto'; @endphp

    <div class="flex items-center justify-between gap-4 mb-4">
        <p class="text-sm text-slate-500">
            @if ($aperto)
                Trascina le voci da un folio all'altro per dividere il conto (oppure usa la freccia accanto alla voce).
            @else
                Conto chiuso: le voci non si possono più modificare.
            @endif
        </p>
        <span @class(['text-xs font-medium px-2.5 py-1 rounded-full capitalize shrink-0', 'bg-okGreen/15 text-okGreen' => $aperto, 'bg-slate-200 text-slate-600' => ! $aperto])>
            Conto {{ $conto->codice }} · {{ $conto->stato }}
        </span>
    </div>

    {{-- ===== I DUE FOLIO ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach ($riepilogo as $f)
            @php
                $fattura = $fatture->get($f->chiave);
                $bloccato = $fattura && $fattura->stato === 'emessa';
                $trascinabile = $aperto && ! $bloccato;
                $addebiti = $f->voci->reject($isCredito);
                $crediti = $f->voci->filter($isCredito);
                $altro = $f->chiave === 'A' ? 'B' : 'A';
            @endphp

            <div class="bg-white rounded-2xl border border-gray-200 p-6 flex flex-col">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-lg font-medium">Folio {{ $f->chiave }} · {{ $f->nome }}</h2>
                    @if ($bloccato)
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-okGreen text-white">Fattura {{ $fattura->numero }}</span>
                    @elseif ($fattura)
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-warmGold text-white">Fattura differita</span>
                    @endif
                </div>

                <div class="folio-zona space-y-2" data-folio-zona="{{ $f->chiave }}" @if (! $trascinabile) data-bloccato="1" @endif>
                    @foreach ($addebiti as $v)
                        <div @class(['flex items-center justify-between gap-3 bg-slate-50 rounded-lg px-4 py-3 text-[15px]', 'cursor-grab' => $trascinabile])
                             @if ($trascinabile)
                                 draggable="true" data-voce="{{ $v->id }}" data-folio="{{ $f->chiave }}"
                                 data-url="{{ route('prenotazioni.folio.voce.sposta', [$prenotazione, $v->id]) }}"
                             @endif>
                            <span>{{ $v->descrizione }}</span>
                            <span class="flex items-center gap-3 shrink-0">
                                <span>€ {{ $eur($v->importo) }}</span>
                                @if ($trascinabile)
                                    @if ($v->tipo === 'servizio' && ! $f->voci->pluck('riferimento')->contains(fn ($r) => str_starts_with((string) $r, 'storno:' . $v->id)))
                                        <form method="POST" action="{{ route('prenotazioni.folio.voce.storna', [$prenotazione, $v->id]) }}">
                                            @csrf
                                            <button class="text-xs font-medium text-slate-400 hover:text-red-600" title="Storna questa spesa" aria-label="Storna">storna</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('prenotazioni.folio.voce.sposta', [$prenotazione, $v->id]) }}">
                                        @csrf
                                        <input type="hidden" name="folio" value="{{ $altro }}">
                                        <button class="text-xs font-medium text-slate-400 hover:text-fdBlue" title="Sposta nel folio {{ $altro }}" aria-label="Sposta nel folio {{ $altro }}">&rarr; {{ $altro }}</button>
                                    </form>
                                @endif
                            </span>
                        </div>
                    @endforeach

                    @if ($trascinabile)
                        <div class="folio-rilascia">Trascina qui una voce dall'altro folio</div>
                    @elseif ($addebiti->isEmpty())
                        <p class="text-sm text-slate-400 py-2">Nessuna voce.</p>
                    @endif
                </div>

                <div class="mt-auto pt-5">
                    <div class="border-t border-gray-100 pt-4 space-y-1.5 text-[15px]">
                        <div class="flex justify-between"><span class="text-slate-500">Totale</span><span>€ {{ $eur($f->addebiti) }}</span></div>
                        @foreach ($crediti as $v)
                            <div class="flex justify-between text-okGreen"><span>{{ $v->descrizione }}</span><span>- € {{ $eur($v->importo) }}</span></div>
                        @endforeach
                        <div class="flex justify-between text-lg font-medium pt-2"><span>Saldo</span><span>€ {{ $eur($f->saldo) }}</span></div>
                    </div>

                    {{-- ===== FATTURA DEL FOLIO ===== --}}
                    @if ($bloccato)
                        <a href="{{ route('prenotazioni.folio.fattura', [$prenotazione, $f->chiave]) }}"
                           class="inline-block mt-4 bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">
                            Apri fattura {{ $fattura->numero }}
                        </a>
                    @elseif ($f->addebiti > 0)
                        <details class="mt-4 border border-gray-200 rounded-xl p-4">
                            <summary class="cursor-pointer text-sm font-medium">Fatturazione folio {{ $f->chiave }}</summary>
                            <form method="POST" action="{{ route('prenotazioni.folio.fattura.emetti', [$prenotazione, $f->chiave]) }}"
                                  class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @csrf
                                <input name="intestatario" placeholder="Intestatario" maxlength="150"
                                       value="{{ $fattura->intestatario ?? trim($prenotazione->ospite->nome . ' ' . $prenotazione->ospite->cognome) }}"
                                       class="sm:col-span-2 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                <input name="partita_iva" placeholder="Partita IVA" maxlength="20" value="{{ $fattura->partita_iva ?? '' }}"
                                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                <input name="codice_fiscale" placeholder="Codice fiscale" maxlength="20" value="{{ $fattura->codice_fiscale ?? '' }}"
                                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                <input name="codice_destinatario" placeholder="Codice destinatario SDI (7 caratteri)" maxlength="7" value="{{ $fattura->codice_destinatario ?? '' }}"
                                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                <input name="pec" type="email" placeholder="PEC" maxlength="150" value="{{ $fattura->pec ?? '' }}"
                                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                <input name="indirizzo" placeholder="Indirizzo di fatturazione" maxlength="255" value="{{ $fattura->indirizzo ?? '' }}"
                                       class="sm:col-span-2 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                <div class="sm:col-span-2 flex flex-wrap gap-2 pt-1">
                                    <button name="azione" value="differisci"
                                            class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Fattura differita</button>
                                    <button name="azione" value="emetti"
                                            class="bg-fdBlue hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Emetti fattura</button>
                                </div>
                            </form>
                        </details>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== AZIONI SUL CONTO ===== --}}
    @if ($aperto)
        <div class="flex flex-wrap items-center gap-2 mt-6">
            <form method="POST" action="{{ route('prenotazioni.folio.unisci', $prenotazione) }}">
                @csrf
                <button class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Unisci conti</button>
            </form>
            <form method="POST" action="{{ route('prenotazioni.folio.chiudi', $prenotazione) }}">
                @csrf
                <button class="bg-white border border-gray-200 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">Chiudi conto</button>
            </form>
            <span class="ml-auto text-lg font-medium">Saldo totale € {{ $eur($saldo) }}</span>
        </div>

        {{-- ===== AGGIUNGI VOCE / REGISTRA PAGAMENTO ===== --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="text-base font-medium mb-3">Aggiungi una spesa al conto</h2>
                <div class="flex flex-wrap gap-2 mb-3">
                    @forelse ($listino as $a)
                        <button type="button" data-preset="{{ $a->nome }}" data-prezzo="{{ $a->prezzo }}"
                                class="text-xs font-medium px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600">{{ $a->nome }} · € {{ $eur($a->prezzo) }}</button>
                    @empty
                        @foreach (['Cena Bistro', 'Colazione', 'Lavanderia', 'Parcheggio'] as $preset)
                            <button type="button" data-preset="{{ $preset }}"
                                    class="text-xs font-medium px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600">{{ $preset }}</button>
                        @endforeach
                        <a href="{{ route('minibar.listino') }}" class="text-xs font-medium px-3 py-1.5 text-fdBlue underline">Crea il listino minibar</a>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('prenotazioni.folio.voce', $prenotazione) }}" class="flex flex-wrap gap-2">
                    @csrf
                    <input name="descrizione" id="voce-descrizione" placeholder="Descrizione" required maxlength="255"
                           class="flex-1 min-w-[160px] border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <input name="importo" id="voce-importo" type="number" step="0.01" min="0.01" placeholder="€" required
                           class="w-28 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <select name="folio" class="border border-gray-200 rounded-lg px-3 py-2 text-sm" aria-label="Folio">
                        <option value="B">Folio B · Extra</option>
                        <option value="A">Folio A · Ospite</option>
                    </select>
                    <button class="bg-fdBlue hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Aggiungi</button>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="text-base font-medium mb-4">Registra pagamento</h2>
                <form method="POST" action="{{ route('prenotazioni.folio.pagamento', $prenotazione) }}" class="flex flex-wrap gap-2">
                    @csrf
                    <select name="folio" class="border border-gray-200 rounded-lg px-3 py-2 text-sm" aria-label="Folio">
                        <option value="A">Folio A · Ospite</option>
                        <option value="B">Folio B · Extra</option>
                    </select>
                    <select name="metodo" class="border border-gray-200 rounded-lg px-3 py-2 text-sm" aria-label="Metodo">
                        <option value="carta">Carta</option>
                        <option value="contanti">Contanti</option>
                        <option value="bonifico">Bonifico</option>
                    </select>
                    <input name="importo" type="number" step="0.01" min="0.01" placeholder="€" required
                           class="w-28 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <button class="bg-okGreen hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">Registra</button>
                </form>
            </div>
        </div>
    @endif
@endunless
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('[data-preset]').forEach(b => b.addEventListener('click', () => {
        const d = document.getElementById('voce-descrizione');
        if (!d) return;
        d.value = b.dataset.preset;
        const imp = document.getElementById('voce-importo');
        if (b.dataset.prezzo) imp.value = b.dataset.prezzo;
        imp.focus();
    }));
})();
(function () {
    const zone = [...document.querySelectorAll('[data-folio-zona]')];
    if (!zone.length) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let trascinata = null;

    function toast(msg) {
        const t = document.createElement('div');
        t.textContent = msg;
        t.className = 'toast is-errore';
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 4000);
    }
    const pulisci = () => zone.forEach(z => z.classList.remove('folio-drop-attiva'));

    document.querySelectorAll('[data-voce]').forEach(el => {
        el.addEventListener('dragstart', e => {
            trascinata = el;
            el.classList.add('voce-trascinata');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', el.dataset.voce);
        });
        el.addEventListener('dragend', () => {
            el.classList.remove('voce-trascinata');
            pulisci();
            trascinata = null;
        });
    });

    zone.forEach(z => {
        const valida = () => trascinata && !z.dataset.bloccato && trascinata.dataset.folio !== z.dataset.folioZona;

        z.addEventListener('dragover', e => {
            if (!valida()) return;
            e.preventDefault();
            z.classList.add('folio-drop-attiva');
        });
        z.addEventListener('dragleave', e => {
            if (!z.contains(e.relatedTarget)) z.classList.remove('folio-drop-attiva');
        });
        z.addEventListener('drop', e => {
            if (!valida()) return;
            e.preventDefault();
            const voce = trascinata;
            pulisci();
            fetch(voce.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                           'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ folio: z.dataset.folioZona })
            })
            .then(async r => {
                const j = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(j.message || 'Operazione non riuscita');
                location.reload();
            })
            .catch(err => toast(err.message));
        });
    });
})();
</script>
@endpush
