<?php

namespace App\Support;

use App\Models\AlloggiatiComune;
use App\Models\AlloggiatiDocumento;
use App\Models\AlloggiatiStato;
use App\Models\InvioAlloggiati;
use Carbon\Carbon;

/**
 * Genera il file TXT a lunghezza fissa (168 char/riga) per Alloggiati Web.
 *
 * Tracciato ufficiale (D.M. 7/1/2013 e D.M. 16/9/2021):
 *  0-1   Tipo alloggiato (16/17/18/19/20)
 *  2-11  Data arrivo gg/mm/aaaa
 * 12-13  Giorni permanenza (01-30)
 * 14-63  Cognome (50)
 * 64-93  Nome (30)
 * 94     Sesso 1=M 2=F
 * 95-104 Data nascita gg/mm/aaaa
 *105-113 Comune nascita (codice 9) — solo se nato in Italia, altrimenti blank
 *114-115 Provincia nascita (2) — solo se nato in Italia
 *116-124 Stato nascita (codice 9) — sempre
 *125-133 Cittadinanza (codice 9) — sempre
 *134-138 Tipo documento (5) — blank per 19/20
 *139-158 Numero documento (20) — blank per 19/20
 *159-167 Luogo rilascio (codice 9) — blank per 19/20
 *
 * CR+LF dopo ogni riga tranne l'ultima.
 *
 * I codici comuni/stati/documenti vanno presi dalle tabelle ufficiali del portale
 * (php artisan alloggiati:import-tabelle). Finché le tabelle sono vuote si usano
 * fallback documentati (ISO-2 per stati, MAP_APP per documenti).
 */
class SchedineTxt
{
    /** Fallback ISO-2 → codice 9 char (placeholder finché non importi la tabella ufficiale). */
    private const STATI_FALLBACK = [
        'IT' => '100000001',
        'PK' => '100000586',
        'FR' => '100000250',
        'DE' => '100000276',
        'ES' => '100000724',
        'GB' => '100000826',
        'US' => '100000840',
        'CH' => '100000756',
        'AT' => '100000040',
        'NL' => '100000528',
        'BE' => '100000056',
        'PT' => '100000620',
        'PL' => '100000616',
        'RO' => '100000642',
        'CN' => '100000156',
        'JP' => '100000392',
        'BR' => '100000076',
        'AR' => '100000032',
        'RU' => '100000643',
        'IN' => '100000356',
        'MA' => '100000504',
        'TN' => '100000788',
        'AL' => '100000008',
        'UA' => '100000804',
        'EG' => '100000818',
        'TR' => '100000792',
        'GR' => '100000300',
        'SE' => '100000752',
        'NO' => '100000578',
        'DK' => '100000208',
        'IE' => '100000372',
        'CZ' => '100000203',
        'HU' => '100000348',
        'HR' => '100000191',
        'SI' => '100000705',
        'SK' => '100000703',
        'BG' => '100000100',
        'RS' => '100000688',
        'CA' => '100000124',
        'AU' => '100000036',
        'MX' => '100000484',
        'KR' => '100000410',
        'SA' => '100000682',
        'AE' => '100000784',
        'IL' => '100000376',
        'ZA' => '100000710',
    ];

    public static function genera(InvioAlloggiati $invio): string
    {
        $righe = [];
        foreach ($invio->schedine ?? [] as $r) {
            $righe[] = self::riga(is_array($r) ? $r : (array) $r);
        }

        if ($righe === []) {
            return '';
        }

        return implode("\r\n", $righe);
    }

    public static function nomeFile(InvioAlloggiati $invio): string
    {
        $ts = $invio->inviato_il instanceof Carbon
            ? $invio->inviato_il
            : Carbon::parse($invio->inviato_il);

        return 'alloggiati-' . $ts->format('Ymd-Hi') . '.txt';
    }

