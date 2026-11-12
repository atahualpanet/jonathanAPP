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
        Schema::create('voci_conto', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conto_id')->constrained('conti')->cascadeOnDelete();
    $table->enum('tipo', ['addebito_camera', 'servizio', 'pagamento', 'rettifica', 'caparra']);
    $table->string('descrizione');
    $table->decimal('importo', 12, 2);
    $table->string('riferimento')->nullable();
    $table->timestamp('registrata_il')->useCurrent();
    $table->timestamps();
    $table->index(['conto_id', 'tipo']);
    $table->index('registrata_il');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voci_conto');
    }
};
