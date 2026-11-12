<?php

return [

    // Attività della checklist di camera (chiave => etichetta). Si possono cambiare etichette e ordine:
    // le chiavi già spuntate nelle pulizie passate restano nello storico.
    'checklist' => [
        'letto'       => 'Letto rifatto',
        'bagno'       => 'Bagno pulito',
        'asciugamani' => 'Asciugamani sostituiti',
        'pavimento'   => 'Pavimento',
        'finestre'    => 'Finestre e specchi',
        'minibar'     => 'Minibar controllato',
    ],

    // true = "Segna come pulita" si attiva solo con tutte le attività spuntate
    'checklist_obbligatoria' => true,
];
