<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabelle codici Alloggiati Web (Polizia di Stato).
 *
 * Popolale importando i file scaricati da (login richiesto):
 * https://alloggiatiweb.poliziadistato.it/portalealloggiati/tabelle.aspx
 *
 *   php artisan alloggiati:import-tabelle \
 *     --stati=storage/app/alloggiati/stati.txt \
 *     --comuni=storage/app/alloggiati/comuni.txt \
 *     --documenti=storage/app/alloggiati/documenti.txt
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('alloggiati_stati')) {
            Schema::create('alloggiati_stati', function (Blueprint $table) {
                $table->id();
                $table->string('codice', 9)->unique();
                $table->string('nome', 120);
                $table->string('iso2', 2)->nullable()->index();
                $table->boolean('cessato')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('alloggiati_comuni')) {
            Schema::create('alloggiati_comuni', function (Blueprint $table) {
                $table->id();
                $table->string('codice', 9)->unique();
                $table->string('nome', 120)->index();
                $table->string('provincia', 2)->index();
                $table->boolean('cessato')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('alloggiati_documenti')) {
            Schema::create('alloggiati_documenti', function (Blueprint $table) {
                $table->id();
                $table->string('codice', 5)->unique();
                $table->string('nome', 120);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alloggiati_documenti');
        Schema::dropIfExists('alloggiati_comuni');
        Schema::dropIfExists('alloggiati_stati');
    }
};
