<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permette di caricare davvero la scansione/foto del documento nel check-in,
 * non solo di spuntare "verificato" guardando un campo testuale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ospiti', function (Blueprint $table) {
            $table->string('documento_file')->nullable()->after('scadenza_documento');
        });
    }

    public function down(): void
    {
        Schema::table('ospiti', function (Blueprint $table) {
            $table->dropColumn('documento_file');
        });
    }
};
