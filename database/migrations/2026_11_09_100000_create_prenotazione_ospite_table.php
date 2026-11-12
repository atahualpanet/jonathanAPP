<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ospiti aggiuntivi di una prenotazione.
 *
 * Il titolare resta in prenotazioni.ospite_id (nulla cambia per le prenotazioni esistenti);
 * qui stanno tutte le altre persone che soggiornano (adulti accompagnatori e bambini),
 * ognuna con i propri dati e il proprio documento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenotazione_ospite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prenotazione_id')->constrained('prenotazioni')->cascadeOnDelete();
            $table->foreignId('ospite_id')->constrained('ospiti')->restrictOnDelete();
            $table->boolean('bambino')->default(false);
            $table->unsignedTinyInteger('ordine')->default(0);
            $table->timestamps();

            $table->unique(['prenotazione_id', 'ospite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenotazione_ospite');
    }
};
