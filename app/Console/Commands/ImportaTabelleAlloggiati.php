<?php

namespace App\Console\Commands;

use App\Models\AlloggiatiComune;
use App\Models\AlloggiatiDocumento;
use App\Models\AlloggiatiStato;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importa le tabelle ufficiali Alloggiati Web scaricate dal portale.
 *
 * I file del portale sono tipicamente CSV/TXT delimitati (punto e virgola o tab)
 * con colonne variabili. Questo comando gestisce i formati più comuni:
 *
 *   Stati:     codice;nome[;iso2][;cessato]
 *   Comuni:    codice;nome;provincia[;cessato]
 *   Documenti: codice;nome
 *
 * Uso:
 *   php artisan alloggiati:import-tabelle \
 *     --stati=storage/app/alloggiati/stati.txt \
 *     --comuni=storage/app/alloggiati/comuni.txt \
 *     --documenti=storage/app/alloggiati/documenti.txt
 *
 * Oppure un file alla volta. Con --truncate svuota la tabella prima dell'import.
 */
class ImportaTabelleAlloggiati extends Command
{
    protected $signature = 'alloggiati:import-tabelle
        {--stati= : Path file tabella Stati}
        {--comuni= : Path file tabella Comuni}
        {--documenti= : Path file tabella Documenti}
        {--truncate : Svuota le tabelle prima di importare}
        {--delimiter=; : Delimitatore colonne}';

    protected $description = 'Importa tabelle codici Alloggiati Web (Stati, Comuni, Documenti)';

    public function handle(): int
    {
        $delim = $this->option('delimiter') ?: ';';
        $ok = 0;

        if ($path = $this->option('stati')) {
            $ok += $this->importStati($path, $delim);
        }
        if ($path = $this->option('comuni')) {
            $ok += $this->importComuni($path, $delim);
        }
        if ($path = $this->option('documenti')) {
            $ok += $this->importDocumenti($path, $delim);
        }

        if ($ok === 0 && ! $this->option('stati') && ! $this->option('comuni') && ! $this->option('documenti')) {
            $this->warn('Specifica almeno un file: --stati= --comuni= --documenti=');
            $this->line('Scarica le tabelle da: https://alloggiatiweb.poliziadistato.it/portalealloggiati/tabelle.aspx');
            return self::FAILURE;
        }

        $this->info('Import completato.');
        $this->table(
            ['Tabella', 'Righe'],
            [
                ['Stati', AlloggiatiStato::count()],
                ['Comuni', AlloggiatiComune::count()],
                ['Documenti', AlloggiatiDocumento::count()],
            ]
        );

        return self::SUCCESS;
    }

    private function importStati(string $path, string $delim): int
    {
        $lines = $this->leggiRighe($path);
        if ($lines === null) {
            return 0;
        }

        if ($this->option('truncate')) {
            AlloggiatiStato::truncate();
        }

        $n = 0;
        foreach ($lines as $line) {
            $cols = $this->split($line, $delim);
            if (count($cols) < 2) {
                continue;
            }
            $codice = substr(trim($cols[0]), 0, 9);
            $nome   = mb_substr(trim($cols[1]), 0, 120);
            if ($codice === '' || $nome === '' || ! ctype_alnum(str_replace(' ', '', $codice))) {
                // salta intestazioni
                if (stripos($nome, 'nome') !== false || stripos($codice, 'cod') !== false) {
                    continue;
                }
            }
            if ($codice === '' || $nome === '') {
                continue;
            }

            $iso2 = isset($cols[2]) ? strtoupper(substr(trim($cols[2]), 0, 2)) : null;
            if ($iso2 !== null && ! preg_match('/^[A-Z]{2}$/', $iso2)) {
                $iso2 = null;
            }
            $cessato = isset($cols[3]) && in_array(strtolower(trim($cols[3])), ['1', 's', 'si', 'true', 'y'], true);

            AlloggiatiStato::updateOrCreate(
                ['codice' => $codice],
                ['nome' => $nome, 'iso2' => $iso2, 'cessato' => $cessato]
            );
            $n++;
        }

        $this->info("Stati: {$n} righe.");
        return $n;
    }

    private function importComuni(string $path, string $delim): int
    {
        $lines = $this->leggiRighe($path);
        if ($lines === null) {
            return 0;
        }

        if ($this->option('truncate')) {
            AlloggiatiComune::truncate();
        }

        $n = 0;
        foreach ($lines as $line) {
            $cols = $this->split($line, $delim);
            if (count($cols) < 3) {
                continue;
            }
            $codice    = substr(trim($cols[0]), 0, 9);
            $nome      = mb_substr(trim($cols[1]), 0, 120);
            $provincia = strtoupper(substr(trim($cols[2]), 0, 2));
            if ($codice === '' || $nome === '' || $provincia === '') {
                continue;
            }
            if (stripos($nome, 'nome') !== false || stripos($codice, 'cod') !== false) {
                continue;
            }

            $cessato = isset($cols[3]) && in_array(strtolower(trim($cols[3])), ['1', 's', 'si', 'true', 'y'], true);

            AlloggiatiComune::updateOrCreate(
                ['codice' => $codice],
                ['nome' => $nome, 'provincia' => $provincia, 'cessato' => $cessato]
            );
            $n++;
        }

        $this->info("Comuni: {$n} righe.");
        return $n;
    }

    private function importDocumenti(string $path, string $delim): int
    {
        $lines = $this->leggiRighe($path);
        if ($lines === null) {
            return 0;
        }

        if ($this->option('truncate')) {
            AlloggiatiDocumento::truncate();
        }

        $n = 0;
        foreach ($lines as $line) {
            $cols = $this->split($line, $delim);
            if (count($cols) < 2) {
                continue;
            }
            $codice = strtoupper(substr(trim($cols[0]), 0, 5));
            $nome   = mb_substr(trim($cols[1]), 0, 120);
            if ($codice === '' || $nome === '') {
                continue;
            }
            if (stripos($nome, 'nome') !== false || stripos($codice, 'cod') !== false) {
                continue;
            }

            AlloggiatiDocumento::updateOrCreate(
                ['codice' => $codice],
                ['nome' => $nome]
            );
            $n++;
        }

        $this->info("Documenti: {$n} righe.");
        return $n;
    }

    private function leggiRighe(string $path): ?array
    {
        if (! is_file($path)) {
            $this->error("File non trovato: {$path}");
            return null;
        }
        $content = file_get_contents($path);
        // Rimuovi BOM UTF-8
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
        return array_values(array_filter($lines, fn ($l) => trim($l) !== ''));
    }

    private function split(string $line, string $delim): array
    {
        // Supporta anche TAB
        if (str_contains($line, "\t") && $delim === ';') {
            return explode("\t", $line);
        }
        return str_getcsv($line, $delim);
    }
}
