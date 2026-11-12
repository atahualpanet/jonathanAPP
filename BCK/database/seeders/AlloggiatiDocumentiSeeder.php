<?php

namespace Database\Seeders;

use App\Models\AlloggiatiDocumento;
use Illuminate\Database\Seeder;

/**
 * Codici documento tipici del portale Alloggiati Web.
 * Sostituisci/integra con la tabella ufficiale dopo l'import.
 */
class AlloggiatiDocumentiSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['codice' => 'IDENT', 'nome' => 'CARTA DI IDENTITA'],
            ['codice' => 'PASOR', 'nome' => 'PASSAPORTO'],
            ['codice' => 'PATEN', 'nome' => 'PATENTE DI GUIDA'],
            ['codice' => 'PERSO', 'nome' => 'PERMESSO DI SOGGIORNO'],
            ['codice' => 'CIDEI', 'nome' => 'CARTA IDENTITA ELETTRONICA'],
            ['codice' => 'ALTRO', 'nome' => 'ALTRO DOCUMENTO'],
        ];

        foreach ($rows as $r) {
            AlloggiatiDocumento::updateOrCreate(
                ['codice' => $r['codice']],
                ['nome' => $r['nome']]
            );
        }
    }
}
