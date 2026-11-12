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
        Schema::create('ospiti', function (Blueprint $table) {
    $table->id();
    $table->string('nome');
    $table->string('cognome');
    $table->string('email')->nullable()->index();
    $table->string('telefono', 30)->nullable();
    $table->date('data_nascita')->nullable();
    $table->string('nazionalita', 2)->nullable();
    $table->string('tipo_documento', 20)->nullable();
    $table->string('numero_documento')->nullable();
    $table->string('ente_rilascio')->nullable();
    $table->date('scadenza_documento')->nullable();
    $table->text('indirizzo')->nullable();
    $table->string('citta')->nullable();
    $table->string('cap', 10)->nullable();
    $table->string('paese', 2)->nullable();
    $table->text('note')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['cognome', 'nome']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ospiti');
    }
};
