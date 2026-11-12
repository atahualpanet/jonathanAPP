@php
    $voci = ['magazzino.index' => 'Scorte', 'magazzino.reparti' => 'Reparti', 'magazzino.ordini' => 'Ordini', 'magazzino.fornitori' => 'Fornitori', 'magazzino.movimenti' => 'Movimenti'];
    $attiva = request()->routeIs('magazzino.articolo*') ? 'magazzino.index' : (request()->routeIs('magazzino.ordine*') ? 'magazzino.ordini' : request()->route()->getName());
    try { $av = \App\Support\MagazzinoService::avvisi(); } catch (\Throwable $e) { $av = []; }
    $badge = ['magazzino.reparti' => ($av['richieste'] ?? 0) + ($av['reparti_da_rifornire'] ?? 0), 'magazzino.ordini' => $av['bozze'] ?? 0, 'magazzino.index' => $av['centrale_sotto_soglia'] ?? 0];
@endphp
<div class="border-b border-gray-200 mb-6 flex overflow-x-auto whitespace-nowrap">
    @foreach ($voci as $rotta => $nome)
        <a href="{{ route($rotta) }}" class="py-3 px-6 border-b-2 font-medium text-sm transition-colors {{ $rotta === $attiva ? 'border-slateBlue text-slateBlue' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300' }}">{{ $nome }}@if (($badge[$rotta] ?? 0) > 0) <span class="ml-1.5 inline-block min-w-[1.25rem] text-center rounded-full bg-[#B5563C] text-white text-xs px-1.5">{{ $badge[$rotta] }}</span>@endif</a>
    @endforeach
</div>
@include('frontdesk.partials.messaggi')
