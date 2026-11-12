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
        Schema::create('registri_pulizia', function (Blueprint $table) {
    $table->id();
    $table->foreignId('camera_id')->constrained('camere')->cascadeOnDelete();
    $table->foreignId('utente_id')->nullable()->constrained('utenti')->nullOnDelete();
    $table->string('stato_precedente', 20)->nullable();
    $table->string('stato_nuovo', 20);
    $table->text('note')->nullable();
    $table->timestamps();
    $table->index(['camera_id', 'created_at']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registri_pulizia');
    }
};
