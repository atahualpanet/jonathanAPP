<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Utente extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $table = 'utenti';

    protected $fillable = [
        'nome',
        'cognome',
        'email',
        'password',
        'ruolo',
        'attivo',
        'deve_cambiare_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'attivo' => 'boolean',
            'deve_cambiare_password' => 'boolean',
        ];
    }

    public function nomeCompleto(): string
    {
        return trim($this->nome . ' ' . $this->cognome);
    }

    /** Il profilo (riga di `ruoli`) di questo utente, o null se il valore in utenti.ruolo non esiste. */
    public function profilo(): ?Ruolo
    {
        return Ruolo::perChiave($this->ruolo);
    }

    /** Può aprire "Utenti e permessi". */
    public function isGestore(): bool
    {
        if (Ruolo::tutti()->isEmpty()) {
            // tabella `ruoli` non ancora creata: si comportano da gestori i due ruoli di vertice
            return in_array(Str::lower((string) $this->ruolo), ['amministratore', 'direttore'], true);
        }
        return (bool) $this->profilo()?->gestore;
    }

    /** Controllo di una funzione della matrice, es. $utente->puo('folio'). */
    public function puo(string $funzione): bool
    {
        return in_array($funzione, $this->profilo()?->funzioni ?? [], true);
    }
}
