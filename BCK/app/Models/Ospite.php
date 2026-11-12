<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ospite extends Model
{
    use SoftDeletes;

    protected $table = 'ospiti';

    protected $casts = [
        'data_nascita'         => 'date',
        'scadenza_documento'   => 'date',
        'privacy_accettata_il' => 'datetime',
    ];

    protected $fillable = [
        'nome',
        'cognome',
        'email',
        'telefono',
        'data_nascita',
        'sesso',
        'luogo_nascita',
        'luogo_nascita_provincia',
        'nazionalita',
        'tipo_documento',
        'numero_documento',
        'luogo_rilascio',
        'luogo_rilascio_provincia',
        'ente_rilascio',          // legacy
        'scadenza_documento',
        'documento_file',
        'indirizzo',
        'citta',
        'cap',
        'paese',
        'note',
        'privacy_accettata',
        'privacy_accettata_il',
    ];

    // ------------------------------------------------------------------
    // Helper Alloggiati Web
    // ------------------------------------------------------------------

    public function natoInItalia(): bool
    {
        return filled($this->luogo_nascita_provincia);
    }

    public function documentoRilasciatoInItalia(): bool
    {
        return filled($this->luogo_rilascio_provincia);
    }

    /** Anagrafica minima Alloggiati (tutti la devono avere). */
    public function anagraficaCompleta(): bool
    {
        return filled($this->nome)
            && filled($this->cognome)
            && in_array($this->sesso, ['M', 'F'], true)
            && $this->data_nascita
            && filled($this->luogo_nascita)
            && filled($this->nazionalita);
    }

    /** Documento completo (tipo + numero + luogo rilascio). In app obbligatorio per gli adulti. */
    public function documentoCompleto(): bool
    {
        return filled($this->tipo_documento)
            && filled($this->numero_documento)
            && filled($this->luogo_rilascio);
    }

    public function prenotazioni()
    {
        return $this->hasMany(Prenotazione::class, 'ospite_id');
    }
}
