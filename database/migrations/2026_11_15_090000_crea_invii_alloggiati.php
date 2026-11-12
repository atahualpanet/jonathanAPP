<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro degli invii Alloggiati Web.
 *
 * Alla Questura le schedine vanno comunicate in blocco (invio "massivo"); chi arriva
 * dopo l'invio del giorno si comunica da solo (invio "singolo"). Ogni invio conserva
 * una copia dei dati così com'erano al momento dell'invio (colonna `schedine`), per
 * poter rigenerare il PDF anche se poi l'anagrafica dell'ospite viene corretta.
 * Qui si registra soltanto: il collegamento col portale non è implementato.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invii_alloggiati', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 10);                 // massivo | singolo
            $table->date('data_riferimento')->index();  // giorno a cui si riferisce l'invio
            $table->timestamp('inviato_il');
            $table->unsignedBigInteger('utente_id')->nullable();
            $table->json('schedine');                   // copia dei dati inviati
            $table->timestamps();
        });

        Schema::table('prenotazioni', function (Blueprint $table) {
            $table->unsignedBigInteger('invio_alloggiati_id')->nullable()->index()->after('alloggiati_inviato_il');
        });
    }

    public function down(): void
    {
        Schema::table('prenotazioni', function (Blueprint $table) {
            $table->dropIndex(['invio_alloggiati_id']);
            $table->dropColumn('invio_alloggiati_id');
        });

        Schema::dropIfExists('invii_alloggiati');
    }
};
