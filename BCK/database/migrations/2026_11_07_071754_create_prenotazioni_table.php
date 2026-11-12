<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prenotazioni', function (Blueprint $table) {
    $table->id();
    $table->string('codice', 20)->unique();
    $table->foreignId('ospite_id')->constrained('ospiti')->restrictOnDelete();
    $table->date('data_arrivo');
    $table->date('data_partenza');
    $table->unsignedTinyInteger('adulti')->default(1);
    $table->unsignedTinyInteger('bambini')->default(0);
    $table->enum('stato', ['in_attesa', 'confermata', 'in_casa', 'partita', 'annullata', 'no_show'])->default('in_attesa');
    $table->enum('canale', ['diretto', 'telefono', 'walk_in', 'ota', 'altro'])->default('diretto');
    $table->decimal('importo_totale', 10, 2)->default(0);
    $table->decimal('importo_caparra', 10, 2)->default(0);
    $table->text('note')->nullable();
    $table->timestamp('entrata_il')->nullable();
    $table->timestamp('uscita_il')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['data_arrivo', 'data_partenza']);
    $table->index('stato');
    $table->index('canale');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prenotazioni');
    }
};
