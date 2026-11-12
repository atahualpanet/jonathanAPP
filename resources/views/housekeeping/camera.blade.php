@extends('layouts.mobile')

@section('title', 'Camera ' . $camera->numero)

@section('content')
@php
    $totale = count($checklist);
    $fatte = count(array_intersect(array_keys($checklist), $voci));
    $percento = $totale > 0 ? round($fatte / $totale * 100) : 0;
    $pulita = ! $aperta && $sit->stato === 'pulita';

    if ($fuoriServizio) { $etichetta = 'Fuori servizio'; $classe = 'bg-danger text-white'; }
    elseif ($aperta)    { $etichetta = 'In corso';       $classe = 'bg-fdBlue text-white'; }
    elseif ($pulita)    { $etichetta = 'Pulita';         $classe = 'bg-okGreen text-white'; }
    else                { $etichetta = 'Da pulire';      $classe = 'bg-slateBlue text-white'; }

    $attiva = $aperta;                       // checklist modificabile solo a pulizia iniziata
    $tuttoFatto = $fatte === $totale;
    $puoCompletare = $aperta && (! $obbligatoria || $tuttoFatto);
@endphp

<header class="px-6 pb-4 bg-white border-b border-gray-200 pt-[max(1.5rem,env(safe-area-inset-top,0px))]">
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <a href="{{ route('hk.piano') }}" aria-label="Torna al piano" class="mt-1 text-deepNavy">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-medium leading-tight">Camera {{ $camera->numero }}</h1>
                <p class="text-sm text-slateBlue">{{ $camera->tipologia->nome ?? '' }}@if ($camera->tipologia) · @endif{{ $sit->tipo_etichetta }}</p>
            </div>
        </div>
        <span id="badge-stato" class="px-3 h-7 inline-flex items-center rounded-full text-xs font-medium {{ $classe }}">{{ $etichetta }}</span>
    </div>
</header>

<main class="flex-1 px-6 py-5">

    @if ($errors->any())
        <div class="mb-4 bg-danger/10 border border-danger/30 text-danger text-sm font-medium px-4 py-2.5 rounded-lg">{{ $errors->first() }}</div>
    @endif

    @if ($fuoriServizio)
        <div class="bg-white rounded-xl border border-gray-200 p-5 text-sm text-slateBlue">Questa camera è fuori servizio: non c'è nulla da pulire. Per eventuali dubbi chiedi alla reception.</div>
    @else
        <p class="text-[15px] font-medium"><span id="n-fatte">{{ $fatte }}</span> di {{ $totale }} attività</p>
        <div class="mt-2 h-2 rounded-full bg-gray-200 overflow-hidden">
            <div id="barra" class="h-full rounded-full bg-hk transition-all w-[{{ (int) $percento }}%]"></div>
        </div>

        <div class="mt-4 bg-white rounded-xl border border-gray-200 divide-y divide-gray-100 {{ $attiva || $pulita ? '' : 'opacity-60' }}">
            @foreach ($checklist as $chiave => $testo)
                <label class="flex items-center gap-3 px-4 py-3.5 {{ $attiva ? 'cursor-pointer' : '' }}">
                    <input type="checkbox" data-voce="{{ $chiave }}" @checked(in_array($chiave, $voci, true)) @disabled(! $attiva)
                           class="w-6 h-6 rounded-md accent-[#7E5A86]">
                    <span class="text-[15px]">{{ $testo }}</span>
                </label>
            @endforeach
        </div>

        @if ($sit->arrivo_oggi)
            <div class="mt-4 bg-hkSoft text-hk text-sm font-medium rounded-xl px-4 py-3.5">Arrivo ospite oggi</div>
        @endif

        {{-- azioni di camera: danno e minibar arrivano nei prossimi passi --}}
        <div class="mt-4 grid grid-cols-3 gap-3">
            <div class="bg-white rounded-xl border border-gray-200 py-3 flex flex-col items-center gap-1 opacity-40" aria-disabled="true" title="In arrivo">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#7E5A86" stroke-width="1.8"><path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg>
                <span class="text-xs">Segnala danno</span>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 py-3 flex flex-col items-center gap-1 opacity-40" aria-disabled="true" title="In arrivo">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#7E5A86" stroke-width="1.8"><rect x="8" y="7" width="8" height="14" rx="2"/><path d="M10 7V4h4v3"/></svg>
                <span class="text-xs">Minibar</span>
            </div>
            <button type="button" id="btn-nota" @disabled(! $attiva)
                    class="bg-white rounded-xl border border-gray-200 py-3 flex flex-col items-center gap-1 {{ $attiva ? '' : 'opacity-40' }}">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#7E5A86" stroke-width="1.8"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>
                <span class="text-xs">Nota</span>
            </button>
        </div>

        <div id="box-nota" class="mt-3 {{ $aperta && $pulizia->nota ? '' : 'hidden' }}">
            <textarea id="nota" rows="3" maxlength="500" placeholder="Scrivi una nota sulla camera (la legge la reception)"
                      class="w-full border border-gray-300 rounded-xl px-3 py-2 text-[15px]">{{ $aperta ? $pulizia->nota : '' }}</textarea>
            <p id="nota-stato" class="text-xs text-slateBlue mt-1"></p>
        </div>

        <div class="mt-6">
            @if ($aperta)
                <form method="POST" action="{{ route('hk.completa', $camera) }}">
                    @csrf
                    <button id="btn-completa" @disabled(! $puoCompletare)
                            class="w-full h-14 rounded-xl bg-hk text-white text-[17px] font-medium disabled:opacity-40">Segna come pulita</button>
                </form>
                @if ($obbligatoria)
                    <p id="avviso-completa" class="mt-2 text-center text-xs text-slateBlue {{ $tuttoFatto ? 'hidden' : '' }}">Spunta tutte le attività per continuare</p>
                @endif
            @elseif ($pulita)
                <a href="{{ route('hk.piano') }}" class="block w-full h-14 leading-[3.5rem] text-center rounded-xl bg-white border border-gray-200 text-[17px] font-medium">Torna al piano</a>
            @else
                <form method="POST" action="{{ route('hk.inizia', $camera) }}">
                    @csrf
                    <button class="w-full h-14 rounded-xl bg-hk text-white text-[17px] font-medium">Inizia pulizia</button>
                </form>
            @endif
        </div>
    @endif
