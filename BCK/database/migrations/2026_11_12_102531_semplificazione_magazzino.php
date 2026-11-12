<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Migrazione storica disattivata.
 *
 * Questa migrazione era stata datata prima della creazione di bistro_articoli,
 * articoli_magazzino e bistro_ricette e tentava inoltre di eliminare tabelle
 * di gestione reparti. L'operazione era quindi fuori sequenza e distruttiva.
 *
 * Il collegamento Bistro → Magazzino è mantenuto in bistro_ricette e viene
 * riallineato dalla migrazione 2026_11_23_090000_bistro_ricette_canoniche.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Intenzionalmente vuota: non eliminare dati né tabelle esistenti.
    }

    public function down(): void
    {
        // Nessuna operazione da annullare.
    }
};
