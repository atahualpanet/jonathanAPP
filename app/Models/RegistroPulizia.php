<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroPulizia extends Model
{
    protected $table = 'registri_pulizia';

    protected $fillable = [
        'camera_id', 'utente_id', 'stato_precedente', 'stato_nuovo', 'note'
    ];

    public function camera()
    {
        return $this->belongsTo(Camera::class, 'camera_id');
    }

    public function utente()
    {
        return $this->belongsTo(Utente::class, 'utente_id');
    }
}
