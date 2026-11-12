<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Ruolo extends Model
{
    protected $table = 'ruoli';

    protected $fillable = ['chiave', 'nome', 'moduli', 'funzioni', 'gestore', 'ordine'];

    protected function casts(): array
    {
        return [
            'moduli'   => 'array',
            'funzioni' => 'array',
            'gestore'  => 'boolean',
        ];
    }

    private static ?Collection $cache = null;

    /** Tutti i ruoli in ordine, una sola query per richiesta. Vuoto se la migrazione non è ancora stata eseguita. */
    public static function tutti(): Collection
    {
        if (self::$cache === null) {
            try {
                self::$cache = Schema::hasTable('ruoli')
                    ? static::orderBy('ordine')->orderBy('id')->get()->keyBy('chiave')
                    : collect();
            } catch (\Throwable $e) {
                self::$cache = collect();
            }
        }
        return self::$cache;
    }

    public static function perChiave(?string $chiave): ?self
    {
        return self::tutti()->get(Str::lower((string) $chiave));
    }

    public static function dimentica(): void
    {
        self::$cache = null;
    }
}
