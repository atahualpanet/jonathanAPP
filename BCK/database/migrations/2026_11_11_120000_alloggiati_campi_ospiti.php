<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campi richiesti dal tracciato Alloggiati Web (Polizia di Stato):
 * - sesso (M/F)
 * - luogo di nascita (comune italiano oppure stato estero + eventuale provincia)
 * - luogo di rilascio del documento (comune italiano oppure stato estero)
 *
 * Nota: il vecchio campo `ente_rilascio` (testo libero) resta per compatibilità
 * ma non viene più usato nei form: al suo posto si usa `luogo_rilascio`.
 * I documenti restano obbligatori in app per tutti gli adulti (uso interno);
 * nelle schedine da inviare al portale si includono solo dove la legge li richiede.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ospiti', function (Blueprint $table) {
            $table->char('sesso', 1)->nullable()->after('data_nascita'); // M | F

            // Luogo di nascita: per italiani = comune (+ provincia), per stranieri = stato
            $table->string('luogo_nascita', 100)->nullable()->after('sesso');
            $table->string('luogo_nascita_provincia', 2)->nullable()->after('luogo_nascita'); // solo IT

            // Luogo di rilascio documento: comune italiano oppure stato estero
            $table->string('luogo_rilascio', 100)->nullable()->after('numero_documento');
            $table->string('luogo_rilascio_provincia', 2)->nullable()->after('luogo_rilascio'); // solo IT
        });
    }

    public function down(): void
    {
        Schema::table('ospiti', function (Blueprint $table) {
            $table->dropColumn([
                'sesso',
                'luogo_nascita',
                'luogo_nascita_provincia',
                'luogo_rilascio',
                'luogo_rilascio_provincia',
            ]);
        });
    }
};
