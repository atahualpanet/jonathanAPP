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
       Schema::create('camere', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tipologia_camera_id')->constrained('tipologie_camera')->restrictOnDelete();
    $table->string('numero', 20)->unique();
    $table->unsignedSmallInteger('piano')->default(0);
    $table->enum('stato_pulizia', ['pulita', 'sporca', 'ispezionata', 'manutenzione'])->default('pulita');
    $table->boolean('attiva')->default(true);
    $table->text('note')->nullable();
    $table->timestamps();
    $table->index('stato_pulizia');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camere');
    }
};
