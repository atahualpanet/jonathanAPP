<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlloggiatiComune extends Model
{
    protected $table = 'alloggiati_comuni';

    protected $fillable = ['codice', 'nome', 'provincia', 'cessato'];

    protected $casts = ['cessato' => 'boolean'];

    /**
     * Cerca comune per nome (+ provincia opzionale).
     * Preferisce match esatto, poi prefisso; esclude cessati se possibile.
     */
    public static function trova(string $nome, ?string $provincia = null): ?self
    {
        $nome = mb_strtoupper(trim($nome));
        $provincia = $provincia ? strtoupper(substr(trim($provincia), 0, 2)) : null;

        $q = static::query()->where('cessato', false);

        if ($provincia) {
            $q->where('provincia', $provincia);
        }

        // Match esatto (case-insensitive)
        $exact = (clone $q)->whereRaw('UPPER(nome) = ?', [$nome])->first();
        if ($exact) {
            return $exact;
        }

        // Prefisso
        $pref = (clone $q)->whereRaw('UPPER(nome) LIKE ?', [$nome . '%'])
            ->orderByRaw('LENGTH(nome) ASC')
            ->first();
        if ($pref) {
            return $pref;
        }

        // Senza filtro provincia se non trovato
        if ($provincia) {
            return static::trova($nome, null);
        }

        return null;
    }

    public function codicePad(): string
    {
        return str_pad(substr($this->codice, 0, 9), 9, ' ', STR_PAD_RIGHT);
    }
}
