<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Assicura che le tabelle di gestione Reparti, Giacenze, Richieste, Trasferimenti
 * e i campi Bistro esistano nel database anche se le vecchie migrazioni risultavano
 * già registrate nella tabella `migrations` di Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabella reparti
        if (!Schema::hasTable('reparti')) {
            Schema::create('reparti', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 60)->unique();
                $table->boolean('attivo')->default(true);
                $table->timestamps();
            });

            // Popola i reparti predefiniti Bar e Ristorante
            DB::table('reparti')->insertOrIgnore([
                ['nome' => 'Bar', 'attivo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nome' => 'Ristorante', 'attivo' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // 2. Tabella giacenze_reparto
        if (!Schema::hasTable('giacenze_reparto')) {
            Schema::create('giacenze_reparto', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reparto_id')->constrained('reparti')->cascadeOnDelete();
                $table->foreignId('articolo_id')->constrained('articoli_magazzino')->cascadeOnDelete();
                $table->decimal('scorta', 12, 3)->default(0);
                $table->decimal('soglia', 12, 3)->nullable();
                $table->decimal('quantita_rifornimento', 12, 3)->nullable();
                $table->timestamps();

                $table->unique(['reparto_id', 'articolo_id']);
            });
        }

        // 3. Tabella trasferimenti_reparto
        if (!Schema::hasTable('trasferimenti_reparto')) {
            Schema::create('trasferimenti_reparto', function (Blueprint $table) {
                $table->id();
                $table->foreignId('articolo_id')->constrained('articoli_magazzino')->restrictOnDelete();
                $table->foreignId('reparto_id')->constrained('reparti')->restrictOnDelete();
                $table->decimal('quantita_inviata', 12, 3);
                $table->decimal('quantita_ricevuta', 12, 3)->nullable();
                $table->enum('stato', ['in_arrivo', 'ricevuto', 'annullato'])->default('in_arrivo');
                $table->foreignId('inviato_da')->nullable()->constrained('utenti')->nullOnDelete();
                $table->foreignId('ricevuto_da')->nullable()->constrained('utenti')->nullOnDelete();
                $table->timestamp('inviato_il')->nullable();
                $table->timestamp('ricevuto_il')->nullable();
                $table->string('nota', 255)->nullable();
                $table->timestamps();

                $table->index(['reparto_id', 'stato']);
            });
        }

        // 4. Tabella richieste_reparto
        if (!Schema::hasTable('richieste_reparto')) {
            Schema::create('richieste_reparto', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reparto_id')->constrained('reparti')->cascadeOnDelete();
                $table->foreignId('articolo_id')->constrained('articoli_magazzino')->cascadeOnDelete();
                $table->decimal('quantita', 12, 3);
                $table->enum('stato', ['aperta', 'evasa', 'annullata'])->default('aperta');
                $table->boolean('automatica')->default(false);
                $table->foreignId('richiesta_da')->nullable()->constrained('utenti')->nullOnDelete();
                $table->timestamp('richiesta_il')->nullable();
                $table->timestamp('evasa_il')->nullable();
                $table->unsignedBigInteger('trasferimento_id')->nullable();
                $table->timestamps();

                $table->index(['stato', 'reparto_id']);
            });
        }

        // 5. Tabella bistro_pagamento_iva
        if (!Schema::hasTable('bistro_pagamento_iva') && Schema::hasTable('bistro_pagamenti')) {
            Schema::create('bistro_pagamento_iva', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pagamento_id')->constrained('bistro_pagamenti')->cascadeOnDelete();
                $table->decimal('aliquota', 5, 2);
                $table->decimal('lordo', 10, 2);
                $table->decimal('imponibile', 10, 2);
                $table->decimal('imposta', 10, 2);
                $table->timestamps();
            });
        }

        // 6. Colonne su movimenti_magazzino
        if (Schema::hasTable('movimenti_magazzino')) {
            Schema::table('movimenti_magazzino', function (Blueprint $table) {
                if (!Schema::hasColumn('movimenti_magazzino', 'reparto_id')) {
                    $table->foreignId('reparto_id')->nullable()->after('articolo_id')->constrained('reparti')->nullOnDelete();
                }
                if (!Schema::hasColumn('movimenti_magazzino', 'nota')) {
                    $table->string('nota', 255)->nullable()->after('riferimento');
                }
            });
        }

        // 7. Colonne su bistro_articoli
        if (Schema::hasTable('bistro_articoli')) {
            Schema::table('bistro_articoli', function (Blueprint $table) {
                if (!Schema::hasColumn('bistro_articoli', 'reparto_id')) {
                    $table->foreignId('reparto_id')->nullable()->after('categoria')->constrained('reparti')->nullOnDelete();
                }
                if (!Schema::hasColumn('bistro_articoli', 'aliquota_iva')) {
                    $table->decimal('aliquota_iva', 5, 2)->nullable()->after('prezzo');
                }
                if (!Schema::hasColumn('bistro_articoli', 'senza_scorta')) {
                    $table->boolean('senza_scorta')->default(false)->after('aliquota_iva');
                }
            });

            // Assegna il reparto agli articoli bistro se non assegnato
            if ($barId = DB::table('reparti')->where('nome', 'Bar')->value('id')) {
                DB::table('bistro_articoli')
                    ->whereNull('reparto_id')
                    ->whereIn('categoria', ['caffe', 'colazione', 'bar'])
                    ->update(['reparto_id' => $barId]);
            }
            if ($ristoranteId = DB::table('reparti')->where('nome', 'Ristorante')->value('id')) {
                DB::table('bistro_articoli')
                    ->whereNull('reparto_id')
                    ->update(['reparto_id' => $ristoranteId]);
            }
        }

        // 8. Colonne su bistro_comanda_righe
        if (Schema::hasTable('bistro_comanda_righe')) {
            Schema::table('bistro_comanda_righe', function (Blueprint $table) {
                if (!Schema::hasColumn('bistro_comanda_righe', 'aliquota_iva')) {
                    $table->decimal('aliquota_iva', 5, 2)->nullable()->after('prezzo');
                }
            });
        }
    }

    public function down(): void
    {
        // Migrazione conservativa: non eliminare tabelle o dati
    }
};
