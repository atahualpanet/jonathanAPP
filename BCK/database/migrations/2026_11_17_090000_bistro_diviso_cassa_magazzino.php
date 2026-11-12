<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bistro, seconda parte:
 *  - conto diviso: ogni incasso (anche parziale) è una riga di bistro_pagamenti e segna le righe pagate;
 *  - punto cassa: le categorie dell'articolo diventano testo libero (caffe, colazione, bar, cucina si aggiungono a quelle del ristorante);
 *  - collegamento al Magazzino: ricette (articolo Bistro → articoli di magazzino) e tabelle minime di magazzino.
 *    Le tabelle di magazzino si creano solo se non esistono già.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bistro_pagamenti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained('bistro_comande')->cascadeOnDelete();
            $table->enum('metodo', ['camera', 'carta', 'contanti']);
            $table->string('camera', 20)->nullable();
            $table->decimal('importo', 10, 2);
            $table->foreignId('prenotazione_id')->nullable()->constrained('prenotazioni')->nullOnDelete();
            $table->unsignedBigInteger('voce_conto_id')->nullable();
            $table->foreignId('utente_id')->nullable()->constrained('utenti')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('bistro_comanda_righe', function (Blueprint $table) {
            $table->unsignedBigInteger('pagamento_id')->nullable()->after('stato');
            $table->index(['comanda_id', 'pagamento_id']);
        });

        Schema::table('bistro_articoli', function (Blueprint $table) {
            $table->string('categoria', 20)->default('primi')->change();
        });

        if (! Schema::hasTable('fornitori')) {
            Schema::create('fornitori', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 150);
                $table->string('email', 150)->nullable();
                $table->string('telefono', 40)->nullable();
                $table->unsignedSmallInteger('lead_time_giorni')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('articoli_magazzino')) {
            Schema::create('articoli_magazzino', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 100);
                $table->string('unita_misura', 10)->default('pz');
                $table->decimal('prezzo_acquisto', 10, 2)->nullable();
                $table->foreignId('fornitore_id')->nullable()->constrained('fornitori')->nullOnDelete();
                $table->decimal('scorta_attuale', 12, 3)->default(0);
                $table->decimal('soglia_riordino', 12, 3)->nullable();
                $table->decimal('quantita_standard_ordine', 12, 3)->nullable();
                $table->boolean('attivo')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('movimenti_magazzino')) {
            Schema::create('movimenti_magazzino', function (Blueprint $table) {
                $table->id();
                $table->foreignId('articolo_id')->constrained('articoli_magazzino')->restrictOnDelete();
                $table->enum('tipo', ['carico', 'scarico']);
                $table->decimal('quantita', 12, 3);
                $table->string('sorgente', 20);                 // bistro, minibar, bar, manuale
                $table->string('riferimento', 60)->nullable();  // es. "comanda:123"
                $table->foreignId('utente_id')->nullable()->constrained('utenti')->nullOnDelete();
                $table->timestamps();

                $table->index(['articolo_id', 'created_at']);
                $table->index(['sorgente', 'riferimento']);
            });
        }

        Schema::create('bistro_ricette', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bistro_articolo_id')->constrained('bistro_articoli')->cascadeOnDelete();
            $table->foreignId('magazzino_articolo_id')->constrained('articoli_magazzino')->cascadeOnDelete();
            $table->decimal('quantita', 12, 3);                 // quanto ne consuma UNA vendita
            $table->timestamps();

            $table->unique(['bistro_articolo_id', 'magazzino_articolo_id'], 'bistro_ricette_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bistro_ricette');
        Schema::table('bistro_comanda_righe', function (Blueprint $table) {
            $table->dropIndex(['comanda_id', 'pagamento_id']);
            $table->dropColumn('pagamento_id');
        });
        Schema::dropIfExists('bistro_pagamenti');
        // categoria resta testo e le tabelle di magazzino restano: possono contenere dati del modulo Magazzino
    }
};
