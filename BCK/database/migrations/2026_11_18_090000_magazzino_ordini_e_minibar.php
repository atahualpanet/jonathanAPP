<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Magazzino: ordini ai fornitori (bozza → approvato → inviato → consegnato) e collegamento col minibar.
 * Le tabelle fornitori, articoli_magazzino e movimenti_magazzino arrivano dalla migrazione del Bistro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimenti_magazzino', function (Blueprint $table) {
            $table->string('nota', 255)->nullable()->after('riferimento');
        });

        Schema::create('ordini_fornitore', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornitore_id')->constrained('fornitori')->restrictOnDelete();
            $table->enum('stato', ['bozza', 'approvato', 'inviato', 'consegnato', 'annullato'])->default('bozza');
            $table->foreignId('creato_da')->nullable()->constrained('utenti')->nullOnDelete();   // vuoto = bozza generata dalla soglia
            $table->foreignId('approvato_da')->nullable()->constrained('utenti')->nullOnDelete();
            $table->timestamp('approvato_il')->nullable();
            $table->timestamp('inviato_il')->nullable();
            $table->timestamp('consegnato_il')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['stato', 'fornitore_id']);
        });

        Schema::create('righe_ordine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordine_id')->constrained('ordini_fornitore')->cascadeOnDelete();
            $table->foreignId('articolo_id')->constrained('articoli_magazzino')->restrictOnDelete();
            $table->decimal('quantita_ordinata', 12, 3);
            $table->decimal('prezzo_acquisto', 10, 2)->nullable();
            $table->decimal('quantita_consegnata', 12, 3)->nullable();
            $table->timestamps();

            $table->unique(['ordine_id', 'articolo_id']);
        });

        // Minibar: l'articolo del listino può puntare a un articolo di magazzino (un pezzo consumato = un pezzo scaricato)
        Schema::table('minibar_articoli', function (Blueprint $table) {
            $table->foreignId('magazzino_articolo_id')->nullable()->after('aliquota_iva')->constrained('articoli_magazzino')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('minibar_articoli', function (Blueprint $table) {
            $table->dropConstrainedForeignId('magazzino_articolo_id');
        });
        Schema::dropIfExists('righe_ordine');
        Schema::dropIfExists('ordini_fornitore');
        Schema::table('movimenti_magazzino', function (Blueprint $table) {
            $table->dropColumn('nota');
        });
    }
};
