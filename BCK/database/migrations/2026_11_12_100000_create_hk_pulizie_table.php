<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Una riga per ogni pulizia di camera: chi, quando ha iniziato e finito, checklist spuntata, nota. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hk_pulizie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained('camere')->cascadeOnDelete();
            $table->foreignId('utente_id')->constrained('utenti')->cascadeOnDelete();
            $table->date('data');
            $table->timestamp('iniziata_il')->nullable();
            $table->timestamp('finita_il')->nullable();
            $table->json('voci')->nullable();      // chiavi delle attività spuntate
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->index(['camera_id', 'data']);
            $table->index(['utente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hk_pulizie');
    }
};
