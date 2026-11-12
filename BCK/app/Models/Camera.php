<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Camera extends Model
{
    protected $table = 'camere';

    protected $fillable = [
        'tipologia_camera_id', 'numero',
        'piano', 'stato_pulizia', 'attiva', 'note'
    ];

    /**
     * Stati di pulizia e relative etichette.
     * Flusso: sporca (Da pulire) -> in_pulizia (In corso) -> pulita. "manutenzione" = fuori servizio.
     * "ispezionata" è il vecchio stato: vale come pulita.
     */
    public const STATI = [
        'sporca'       => 'Da pulire',
        'in_pulizia'   => 'In corso',
        'pulita'       => 'Pulita',
        'ispezionata'  => 'Pulita',
        'manutenzione' => 'Fuori servizio',
    ];

    // Relazione: Una camera appartiene a una tipologia
    public function tipologia()
    {
        return $this->belongsTo(TipologiaCamera::class, 'tipologia_camera_id');
    }

    // Relazione: Una camera ha più registri di pulizia
    public function registriPulizia()
    {
        return $this->hasMany(RegistroPulizia::class, 'camera_id');
    }

    public function etichettaStato(): string
    {
        return self::STATI[$this->stato_pulizia] ?? ucfirst((string) $this->stato_pulizia);
    }

    /** Cambia lo stato di pulizia e lo scrive nello storico (registri_pulizia). */
    public function cambiaStato(string $nuovo, ?int $utenteId = null, ?string $note = null): void
    {
        if (! array_key_exists($nuovo, self::STATI)) {
            throw new \InvalidArgumentException("Stato di pulizia non valido: {$nuovo}");
        }

        $precedente = $this->stato_pulizia;
        if ($precedente === $nuovo) {
            return;
        }

        DB::transaction(function () use ($nuovo, $precedente, $utenteId, $note) {
            $this->update(['stato_pulizia' => $nuovo]);

            RegistroPulizia::create([
                'camera_id'        => $this->id,
                'utente_id'        => $utenteId,
                'stato_precedente' => $precedente,
                'stato_nuovo'      => $nuovo,
                'note'             => $note,
            ]);
        });
    }
}
