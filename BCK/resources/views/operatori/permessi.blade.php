@extends('layouts.shell')

@section('title', 'Jonathan PMS — Permessi per profilo')
@section('page_title', 'Permessi per profilo')
@section('page_color', '#7A3B4A')

@section('content')
<div class="w-100">

    @include('frontdesk.partials.messaggi')

    <div class="mb-5 flex items-center gap-6">
        <a href="{{ route('operatori.index') }}" class="text-sm text-fdBlue hover:underline">&larr; Utenti e permessi</a>
        <a href="{{ route('profili.index') }}" class="text-sm text-fdBlue hover:underline">Gestisci profili &rarr;</a>
    </div>

    <form method="POST" action="{{ route('permessi.salva') }}">
        @csrf
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse min-w-[720px]">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs text-slate-500">
                            <th class="h-14 px-6 text-left font-normal"></th>
                            @foreach ($ruoli as $r)
                                <th class="h-14 px-2 text-center font-normal">{{ $r->nome }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($funzioni as $chiaveFunzione => $etichetta)
                            <tr class="border-b border-gray-200 last:border-b-0 h-[60px]">
                                <td class="px-6 text-[15px] font-medium">{{ $etichetta }}</td>
                                @foreach ($ruoli as $r)
                                    <td class="px-2 text-center">
                                        <input type="checkbox" name="matrice[{{ $r->id }}][]" value="{{ $chiaveFunzione }}"
                                               @checked(in_array($chiaveFunzione, $r->funzioni ?? [], true))
                                               aria-label="{{ $r->nome }}: {{ $etichetta }}"
                                               class="w-5 h-5 rounded accent-[#5E8F68] cursor-pointer">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($nota)
            <div class="mt-4 rounded-xl bg-okGreen/10 text-okGreen px-5 py-3 text-sm font-medium">{{ $nota }}</div>
        @endif

        <div class="mt-5">
            <button class="bg-deepNavy hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl text-sm">Salva permessi</button>
        </div>
    </form>
</div>
@endsection
