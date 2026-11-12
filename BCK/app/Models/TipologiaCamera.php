<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipologiaCamera extends Model
{
    protected $table = 'tipologie_camera'; // Nota il singolare

    protected $fillable = [
        'nome', 'codice', 'descrizione', 'capacita_adulti', 
        'capacita_bambini', 'prezzo_base', 'attiva'
    ];

    public function camere()
    {
        return $this->hasMany(Camera::class, 'tipologia_camera_id');
    }
}