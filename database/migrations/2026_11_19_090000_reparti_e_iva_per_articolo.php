<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Magazzini di reparto e IVA per articolo.
 *  - "reparti" (Bar, Ristorante...): ognuno ha le sue giacenze e le sue soglie, rifornite dal magazzino centrale con un trasferimento.
 *    La scorta di articoli_magazzino resta quella del magazzino CENTRALE (gli ordini ai fornitori lavorano su quella).
 *  - ogni articolo Bistro scarica dal suo reparto (non dal centrale) e può avere la sua aliquota IVA.
 *  - ogni incasso conserva il riepilogo IVA, anche per carta e contanti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reparti', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 60)->unique();
            $table->boolean('attivo')->default(true);
            $table->timestamps();
        });

        Schema::create('giacenze_reparto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reparto_id')->constrained('reparti')->cascadeOnDelete();
            $table->foreignId('articolo_id')->constrained('articoli_magazzino')->cascadeOnDelete();
            $table->decimal('scorta', 12, 3)->default(0);
            $table->decimal('soglia', 12, 3)->nullable();               // sotto questa scorta il reparto deve farsi rifornire
            $table->decimal('quantita_rifornimento', 12, 3)->nullable(); // quantità abituale da trasferire
            $table->timestamps();

            $table->unique(['reparto_id', 'articolo_id']);
        });

        Schema::table('movimenti_magazzino', function (Blueprint $table) {
            $table->foreignId('reparto_id')->nullable()->after('articolo_id')->constrained('reparti')->nullOnDelete(); // vuoto = magazzino centrale
        });

        Schema::table('bistro_articoli', function (Blueprint $table) {
            $table->foreignId('reparto_id')->nullable()->after('categoria')->constrained('reparti')->nullOnDelete();
            $table->decimal('aliquota_iva', 5, 2)->nullable()->after('prezzo');   // vuoto = aliquota ristorazione/predefinita dalla configurazione
        });

        Schema::table('bistro_comanda_righe', function (Blueprint $table) {
            $table->decimal('aliquota_iva', 5, 2)->nullable()->after('prezzo');   // copiata dall'articolo quando si ordina, come il prezzo
        });

        Schema::create('bistro_pagamento_iva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pagamento_id')->constrained('bistro_pagamenti')->cascadeOnDelete();
            $table->decimal('aliquota', 5, 2);
            $table->decimal('lordo', 10, 2);
            $table->decimal('imponibile', 10, 2);
            $table->decimal('imposta', 10, 2);
            $table->timestamps();
        });

        // Reparti di partenza e assegnazione degli articoli già presenti: punto cassa → Bar, tavoli → Ristorante
        $bar = DB::table('reparti')->insertGetId(['nome' => 'Bar', 'attivo' => true, 'created_at' => now(), 'updated_at' => now()]);
        $ristorante = DB::table('reparti')->insertGetId(['nome' => 'Ristorante', 'attivo' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bistro_articoli')->whereIn('categoria', ['caffe', 'colazione', 'bar', 'cucina'])->update(['reparto_id' => $bar]);
        DB::table('bistro_articoli')->whereNull('reparto_id')->update(['reparto_id' => $ristorante]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bistro_pagamento_iva');
        Schema::table('bistro_comanda_righe', fn (Blueprint $t) => $t->dropColumn('aliquota_iva'));
        Schema::table('bistro_articoli', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reparto_id');
            $table->dropColumn('aliquota_iva');
        });
        Schema::table('movimenti_magazzino', fn (Blueprint $t) => $t->dropConstrainedForeignId('reparto_id'));
        Schema::dropIfExists('giacenze_reparto');
        Schema::dropIfExists('reparti');
    }
};
