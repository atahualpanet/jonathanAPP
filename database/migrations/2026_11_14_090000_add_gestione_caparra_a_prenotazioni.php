<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Esito della caparra quando una prenotazione viene annullata o segnata no-show.
 * Prima il sistema non la rimborsava né la trattenava: ora l'operatore sceglie e l'esito resta registrato.
 *   caparra_trattenuta   quanto dell'importo_caparra l'hotel tiene (penale)
 *   caparra_rimborsata   quanto torna all'ospite
 *   caparra_gestita_il   quando è stata decisa (null = nessuna decisione: caparra a zero o prenotazione non chiusa così)
 * trattenuta + rimborsata = importo_caparra al momento della chiusura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenotazioni', function (Blueprint $table) {
            $table->decimal('caparra_trattenuta', 10, 2)->nullable()->after('importo_caparra');
            $table->decimal('caparra_rimborsata', 10, 2)->nullable()->after('caparra_trattenuta');
            $table->timestamp('caparra_gestita_il')->nullable()->after('caparra_rimborsata');
        });
    }

    public function down(): void
    {
        Schema::table('prenotazioni', function (Blueprint $table) {
            $table->dropColumn(['caparra_trattenuta', 'caparra_rimborsata', 'caparra_gestita_il']);
        });
    }
};
