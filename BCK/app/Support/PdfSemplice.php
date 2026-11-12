<?php

namespace App\Support;

/**
 * Scrittore PDF minimale (solo testo, linee e rettangoli, font standard Helvetica)
 * per i documenti semplici dell'applicazione, senza dipendenze esterne.
 * Coordinate in punti, origine in ALTO a sinistra, pagina A4 verticale.
 */
class PdfSemplice
{
    public const LARGHEZZA = 595.28;
    public const ALTEZZA   = 841.89;

    /** Larghezze Helvetica (1/1000 em) per i caratteri ASCII 32..126. */
    private const W = [
        278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
        1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
        333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
        556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
    ];

    /** @var array<int, string> contenuto di ogni pagina */
    private array $pagine = [''];
    private int $corrente = 0;

    public function nuovaPagina(): void
    {
        $this->pagine[] = '';
        $this->corrente = count($this->pagine) - 1;
    }

    public function vaiAPagina(int $indice): void
    {
        $this->corrente = max(0, min($indice, count($this->pagine) - 1));
    }

    public function numeroPagine(): int
    {
        return count($this->pagine);
    }

    public function testo(float $x, float $y, string $s, float $size = 10, bool $grassetto = false, array $rgb = [0, 0, 0]): void
    {
        $py = self::ALTEZZA - $y;
        $this->pagine[$this->corrente] .= sprintf(
            "BT /%s %.2F Tf %.3F %.3F %.3F rg %.2F %.2F Td (%s) Tj ET\n",
            $grassetto ? 'F2' : 'F1', $size, $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255, $x, $py, $this->escape($s)
        );
    }

    public function linea(float $x1, float $y1, float $x2, float $y2, float $spessore = 0.5, array $rgb = [200, 200, 200]): void
    {
        $this->pagine[$this->corrente] .= sprintf(
            "%.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S\n",
            $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255, $spessore, $x1, self::ALTEZZA - $y1, $x2, self::ALTEZZA - $y2
        );
    }

    public function rettangolo(float $x, float $y, float $w, float $h, array $rgb): void
    {
        $this->pagine[$this->corrente] .= sprintf(
            "%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f\n",
            $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255, $x, self::ALTEZZA - $y - $h, $w, $h
        );
    }

    /** Larghezza approssimata del testo (i caratteri accentati contano come lettere medie). */
    public function larghezza(string $s, float $size = 10, bool $grassetto = false): float
    {
        $tot = 0;
        foreach (preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) as $c) {
            $o = mb_ord($c);
            $tot += ($o >= 32 && $o <= 126) ? self::W[$o - 32] : 556;
        }
        return $tot * $size / 1000 * ($grassetto ? 1.06 : 1.0);
    }

    /** Accorcia con "…" finché il testo sta nella larghezza data. */
    public function adatta(string $s, float $maxLarghezza, float $size = 10, bool $grassetto = false): string
    {
        if ($this->larghezza($s, $size, $grassetto) <= $maxLarghezza) {
            return $s;
        }
        while ($s !== '' && $this->larghezza($s . '...', $size, $grassetto) > $maxLarghezza) {
            $s = mb_substr($s, 0, -1);
        }
        return rtrim($s) . '...';
    }

    /** Il PDF completo, come stringa binaria. */
    public function output(string $titolo = ''): string
    {
        $oggetti = [];
        $oggetti[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        $n = count($this->pagine);
        for ($i = 0; $i < $n; $i++) {
            $kids[] = (5 + $i * 2) . ' 0 R';
        }
        $oggetti[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $n . ' >>';
        $oggetti[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $oggetti[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        foreach ($this->pagine as $i => $contenuto) {
            $idPagina = 5 + $i * 2;
            $idContenuto = $idPagina + 1;
            $oggetti[$idPagina] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::LARGHEZZA, self::ALTEZZA, $idContenuto
            );
            $oggetti[$idContenuto] = '<< /Length ' . strlen($contenuto) . " >>\nstream\n" . $contenuto . 'endstream';
        }

        $idInfo = 5 + $n * 2;
        $oggetti[$idInfo] = '<< /Title (' . $this->escape($titolo) . ') /Producer (Jonathan PMS) >>';

        $pdf = "%PDF-1.4\n";
        $offset = [];
        foreach ($oggetti as $id => $corpo) {
            $offset[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $corpo . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . ($idInfo + 1) . "\n0000000000 65535 f \n";
        for ($id = 1; $id <= $idInfo; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offset[$id]);
        }
        $pdf .= "trailer\n<< /Size " . ($idInfo + 1) . " /Root 1 0 R /Info {$idInfo} 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    /** UTF-8 -> WinAnsi (CP1252) con escape delle parentesi e del backslash. */
    private function escape(string $s): string
    {
        $s = str_replace(["\r", "\n", "\t"], ' ', $s);
        $conv = mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $conv);
    }
}
