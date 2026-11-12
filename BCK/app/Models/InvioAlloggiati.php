<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvioAlloggiati extends Model
{
    public const MASSIVO = 'massivo';
    public const SINGOLO = 'singolo';

    protected $table = 'invii_alloggiati';

    protected $fillable = ['tipo', 'data_riferimento', 'inviato_il', 'utente_id', 'schedine'];

    protected $casts = [
        'data_riferimento' => 'date',
        'inviato_il'       => 'datetime',
        'schedine'         => 'array',
    ];

    public function utente()
    {
        return $this->belongsTo(Utente::class, 'utente_id');
    }

    public function prenotazioni()
    {
        return $this->hasMany(Prenotazione::class, 'invio_alloggiati_id');
    }

    public function massivo(): bool
    {
        return $this->tipo === self::MASSIVO;
    }
}
