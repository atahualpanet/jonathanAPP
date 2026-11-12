<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabella dei ruoli (profili): modificabili dalla pagina "Permessi per profilo"
        Schema::create('ruoli', function (Blueprint $table) {
            $table->id();
            $table->string('chiave', 50)->unique();   // valore salvato in utenti.ruolo (minuscolo, non cambia mai)
            $table->string('nome', 100);              // etichetta mostrata, rinominabile
            $table->json('moduli')->nullable();       // moduli della suite visibili
            $table->json('funzioni')->nullable();     // funzioni consentite (chiavi di config/permessi.php → 'funzioni')
            $table->boolean('gestore')->default(false); // può aprire "Utenti e permessi"
            $table->unsignedSmallInteger('ordine')->default(0);
            $table->timestamps();
        });

        $tutte = ['prenotazioni', 'folio', 'tariffe', 'stato_camere', 'guasti', 'comande', 'kpi'];
        $tuttiModuli = ['frontdesk', 'direct', 'sync', 'housekeeping', 'bistro', 'intelligence'];

        $iniziali = [
            ['amministratore', 'Amministratore', $tuttiModuli, $tutte, true],
            ['direttore',      'Direttore',      $tuttiModuli, $tutte, true],
            ['reception',      'Reception',      ['frontdesk', 'direct', 'sync'], ['prenotazioni', 'folio', 'tariffe', 'stato_camere', 'guasti'], false],
            ['housekeeping',   'Housekeeping',   ['housekeeping'], ['stato_camere', 'guasti'], false],
            ['barman',         'Barman',         ['bistro'], ['comande'], false],
            ['manutentore',    'Manutentore',    ['housekeeping'], ['guasti'], false],
        ];

        foreach ($iniziali as $i => [$chiave, $nome, $moduli, $funzioni, $gestore]) {
            DB::table('ruoli')->insert([
                'chiave'     => $chiave,
                'nome'       => $nome,
                'moduli'     => json_encode($moduli),
                'funzioni'   => json_encode($funzioni),
                'gestore'    => $gestore,
                'ordine'     => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. utenti.ruolo non è più un ENUM fisso: accetta qualunque chiave presente in `ruoli`
        Schema::table('utenti', function (Blueprint $table) {
            $table->string('ruolo', 50)->default('reception')->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ruoli');
        // il ruolo resta stringa: tornare all'ENUM farebbe fallire gli utenti con ruoli personalizzati
    }
};
