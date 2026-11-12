<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Trasferimenti dal magazzino centrale a un reparto: il centrale invia, il reparto riceve e conferma la quantità arrivata. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trasferimenti_reparto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articolo_id')->constrained('articoli_magazzino')->restrictOnDelete();
            $table->foreignId('reparto_id')->constrained('reparti')->restrictOnDelete();
            $table->decimal('quantita_inviata', 12, 3);
            $table->decimal('quantita_ricevuta', 12, 3)->nullable();
            $table->enum('stato', ['in_arrivo', 'ricevuto', 'annullato'])->default('in_arrivo');
            $table->foreignId('inviato_da')->nullable()->constrained('utenti')->nullOnDelete();
            $table->foreignId('ricevuto_da')->nullable()->constrained('utenti')->nullOnDelete();
            $table->timestamp('inviato_il')->nullable();
            $table->timestamp('ricevuto_il')->nullable();
            $table->string('nota', 255)->nullable();
            $table->timestamps();

            $table->index(['reparto_id', 'stato']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trasferimenti_reparto');
    }
};
