<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlloggiatiStato extends Model
{
    protected $table = 'alloggiati_stati';

    protected $fillable = ['codice', 'nome', 'iso2', 'cessato'];

    protected $casts = ['cessato' => 'boolean'];

    /** Lookup per ISO-2 (es. IT, PK) o per nome parziale. */
    public static function trova(?string $iso2 = null, ?string $nome = null): ?self
    {
        if ($iso2) {
            $iso2 = strtoupper(substr(trim($iso2), 0, 2));
            $s = static::where('iso2', $iso2)->where('cessato', false)->first();
            if ($s) {
                return $s;
            }
        }
        if ($nome) {
            $nome = trim($nome);
            return static::where('cessato', false)
                ->where(function ($q) use ($nome) {
                    $q->where('nome', 'like', $nome)
                      ->orWhere('nome', 'like', $nome . '%');
                })
                ->orderByRaw('LENGTH(nome) ASC')
                ->first();
        }
        return null;
    }

    public function codicePad(): string
    {
        return str_pad(substr($this->codice, 0, 9), 9, ' ', STR_PAD_RIGHT);
    }
}
