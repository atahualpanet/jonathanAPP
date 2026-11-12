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
        Schema::create('conti', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prenotazione_id')->constrained('prenotazioni')->cascadeOnDelete();
    $table->string('codice', 20)->unique();
    $table->enum('stato', ['aperto', 'chiuso', 'annullato'])->default('aperto');
    $table->decimal('saldo', 12, 2)->default(0);
    $table->timestamp('chiuso_il')->nullable();
    $table->timestamps();
    $table->index('stato');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conti');
    }
};
