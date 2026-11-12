<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Minibar: listino articoli, dotazione standard per tipologia di camera e
 * controlli della governante (ogni controllo addebita le mancanze sul folio B).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minibar_articoli', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->enum('categoria', ['bevande', 'alcolici', 'snack', 'altro'])->default('bevande');
            $table->decimal('prezzo', 8, 2);
            $table->decimal('aliquota_iva', 4, 2)->default(22);
            $table->boolean('attivo')->default(true);
            $table->timestamps();

            $table->index(['attivo', 'categoria']);
        });

        Schema::create('minibar_dotazioni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipologia_camera_id')->constrained('tipologie_camera')->cascadeOnDelete();
            $table->foreignId('articolo_id')->constrained('minibar_articoli')->cascadeOnDelete();
            $table->unsignedTinyInteger('quantita')->default(1);
            $table->timestamps();

            $table->unique(['tipologia_camera_id', 'articolo_id']);
        });

        Schema::create('minibar_controlli', function (Blueprint $table) {
            $table->id();
            $table->string('token', 40)->unique();            // evita il doppio addebito se il modulo viene inviato due volte
            $table->foreignId('camera_id')->constrained('camere')->restrictOnDelete();
            $table->foreignId('prenotazione_id')->constrained('prenotazioni')->restrictOnDelete();
            $table->string('controllato_da', 100)->nullable(); // nome di chi ha controllato (governante)
            $table->decimal('totale', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('minibar_controllo_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('controllo_id')->constrained('minibar_controlli')->cascadeOnDelete();
            $table->unsignedBigInteger('articolo_id')->nullable();
            $table->string('nome', 100);                       // copiati dal listino al momento del controllo
            $table->decimal('prezzo', 8, 2);
            $table->unsignedTinyInteger('quantita');
            $table->unsignedBigInteger('voce_conto_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minibar_controllo_righe');
        Schema::dropIfExists('minibar_controlli');
        Schema::dropIfExists('minibar_dotazioni');
        Schema::dropIfExists('minibar_articoli');
    }
};