</main>

@if ($aperta)
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const intestazioni = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf };
    const obbligatoria = @json($obbligatoria);

    function aggiorna(fatte, totale) {
        document.getElementById('n-fatte').textContent = fatte;
        document.getElementById('barra').style.width = (totale ? Math.round(fatte / totale * 100) : 0) + '%';
        const completa = document.getElementById('btn-completa');
        const avviso = document.getElementById('avviso-completa');
        const ok = !obbligatoria || fatte === totale;
        if (completa) completa.disabled = !ok;
        if (avviso) avviso.classList.toggle('hidden', ok);
    }

    document.querySelectorAll('[data-voce]').forEach(function (cb) {
        cb.addEventListener('change', async function () {
            try {
                const r = await fetch(@json(route('hk.voce', $camera)), {
                    method: 'POST', headers: intestazioni,
                    body: JSON.stringify({ voce: cb.dataset.voce, spuntata: cb.checked })
                });
                if (!r.ok) throw new Error('http ' + r.status);
                const d = await r.json();
                aggiorna(d.fatte, d.totale);
            } catch (e) {
                cb.checked = !cb.checked;
                alert('Non salvato: controlla la connessione e riprova.');
            }
        });
    });

    const btnNota = document.getElementById('btn-nota');
    const boxNota = document.getElementById('box-nota');
    const nota = document.getElementById('nota');
    const statoNota = document.getElementById('nota-stato');
    btnNota.addEventListener('click', function () { boxNota.classList.toggle('hidden'); if (!boxNota.classList.contains('hidden')) nota.focus(); });
    nota.addEventListener('blur', async function () {
        try {
            const r = await fetch(@json(route('hk.nota', $camera)), { method: 'POST', headers: intestazioni, body: JSON.stringify({ nota: nota.value }) });
            statoNota.textContent = r.ok ? 'Nota salvata' : 'Nota non salvata, riprova';
        } catch (e) { statoNota.textContent = 'Nota non salvata, riprova'; }
    });
})();
</script>
@endif
@endsection
