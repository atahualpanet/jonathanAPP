@php
    app()->setLocale(session('locale', 'it'));

    // Prende il nome dell'hotel dal file di configurazione
    $nomeHotel = 'Hotel';
    try {
        $cfg = json_decode(file_get_contents(config_path('hotel_config.json')), true);
        $nomeHotel = $cfg['hotel']['nome_commerciale'] ?? $cfg['hotel']['nome'] ?? 'Hotel';
    } catch (\Throwable $e) {}

    // Prende il nome dell'utente loggato e il suo ruolo
    $nomeUtente = auth()->user()?->nome ?? 'Utente';
    $ruoloUtente = auth()->user()?->ruolo ?? 'reception';

    // Genera la data di oggi in italiano
    \Carbon\Carbon::setLocale('it');
    $dataOggi = ucfirst(now()->translatedFormat('l j F'));

    // Calcola il saluto in base all'ora corrente
    $ora = now()->hour; // Restituisce un numero da 0 a 23
    
    if ($ora >= 0 && $ora < 12) {
        $saluto = 'Buongiorno';
    } elseif ($ora >= 12 && $ora < 17) {
        $saluto = 'Buon pomeriggio';
    } elseif ($ora >= 17 && $ora < 0) {
        $saluto = 'Buonasera';
    } else {
        $saluto = 'Buonanotte';
    }
@endphp

@extends('layouts.shell')

@section('title', 'Jonathan PMS — Home della suite')
@section('page_title', 'Jonathan')
@section('page_color', '#C9A75D')

@section('content')

{{-- ===== INTESTAZIONE: saluto e KPI di oggi ===== --}}
<div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $saluto }}, {{ $nomeUtente }}</h2>
<p class="text-sm text-slate-500 mt-1 capitalize">{{ $ruoloUtente }}</p>
    </div>
</div>

{{-- GRIGLIA DEI MODULI --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

    @php
        // Avvisi scorte per le card Bistro e Magazzino
        $av = [];
        try { $av = \App\Support\MagazzinoService::avvisi(); } catch (\Throwable $e) {}
        $rosso = fn ($n, $testo) => ($n ?? 0) > 0 ? '<span class="text-[#B5563C] font-bold">' . (int) $n . '</span> ' . $testo : '0 ' . $testo;
        $avBistro = array_filter([
            ($av['reparti_da_rifornire'] ?? 0) > 0 ? '<span class="text-[#B5563C] font-bold">' . (int) $av['reparti_da_rifornire'] . '</span> da rifornire' : null,
            ($av['in_arrivo'] ?? 0) > 0 ? '<span class="text-fdBlue font-bold">' . (int) $av['in_arrivo'] . '</span> consegne da confermare' : null,
        ]);
        $tuttiIModuli = [
            [
                'nome' => 'FrontDesk',
                'descrizione' => e($arriviOggi . ' arrivi · ' . $partenzeOggi . ' partenze · ' . $inCasaCount . ' in casa'),
                'simbolo' => 'frontdesk-simbolo.png',
                'url' => route('frontdesk.prenotazioni'),
                'ruoli_permessi' => ['amministratore', 'direttore', 'reception'],
            ],
            [
                'nome' => 'Prenotazioni ',
                'descrizione' => 'Calendario, disponibilità, tariffe - In arrivo',
                'simbolo' => 'direct-simbolo.png',
                'url' => route('direct.index'),
                'ruoli_permessi' => ['amministratore', 'direttore', 'reception'],
            ],
            [
                'nome' => 'Housekeeping',
                'descrizione' =>
                    ($camereDaPulire > 0 ? '<span class="text-[#B5563C] font-bold">' . (int) $camereDaPulire . '</span> da pulire' : '0 da pulire') . ' · ' .
                    ($camereInPulizia > 0 ? '<span class="text-fdBlue font-bold">' . (int) $camereInPulizia . '</span> in corso' : '0 in corso') . ' · ' .
                    ($camereInManutenzione > 0 ? '<span class="text-slate-700 font-bold">' . (int) $camereInManutenzione . '</span> off' : '0 off'),
                'simbolo' => 'housekeeping-simbolo.png',
                'url' => route('housekeeping.index'),
                'ruoli_permessi' => ['amministratore', 'direttore', 'housekeeping'],
            ],
            [
                'nome' => 'Bistro',
                'descrizione' => 'Tavoli, comande, cucina e conto' . ($avBistro ? ' · ' . implode(' · ', $avBistro) : ''),
                'simbolo' => 'bistro-simbolo.png',
                'url' => route('bistro.mappa'),
                'ruoli_permessi' => ['amministratore', 'direttore', 'barman'],
            ],
            [
                'nome' => 'Magazzino',
                'descrizione' => $rosso($av['centrale_sotto_soglia'] ?? 0, 'sotto soglia') . ' · ' . $rosso($av['reparti_da_rifornire'] ?? 0, 'nei reparti') . ' · ' . $rosso($av['richieste'] ?? 0, ($av['richieste'] ?? 0) === 1 ? 'richiesta' : 'richieste'),
                'simbolo' => 'magazzino-simbolo.png',
                'url' => route('magazzino.index'),
                'ruoli_permessi' => ['amministratore', 'direttore'],
            ],
            [
                'nome' => 'Performance',
                'descrizione' => 'Occupazione, ricavi, trend - In arrivo',
                'simbolo' => 'intelligence-simbolo.png',
                'url' => null,
                'ruoli_permessi' => ['amministratore', 'direttore'],
            ],
            [
                'nome' => 'Impostazioni',
                'descrizione' => 'Utenti, minibar, configurazioni',
                'simbolo' => 'impostazioni-simbolo.png',
                'url' => route('impostazioni.index'),
                'ruoli_permessi' => ['amministratore', 'direttore'],
            ],
        ];

        $moduliVisibili = array_filter($tuttiIModuli, function ($modulo) use ($ruoloUtente) {
            return in_array($ruoloUtente, $modulo['ruoli_permessi']);
        });
    @endphp

    @foreach ($moduliVisibili as $modulo)
        @php $attivo = filled($modulo['url']); @endphp

        <{{ $attivo ? 'a' : 'div' }}
            @if ($attivo) href="{{ $modulo['url'] }}" @endif
            class="bg-white rounded-2xl p-6 border flex flex-col justify-between transition {{ $attivo ? 'border-gray-200 shadow-sm hover:border-gray-300 cursor-pointer group no-underline' : 'border-dashed border-gray-300 opacity-70' }}">
            <div>
                <div class="w-12 h-12 rounded-xl overflow-hidden mb-4 shadow-sm">
                    <img src="{{ asset('img/moduli/' . $modulo['simbolo']) }}"
                         alt="{{ $modulo['nome'] }}"
                         class="w-full h-full object-cover">
                </div>
                <h3 class="text-lg font-bold text-slate-900">{{ $modulo['nome'] }}</h3>
            </div>
            <p class="text-sm text-slate-500 mt-4 font-medium">{!! $modulo['descrizione'] !!}</p>
        </{{ $attivo ? 'a' : 'div' }}>
    @endforeach

</div>

@endsection