    /**
     * Genera una singola riga di 168 caratteri.
     * Accetta l'array prodotto da FrontDeskController::schedineDi().
     */
    public static function riga(array $r): string
    {
        $tipo = (int) ($r['tipo_alloggiato'] ?? 16);
        if (! in_array($tipo, [16, 17, 18, 19, 20], true)) {
            $tipo = 16;
        }
        $conDocumento = in_array($tipo, [16, 17, 18], true);

        $arrivo  = self::dataIt($r['arrivo'] ?? null);
        $giorni  = self::giorni($r);
        $cognome = self::pad(self::pulisci($r['cognome'] ?? ''), 50);
        $nome    = self::pad(self::pulisci($r['nome'] ?? ''), 30);
        $sesso   = self::sessoCodice($r['sesso'] ?? null);
        $nascita = self::dataIt($r['data_nascita'] ?? null);

        // --- Luogo di nascita ---
        $natoInItalia = filled($r['luogo_nascita_provincia'] ?? null)
            || strtoupper(substr((string) ($r['nazionalita'] ?? ''), 0, 2)) === 'IT';

        if ($natoInItalia) {
            $comune = AlloggiatiComune::trova(
                (string) ($r['luogo_nascita'] ?? ''),
                (string) ($r['luogo_nascita_provincia'] ?? '')
            );
            $comuneNascita = $comune ? $comune->codicePad() : self::pad('', 9);
            $provNascita   = self::pad(strtoupper(substr((string) ($r['luogo_nascita_provincia'] ?? ''), 0, 2)), 2);
            $statoNascita  = self::codiceStato('IT', 'ITALIA');
        } else {
            $comuneNascita = self::pad('', 9);
            $provNascita   = self::pad('', 2);
            $iso = strtoupper(substr((string) ($r['nazionalita'] ?? ''), 0, 2));
            $statoNascita  = self::codiceStato($iso, (string) ($r['luogo_nascita'] ?? ''));
        }

        // --- Cittadinanza ---
        $isoCit = strtoupper(substr((string) ($r['nazionalita'] ?? ''), 0, 2));
        $cittadinanza = self::codiceStato($isoCit, null);

        // --- Documento (solo 16/17/18) ---
        if ($conDocumento) {
            $tipoDoc = AlloggiatiDocumento::codicePerApp($r['tipo_documento'] ?? null);
            $numDoc  = self::pad(self::pulisci((string) ($r['numero_documento'] ?? '')), 20);

            if (filled($r['luogo_rilascio_provincia'] ?? null)) {
                // Rilasciato in Italia: codice comune se trovato, altrimenti codice Italia
                $comuneRil = AlloggiatiComune::trova(
                    (string) ($r['luogo_rilascio'] ?? ''),
                    (string) ($r['luogo_rilascio_provincia'] ?? '')
                );
                $luogoRilascio = $comuneRil
                    ? $comuneRil->codicePad()
                    : self::codiceStato('IT', 'ITALIA');
            } else {
                // Estero: codice stato
                $isoR = $isoCit;
                $luogoRilascio = self::codiceStato($isoR, (string) ($r['luogo_rilascio'] ?? ''));
            }
        } else {
            $tipoDoc       = self::pad('', 5);
            $numDoc        = self::pad('', 20);
            $luogoRilascio = self::pad('', 9);
        }

        $out = sprintf('%02d', $tipo)
            . $arrivo
            . sprintf('%02d', $giorni)
            . $cognome
            . $nome
            . $sesso
            . $nascita
            . $comuneNascita
            . $provNascita
            . $statoNascita
            . $cittadinanza
            . $tipoDoc
            . $numDoc
            . $luogoRilascio;

        return substr(str_pad($out, 168, ' '), 0, 168);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private static function codiceStato(?string $iso2, ?string $nome = null): string
    {
        $iso2 = $iso2 ? strtoupper(substr(trim($iso2), 0, 2)) : null;

        $stato = AlloggiatiStato::trova($iso2, $nome);
        if ($stato) {
            return $stato->codicePad();
        }

        if ($iso2 && isset(self::STATI_FALLBACK[$iso2])) {
            return self::pad(self::STATI_FALLBACK[$iso2], 9);
        }

        // Ultimo fallback: ISO-2 paddato (il portale può scartare → importa la tabella)
        return self::pad($iso2 ?? '', 9);
    }

    private static function giorni(array $r): int
    {
        if (isset($r['giorni_permanenza']) && $r['giorni_permanenza'] !== null && $r['giorni_permanenza'] !== '') {
            $g = (int) $r['giorni_permanenza'];
        } else {
            try {
                $g = (int) Carbon::parse($r['arrivo'] ?? 'now')
                    ->diffInDays(Carbon::parse($r['partenza'] ?? 'now'));
            } catch (\Throwable) {
                $g = 1;
            }
        }
        if ($g < 1) {
            $g = 1;
        }
        if ($g > 30) {
            $g = 30;
        }
        return $g;
    }

    private static function sessoCodice(?string $s): string
    {
        return match (strtoupper((string) $s)) {
            'M', '1' => '1',
            'F', '2' => '2',
            default  => '1',
        };
    }

    private static function dataIt(?string $d): string
    {
        if (! $d) {
            return str_repeat(' ', 10);
        }
        try {
            return Carbon::parse($d)->format('d/m/Y');
        } catch (\Throwable) {
            return str_repeat(' ', 10);
        }
    }

    private static function pad(string $s, int $len): string
    {
        $s = mb_substr($s, 0, $len);
        return str_pad($s, $len, ' ', STR_PAD_RIGHT);
    }

    private static function pulisci(string $s): string
    {
        $map = [
            'À'=>'A','Á'=>'A','Â'=>'A','Ä'=>'A','È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
            'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','Ò'=>'O','Ó'=>'O','Ô'=>'O','Ö'=>'O',
            'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U','Ç'=>'C','Ñ'=>'N',
            'à'=>'A','á'=>'A','â'=>'A','ä'=>'A','è'=>'E','é'=>'E','ê'=>'E','ë'=>'E',
            'ì'=>'I','í'=>'I','î'=>'I','ï'=>'I','ò'=>'O','ó'=>'O','ô'=>'O','ö'=>'O',
            'ù'=>'U','ú'=>'U','û'=>'U','ü'=>'U','ç'=>'C','ñ'=>'N',
        ];
        $s = strtr($s, $map);
        $s = preg_replace('/[^A-Za-z0-9 \-\']/u', '', $s) ?? $s;
        return strtoupper(trim($s));
    }
}
