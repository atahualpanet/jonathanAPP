@extends('layouts.shell')
@section('title', 'Jonathan PMS — Licenze e Moduli')
@section('page_title', 'Licenze e Moduli')
@section('page_color', '#7A3B4A')

@section('content')
<div class="w-full">
    @include('frontdesk.partials.messaggi')

    <a href="{{ route('impostazioni.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-deepNavy mb-5">
        &larr; Torna alle Impostazioni
    </a>

    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 class="text-lg font-medium mb-4">Stato dei moduli e licenze</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-200 text-left text-slate-500">
                    <tr>
                        <th class="py-2">Modulo</th>
                        <th class="py-2">Stato</th>
                        <th class="py-2">Scadenza</th>
                        <th class="py-2">Versione</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($moduli as $m)
                        <tr class="border-b border-gray-100">
                            <td class="py-2 font-medium">{{ $m->nome }}</td>
                            <td class="py-2">
                                @if ($m->stato === 'attivo')
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-okGreen text-white">Attivo</span>
                                @elseif ($m->stato === 'in_scadenza')
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-warmGold text-white">In scadenza</span>
                                @else
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-[#B5563C] text-white">Scaduto</span>
                                @endif
                            </td>
                            <td class="py-2 text-slate-500">{{ $m->scadenza ?? '—' }}</td>
                            <td class="py-2 text-slate-500">{{ $m->versione }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection