<?php

namespace App\Support;

use App\Models\InvioAlloggiati;

/**
 * PDF di riepilogo di un invio Alloggiati Web.
 * Mostra tutti i campi richiesti dal portale (art. 109 TULPS / D.M. 7 gennaio 2013).
 * Non è il file del tracciato ufficiale: è la ricevuta interna dei dati registrati.
 */
class SchedinePdf
{
    private const MARGINE = 40;
    private const FONDO = 780;

    private const DOCUMENTI = [
        'carta_identita' => "Carta d'identità",
        'passaporto'     => 'Passaporto',
        'patente'        => 'Patente',
    ];

    private const TIPI_ALLOGGIATO = [
        16 => 'Singolo',
        17 => 'Capofamiglia',
        18 => 'Capogruppo',
        19 => 'Familiare',
        20 => 'Membro gruppo',
    ];

    public static function genera(InvioAlloggiati $invio, string $hotel): string
    {
        $pdf = new PdfSemplice();
        $schedine = $invio->schedine ?? [];

        $y = self::intestazione($pdf, $invio, $hotel, count($schedine));

        if ($schedine === []) {
            $pdf->testo(self::MARGINE, $y, 'Nessuna schedina in questo invio.', 10);
        }

        foreach ($schedine as $i => $r) {
            $altezzaBlocco = self::altezzaBlocco($r);
            if ($y + $altezzaBlocco > self::FONDO) {
                $pdf->nuovaPagina();
                $y = 50;
            }
            $y = self::bloccoPersona($pdf, $r, $y, $i + 1);
            $y += 14;
        }

        $tot = $pdf->numeroPagine();
        for ($i = 0; $i < $tot; $i++) {
            $pdf->vaiAPagina($i);
            $pdf->linea(self::MARGINE, 806, 555, 806);
            $pdf->testo(self::MARGINE, 820, 'Copia di riepilogo dei dati registrati in Jonathan PMS: non è il file del tracciato Alloggiati Web.', 7.5, false, [120, 130, 140]);
            $testo = 'Pagina ' . ($i + 1) . ' di ' . $tot;
            $pdf->testo(555 - $pdf->larghezza($testo, 7.5), 820, $testo, 7.5, false, [120, 130, 140]);
        }

        return $pdf->output('Schedine Alloggiati Web ' . $invio->inviato_il->format('d-m-Y H:i'));
    }

    private static function intestazione(PdfSemplice $pdf, InvioAlloggiati $invio, string $hotel, int $n): float
    {
        $pdf->testo(self::MARGINE, 50, 'Alloggiati Web · Polizia di Stato', 16, true, [10, 34, 57]);
        $pdf->testo(self::MARGINE, 68, $hotel, 11, false, [77, 108, 134]);

        $tipo = $invio->massivo() ? 'Invio massivo' : 'Invio singolo';
        $meta = $tipo
            . '  ·  Inviato il ' . $invio->inviato_il->format('d/m/Y \a\l\l\e H:i')
            . '  ·  Giornata ' . $invio->data_riferimento->format('d/m/Y')
            . '  ·  ' . $n . ($n === 1 ? ' schedina' : ' schedine');
        $pdf->testo(self::MARGINE, 88, $meta, 9, false, [100, 110, 120]);
        $pdf->linea(self::MARGINE, 100, 555, 100, 0.8, [200, 205, 210]);

        return 118;
    }

    /** True se per questa persona il portale richiede il documento (16/17/18). */
    private static function richiedeDocumento(array $r): bool
    {
        if (array_key_exists('include_documento', $r)) {
            return (bool) $r['include_documento'];
        }
        $tipo = (int) ($r['tipo_alloggiato'] ?? 0);
        if (in_array($tipo, [16, 17, 18], true)) {
            return true;
        }
        if (in_array($tipo, [19, 20], true)) {
            return false;
        }
        // Snapshot vecchi senza tipo: il titolare lo richiede sempre
        $ruolo = mb_strtolower((string) ($r['ruolo'] ?? ''));
        if (str_contains($ruolo, 'titolare') || str_contains($ruolo, 'singol')) {
            return true;
        }
        return filled($r['numero_documento'] ?? null);
    }

    /** Giorni di permanenza: campo salvato, altrimenti calcolato da arrivo/partenza. */
    private static function giorniPermanenza(array $r): ?int
    {
        if (isset($r['giorni_permanenza']) && $r['giorni_permanenza'] !== null && $r['giorni_permanenza'] !== '') {
            return (int) $r['giorni_permanenza'];
        }
        $arrivo = $r['arrivo'] ?? null;
        $partenza = $r['partenza'] ?? null;
        if (! $arrivo || ! $partenza) {
            return null;
        }
        try {
            return (int) \Carbon\Carbon::parse($arrivo)->diffInDays(\Carbon\Carbon::parse($partenza));
        } catch (\Throwable) {
            return null;
        }
    }

