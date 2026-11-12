<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bistro: tavoli, listino, comande con le loro righe.
 * L'addebito in camera finisce in voci_conto (folio B, come il minibar): qui si tiene solo il collegamento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bistro_tavoli', function (Blueprint $table) {
            $table->id();
            $table->enum('zona', ['sala', 'terrazza', 'bar'])->default('sala');
            $table->unsignedSmallInteger('numero');
            $table->boolean('attivo')->default(true);
            $table->timestamps();

            $table->unique(['zona', 'numero']);
        });

        Schema::create('bistro_articoli', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->enum('categoria', ['antipasti', 'primi', 'secondi', 'dessert', 'bevande'])->default('primi');
            $table->decimal('prezzo', 8, 2);
            $table->boolean('attivo')->default(true);
            $table->timestamps();

            $table->index(['attivo', 'categoria']);
        });

        Schema::create('bistro_comande', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tavolo_id')->constrained('bistro_tavoli')->restrictOnDelete();
            $table->unsignedTinyInteger('coperti');
            $table->enum('stato', ['aperta', 'conto', 'chiusa'])->default('aperta');
            $table->timestamp('aperta_il')->useCurrent();
            $table->timestamp('chiusa_il')->nullable();
            $table->foreignId('utente_id')->nullable()->constrained('utenti')->nullOnDelete();
            $table->enum('metodo_pagamento', ['camera', 'carta', 'contanti'])->nullable();
            $table->decimal('totale', 10, 2)->default(0);
            $table->foreignId('prenotazione_id')->nullable()->constrained('prenotazioni')->nullOnDelete();
            $table->unsignedBigInteger('voce_conto_id')->nullable();
            $table->timestamps();

            $table->index(['tavolo_id', 'stato']);
            $table->index('aperta_il');
        });

        Schema::create('bistro_comanda_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained('bistro_comande')->cascadeOnDelete();
            $table->unsignedBigInteger('articolo_id')->nullable();
            $table->string('nome', 100);                       // copiati dal listino al momento dell'ordine
            $table->string('categoria', 20);
            $table->decimal('prezzo', 8, 2);
            $table->unsignedTinyInteger('quantita')->default(1);
            $table->enum('stato', ['nuova', 'inviata', 'pronta'])->default('nuova');
            $table->timestamp('inviata_il')->nullable();
            $table->timestamp('pronta_il')->nullable();
            $table->timestamps();

            $table->index(['comanda_id', 'stato']);
        });

        // Sala con i 10 tavoli della mappa di progetto; terrazza e bar si aggiungono dal listino Bistro.
        foreach (range(1, 10) as $n) {
            DB::table('bistro_tavoli')->insert(['zona' => 'sala', 'numero' => $n, 'attivo' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bistro_comanda_righe');
        Schema::dropIfExists('bistro_comande');
        Schema::dropIfExists('bistro_articoli');
        Schema::dropIfExists('bistro_tavoli');
    }
};
