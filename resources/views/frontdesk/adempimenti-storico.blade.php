@php
    app()->setLocale(session('locale', 'it'));
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Storico Alloggiati')
@section('page_title', 'Storico Alloggiati Web')
@section('page_color', '#4F86B0')

@section('content')

<a href="{{ route('adempimenti.mensile') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">
    &larr; Torna ad Adempimenti
</a>

<div class="bg-white rounded-2xl border border-gray-200 p-6">
    <h2 class="text-lg font-medium mb-4">Storico invii · ricerca</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        <div class="lg:col-span-2">
            <label class="block text-xs text-slate-500 mb-1">Cerca (cognome, nome, documento, camera…)</label>
            <input type="search" id="q" placeholder="es. Ramanaye, 203, PASOR…"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-fdBlue/30">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Dal</label>
            <input type="date" id="dal" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-fdBlue/30">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Al</label>
            <input type="date" id="al" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-fdBlue/30">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Tipo</label>
            <select id="tipo" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-fdBlue/30">
                <option value="">Tutti</option>
                <option value="massivo">Massivo</option>
                <option value="singolo">Singolo</option>
            </select>
        </div>
    </div>

    <div class="flex items-center gap-3 mb-4">
        <button type="button" id="btn-cerca"
                class="bg-fdBlue hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg text-sm">
            Cerca
        </button>
        <button type="button" id="btn-reset"
                class="text-sm text-slate-500 hover:text-deepNavy">
            Reset
        </button>
        <span id="status" class="text-xs text-slate-400 ml-auto"></span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-slate-500 border-b border-gray-100">
                    <th class="py-2 pr-3 font-medium">Inviato</th>
                    <th class="py-2 pr-3 font-medium">Riferimento</th>
                    <th class="py-2 pr-3 font-medium">Tipo</th>
                    <th class="py-2 pr-3 font-medium">Schedine</th>
                    <th class="py-2 pr-3 font-medium">Ospiti</th>
                    <th class="py-2 font-medium text-right">File</th>
                </tr>
            </thead>
            <tbody id="risultati">
                <tr>
                    <td colspan="6" class="py-8 text-center text-slate-400">Usa i filtri e premi Cerca.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const url = @json(route('adempimenti.storico.search'));
    const $q = document.getElementById('q');
    const $dal = document.getElementById('dal');
    const $al = document.getElementById('al');
    const $tipo = document.getElementById('tipo');
    const $btn = document.getElementById('btn-cerca');
    const $reset = document.getElementById('btn-reset');
    const $status = document.getElementById('status');
    const $tb = document.getElementById('risultati');

    let timer = null;

    function cerca() {
        $status.textContent = 'Ricerca…';
        const params = new URLSearchParams();
        if ($q.value.trim()) params.set('q', $q.value.trim());
        if ($dal.value) params.set('dal', $dal.value);
        if ($al.value) params.set('al', $al.value);
        if ($tipo.value) params.set('tipo', $tipo.value);

        fetch(url + '?' + params.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(j => {
            const rows = j.data || [];
            $status.textContent = rows.length + (rows.length === 1 ? ' risultato' : ' risultati');
            if (!rows.length) {
                $tb.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-slate-400">Nessun invio trovato.</td></tr>';
                return;
            }
            $tb.innerHTML = rows.map(r => {
                const badge = r.tipo === 'massivo'
                    ? '<span class="text-xs font-medium px-2 py-0.5 rounded-full bg-okGreen text-white">Massivo</span>'
                    : '<span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-slate-600">Singolo</span>';
                const nomi = (r.nomi || []).join(', ') || '—';
                return `<tr class="border-b border-gray-50 hover:bg-gray-50/50">
                    <td class="py-2.5 pr-3 text-slate-600">${r.inviato_il || '—'}</td>
                    <td class="py-2.5 pr-3">${r.data_riferimento || '—'}</td>
                    <td class="py-2.5 pr-3">${badge}</td>
                    <td class="py-2.5 pr-3">${r.n_schedine}</td>
                    <td class="py-2.5 pr-3 text-slate-600 max-w-[220px] truncate" title="${nomi}">${nomi}</td>
                    <td class="py-2.5 text-right whitespace-nowrap">
                        <a href="${r.url_pdf}" target="_blank" rel="noopener" class="text-xs font-medium text-fdBlue hover:underline mr-2">PDF</a>
                        <a href="${r.url_txt}" class="text-xs font-medium text-fdBlue hover:underline">TXT</a>
                    </td>
                </tr>`;
            }).join('');
        })
        .catch(() => {
            $status.textContent = 'Errore di rete';
            $tb.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-[#B5563C]">Errore durante la ricerca.</td></tr>';
        });
    }

    $btn.addEventListener('click', cerca);
    $q.addEventListener('keydown', e => { if (e.key === 'Enter') cerca(); });
    $q.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(cerca, 400);
    });
    $reset.addEventListener('click', () => {
        $q.value = ''; $dal.value = ''; $al.value = ''; $tipo.value = '';
        $tb.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-slate-400">Usa i filtri e premi Cerca.</td></tr>';
        $status.textContent = '';
    });

    // Carica ultimi invii all'apertura
    cerca();
})();
</script>
@endpush
@endsection