    private static function tipoAlloggiatoLabel(array $r): string
    {
        $tipoCod = (int) ($r['tipo_alloggiato'] ?? 0);
        if (isset(self::TIPI_ALLOGGIATO[$tipoCod])) {
            return self::TIPI_ALLOGGIATO[$tipoCod] . ' (' . $tipoCod . ')';
        }
        return (string) ($r['ruolo'] ?? '—');
    }

    private static function altezzaBlocco(array $r): int
    {
        return self::richiedeDocumento($r) ? 96 : 78;
    }

    private static function bloccoPersona(PdfSemplice $pdf, array $r, float $y, int $n): float
    {
        $h = self::altezzaBlocco($r);
        $pdf->rettangolo(self::MARGINE, $y - 4, 515, $h, $n % 2 === 0 ? [248, 249, 251] : [255, 255, 255]);
        $pdf->linea(self::MARGINE, $y - 4, 555, $y - 4, 0.4, [220, 224, 230]);

        $nome = trim(($r['cognome'] ?? '') . ' ' . ($r['nome'] ?? ''));
        $camera = (string) ($r['camera'] ?? '—');

        $pdf->testo(self::MARGINE + 6, $y + 12, $n . '.  ' . ($nome !== '' ? $nome : '—'), 11, true, [10, 34, 57]);
        $pdf->testo(self::MARGINE + 280, $y + 12, 'Camera ' . $camera, 9, false, [80, 90, 100]);
        $pdf->testo(self::MARGINE + 370, $y + 12, self::tipoAlloggiatoLabel($r), 9, false, [80, 90, 100]);

        $y += 28;
        $col1 = self::MARGINE + 6;
        $col2 = self::MARGINE + 175;
        $col3 = self::MARGINE + 340;

        self::campo($pdf, $col1, $y, 'Sesso', self::sesso($r['sesso'] ?? null));
        self::campo($pdf, $col2, $y, 'Data di nascita', self::data($r['data_nascita'] ?? null));
        self::campo($pdf, $col3, $y, 'Luogo di nascita', self::luogo(
            $r['luogo_nascita'] ?? null,
            $r['luogo_nascita_provincia'] ?? null
        ));
        $y += 18;

        self::campo($pdf, $col1, $y, 'Cittadinanza', (string) ($r['nazionalita'] ?? '—'));
        self::campo($pdf, $col2, $y, 'Arrivo', self::data($r['arrivo'] ?? null));
        $giorni = self::giorniPermanenza($r);
        self::campo($pdf, $col3, $y, 'Permanenza', $giorni !== null ? ($giorni . ($giorni === 1 ? ' giorno' : ' giorni')) : '—');
        $y += 18;

        if (self::richiedeDocumento($r)) {
            $tipoDoc = self::DOCUMENTI[$r['tipo_documento'] ?? ''] ?? (($r['tipo_documento'] ?? '') !== '' ? $r['tipo_documento'] : '—');
            $numDoc  = (string) (($r['numero_documento'] ?? '') !== '' ? $r['numero_documento'] : '—');
            $luogoR  = self::luogo($r['luogo_rilascio'] ?? null, $r['luogo_rilascio_provincia'] ?? null);
            self::campo($pdf, $col1, $y, 'Documento', (string) $tipoDoc);
            self::campo($pdf, $col2, $y, 'Numero', $numDoc);
            self::campo($pdf, $col3, $y, 'Luogo di rilascio', $luogoR);
            $y += 18;
        } else {
            $pdf->testo($col1, $y, 'Documento non richiesto per questo tipo di alloggiato', 8, false, [140, 150, 160]);
            $y += 16;
        }

        return $y + 6;
    }

    private static function campo(PdfSemplice $pdf, float $x, float $y, string $etichetta, string $valore): void
    {
        $pdf->testo($x, $y, $etichetta, 7.5, false, [130, 140, 150]);
        $pdf->testo($x, $y + 10, $pdf->adatta($valore !== '' ? $valore : '—', 150, 9), 9, false, [20, 30, 40]);
    }

    private static function sesso(?string $s): string
    {
        return match (strtoupper((string) $s)) {
            'M' => 'Maschio',
            'F' => 'Femmina',
            default => '—',
        };
    }

    private static function luogo(?string $luogo, ?string $provincia): string
    {
        $luogo = trim((string) $luogo);
        $provincia = strtoupper(trim((string) $provincia));
        if ($luogo === '' && $provincia === '') {
            return '—';
        }
        if ($provincia !== '') {
            return $luogo !== '' ? $luogo . ' (' . $provincia . ')' : $provincia;
        }
        return $luogo;
    }

    private static function data(?string $d): string
    {
        if (! $d) {
            return '—';
        }
        try {
            return \Carbon\Carbon::parse($d)->format('d/m/Y');
        } catch (\Throwable) {
            return $d;
        }
    }
}
