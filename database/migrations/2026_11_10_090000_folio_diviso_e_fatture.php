<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Folio diviso (A = ospite, B = extra) e fatture per folio.
 * - voci_conto.folio: in quale folio sta ogni voce (le voci già esistenti restano nel folio A)
 * - fatture: una riga per conto+folio, "differita" (da emettere dopo) oppure "emessa" (con numero progressivo annuo)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voci_conto', function (Blueprint $table) {
            $table->string('folio', 1)->default('A')->after('conto_id');
            $table->index(['conto_id', 'folio']);
        });

        Schema::create('fatture', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conto_id')->constrained('conti')->cascadeOnDelete();
            $table->string('folio', 1);
            $table->enum('stato', ['differita', 'emessa'])->default('differita');
            $table->unsignedSmallInteger('anno')->nullable();
            $table->unsignedInteger('progressivo')->nullable();
            $table->string('numero', 20)->nullable()->unique();
            $table->date('data_emissione')->nullable();
            $table->string('intestatario', 150)->nullable();
            $table->string('partita_iva', 11)->nullable();
            $table->string('codice_fiscale', 16)->nullable();
            $table->string('codice_destinatario', 7)->nullable();
            $table->string('pec', 150)->nullable();
            $table->string('indirizzo')->nullable();
            $table->decimal('importo_totale', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['conto_id', 'folio']);
            $table->unique(['anno', 'progressivo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fatture');

        Schema::table('voci_conto', function (Blueprint $table) {
            $table->dropIndex(['conto_id', 'folio']);
            $table->dropColumn('folio');
        });
    }
};
