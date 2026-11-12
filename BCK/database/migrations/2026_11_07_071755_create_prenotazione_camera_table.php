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
        Schema::create('prenotazione_camera', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prenotazione_id')->constrained('prenotazioni')->cascadeOnDelete();
    $table->foreignId('camera_id')->constrained('camere')->restrictOnDelete();
    $table->date('data_arrivo');
    $table->date('data_partenza');
    $table->decimal('tariffa', 10, 2)->default(0);
    $table->timestamps();
    $table->unique(['prenotazione_id', 'camera_id']);
    $table->index(['camera_id', 'data_arrivo', 'data_partenza']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prenotazione_camera');
    }
};
