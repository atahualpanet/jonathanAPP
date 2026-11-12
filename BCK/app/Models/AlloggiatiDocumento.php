<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlloggiatiDocumento extends Model
{
    protected $table = 'alloggiati_documenti';

    protected $fillable = ['codice', 'nome'];

    /** Mappa valori app → codice portale (fallback se tabella vuota). */
    public const MAP_APP = [
        'carta_identita' => 'IDENT',
        'passaporto'     => 'PASOR',
        'patente'        => 'PATEN',
        'permesso_soggiorno' => 'PERSO',
        'altro'          => 'ALTRO',
    ];

    public static function codicePerApp(?string $tipoApp): string
    {
        if (! $tipoApp) {
            return str_repeat(' ', 5);
        }

        // Prima cerca in tabella per nome/codice
        $upper = strtoupper(trim($tipoApp));
        $row = static::where('codice', $upper)->first()
            ?? static::whereRaw('UPPER(nome) LIKE ?', ['%' . $upper . '%'])->first();

        if ($row) {
            return str_pad(substr($row->codice, 0, 5), 5, ' ', STR_PAD_RIGHT);
        }

        $cod = self::MAP_APP[$tipoApp] ?? self::MAP_APP[strtolower($tipoApp)] ?? null;
        if ($cod) {
            return str_pad($cod, 5, ' ', STR_PAD_RIGHT);
        }

        return str_repeat(' ', 5);
    }
}
