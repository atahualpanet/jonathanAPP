<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * voci_conto.origine: da dove nasce una voce generata dalla prenotazione.
 *   camera:{id camera}  addebito delle notti di quella camera
 *   tassa               tassa di soggiorno
 *   caparra             caparra versata alla prenotazione
 * Serve a riallineare il conto quando la prenotazione cambia (date, camera, tariffa, adulti, caparra).
 * Le voci già esistenti restano con origine vuota: vengono riconosciute dalla descrizione e poi marcate.
 * (Non si usa `riferimento`: lì viaggia l'aliquota IVA e una sigla come "camera:12" verrebbe letta come aliquota.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voci_conto', function (Blueprint $table) {
            $table->string('origine', 40)->nullable()->after('riferimento');
            $table->index(['conto_id', 'origine']);
        });
    }

    public function down(): void
    {
        Schema::table('voci_conto', function (Blueprint $table) {
            $table->dropIndex(['conto_id', 'origine']);
            $table->dropColumn('origine');
        });
    }
};
