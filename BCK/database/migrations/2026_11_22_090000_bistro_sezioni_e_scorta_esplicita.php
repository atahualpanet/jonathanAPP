<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bistro: un solo posto dove si vende (reparto + sezione) e una scelta ESPLICITA sulla scorta.
 *  - la sezione "Cucina" del punto cassa sparisce: gli articoli che c'erano passano a "Bibite" (bar);
 *  - ogni articolo o scarica un articolo di magazzino, o è segnato "senza scorta" (i piatti lo sono di default);
 *    un articolo che non è né l'uno né l'altro viene segnalato a ogni vendita, invece di non fare niente in silenzio.
 */
return new class extends Migration
{
    private const PIATTI = ['antipasti', 'primi', 'secondi', 'dessert'];

    public function up(): void
    {
        Schema::table('bistro_articoli', function (Blueprint $table) {
            $table->boolean('senza_scorta')->default(false)->after('aliquota_iva');
        });

        DB::table('bistro_articoli')->where('categoria', 'cucina')->update(['categoria' => 'bar']);

        // il reparto segue la sezione: Bar per caffè/colazione/bibite, Ristorante per il resto
        if ($bar = DB::table('reparti')->where('nome', 'Bar')->value('id')) {
            DB::table('bistro_articoli')->whereIn('categoria', ['caffe', 'colazione', 'bar'])->update(['reparto_id' => $bar]);
        }
        if ($ristorante = DB::table('reparti')->where('nome', 'Ristorante')->value('id')) {
            DB::table('bistro_articoli')->whereIn('categoria', ['antipasti', 'primi', 'secondi', 'dessert', 'bevande'])->update(['reparto_id' => $ristorante]);
        }

        // i piatti senza collegamento al magazzino non tengono scorta
        DB::table('bistro_articoli')->whereIn('categoria', self::PIATTI)
            ->whereNotIn('id', DB::table('bistro_ricette')->select('bistro_articolo_id'))->update(['senza_scorta' => true]);
    }

    public function down(): void
    {
        Schema::table('bistro_articoli', function (Blueprint $table) {
            $table->dropColumn('senza_scorta');
        });
    }
};
