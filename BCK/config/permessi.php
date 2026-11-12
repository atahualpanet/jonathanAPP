<?php

/*
 * Moduli e funzioni dell'applicazione (l'elenco è legato al codice, quindi sta qui).
 * I PROFILI (amministratore, direttore, reception, ...) e chi può fare cosa
 * stanno invece nel database, tabella `ruoli`, e si modificano dalla pagina
 * "Permessi per profilo".
 */
return [

    // Colori dei moduli = riquadri nella colonna "Moduli"
    'moduli' => [
        'frontdesk'    => ['nome' => 'FrontDesk',    'colore' => '#4F86B0'],
        'direct'       => ['nome' => 'Direct',       'colore' => '#B8923F'],
        'sync'         => ['nome' => 'Sync',         'colore' => '#5E8F68'],
        'housekeeping' => ['nome' => 'Housekeeping', 'colore' => '#7E5A86'],
        'bistro'       => ['nome' => 'Bistro',       'colore' => '#C9822B'],
        'intelligence' => ['nome' => 'Intelligence', 'colore' => '#C4543A'],
    ],

    // Righe della matrice permessi (chiave => etichetta)
    'funzioni' => [
        'prenotazioni' => 'Prenotazioni e tableau',
        'folio'        => 'Folio e fatturazione',
        'tariffe'      => 'Tariffe e canali',
        'stato_camere' => 'Stato camere',
        'guasti'       => 'Segnalazione guasti',
        'comande'      => 'Comande e conti',
        'kpi'          => 'KPI e report',
        'assegna_pulizie' => 'Assegnazione camere (Housekeeping)',
    ],

    // Striscia verde in fondo alla matrice. Metti null per toglierla.
    'nota' => 'Dati cifrati e registro degli accessi attivi',
];
