<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Richieste di rifornimento dei reparti al magazzino centrale (manuali, o automatiche quando la scorta tocca la soglia). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('richieste_reparto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reparto_id')->constrained('reparti')->cascadeOnDelete();
            $table->foreignId('articolo_id')->constrained('articoli_magazzino')->cascadeOnDelete();
            $table->decimal('quantita', 12, 3);
            $table->enum('stato', ['aperta', 'evasa', 'annullata'])->default('aperta');
            $table->boolean('automatica')->default(false);
            $table->foreignId('richiesta_da')->nullable()->constrained('utenti')->nullOnDelete();
            $table->timestamp('richiesta_il')->nullable();
            $table->timestamp('evasa_il')->nullable();
            $table->unsignedBigInteger('trasferimento_id')->nullable();
            $table->timestamps();

            $table->index(['stato', 'reparto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('richieste_reparto');
    }
};
