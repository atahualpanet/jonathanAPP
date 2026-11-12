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
       Schema::create('tipologie_camera', function (Blueprint $table) {
    $table->id();
    $table->string('nome');
    $table->string('codice', 20)->unique();
    $table->text('descrizione')->nullable();
    $table->unsignedTinyInteger('capacita_adulti')->default(1);
    $table->unsignedTinyInteger('capacita_bambini')->default(0);
    $table->decimal('prezzo_base', 10, 2)->default(0);
    $table->boolean('attiva')->default(true);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipologie_camera');
    }
};
