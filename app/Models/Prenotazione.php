<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prenotazione extends Model
{
    use SoftDeletes;

    protected $table = 'prenotazioni';

    protected $fillable = [
        'codice', 'ospite_id', 'data_arrivo', 'data_partenza', 
        'adulti', 'bambini', 'stato', 'canale', 
        'importo_totale', 'importo_caparra', 'caparra_trattenuta', 'caparra_rimborsata', 'caparra_gestita_il', 'note', 
        'entrata_il', 'uscita_il', 'uscita_motivo', 'alloggiati_inviato_il', 'invio_alloggiati_id', 'istat_inviato_il'
    ];

    protected $casts = [
        'data_arrivo'            => 'date',
        'data_partenza'          => 'date',
        'caparra_gestita_il'     => 'datetime',
        'entrata_il'             => 'datetime',
        'uscita_il'               => 'datetime',
        'alloggiati_inviato_il'  => 'datetime',
        'istat_inviato_il'       => 'datetime',
    ];

    // Relazione: Una prenotazione appartiene a un ospite (il titolare)
    public function ospite()
    {
        return $this->belongsTo(Ospite::class, 'ospite_id');
    }

    // Relazione: le altre persone della prenotazione (accompagnatori e bambini), nell'ordine di inserimento
    public function accompagnatori()
    {
        return $this->belongsToMany(Ospite::class, 'prenotazione_ospite', 'prenotazione_id', 'ospite_id')
                    ->withPivot(['bambino', 'ordine'])
                    ->withTimestamps()
                    ->orderBy('prenotazione_ospite.ordine')
                    ->orderBy('prenotazione_ospite.id');
    }

    // Relazione: Una prenotazione può essere associata a una o più camere (tabella pivot)
    public function camere()
    {
        return $this->belongsToMany(Camera::class, 'prenotazione_camera', 'prenotazione_id', 'camera_id')
                    ->withPivot(['data_arrivo', 'data_partenza', 'tariffa'])
                    ->withTimestamps();
    }

    /** True finché la prenotazione si può ancora modificare (non è partita, annullata o no-show). */
    public function modificabile(): bool
    {
        return ! in_array($this->stato, ['partita', 'annullata', 'no_show'], true);
    }

    /** Persone previste dalla prenotazione (adulti + bambini). */
    public function personePreviste(): int
    {
        return max(1, (int) $this->adulti + (int) $this->bambini);
    }

    /**
     * Tutte le persone registrate, titolare per primo, ognuna con il suo stato.
     * Regole: gli adulti hanno bisogno di dati (con nazionalità), documento e privacy; i bambini solo dei dati
     * anagrafici (il documento è facoltativo e la privacy la firma l'adulto).
     * Il criterio è lo stesso di Alloggiati Web (alloggiatiCompleto): se il check-in è completo, Alloggiati non resta bloccato.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function persone()
    {
        $this->loadMissing('ospite', 'accompagnatori');

        $righe = collect([[$this->ospite, false, 'Titolare']]);
        $n = 2;
        foreach ($this->accompagnatori as $a) {
            $bambino = (bool) $a->pivot->bambino;
            $righe->push([$a, $bambino, $bambino ? 'Bambino' : 'Ospite ' . $n]);
            $n++;
        }

        return $righe->map(function ($r) {
            [$o, $bambino, $ruolo] = $r;

            $datiOk      = $o->anagraficaCompleta();
            $documentoOk = $o->documentoCompleto();
            $privacyOk   = (bool) $o->privacy_accettata;

            return (object) [
                'ospite'          => $o,
                'bambino'         => $bambino,
                'titolare'        => $o->id === $this->ospite_id,
                'ruolo'           => $ruolo,
                'documentoOk'     => $documentoOk,
                'datiOk'          => $datiOk,
                'privacyOk'       => $privacyOk,
                'richiedeDoc'     => ! $bambino,
                'richiedePrivacy' => ! $bambino,
                'completo'        => $datiOk && ($bambino || ($documentoOk && $privacyOk)),
            ];
        })->values();
    }

    /** Quante persone previste non sono ancora state registrate con nome e cognome. */
    public function postiMancanti(): int
    {
        $this->loadMissing('accompagnatori');
        return max(0, $this->personePreviste() - 1 - $this->accompagnatori->count());
    }

    /** Adulti già registrati (titolare compreso). */
    public function adultiRegistrati(): int
    {
        return $this->persone()->where('bambino', false)->count();
    }

    /** Bambini già registrati. */
    public function bambiniRegistrati(): int
    {
        return $this->persone()->where('bambino', true)->count();
    }

    /**
     * Cosa manca per poter completare il check-in, una riga per persona incompleta:
     * ["Rossi Mario: data di nascita, documento", ...]. Vuoto = tutto a posto.
     * È l'unico punto in cui si decide cosa serve: lo usano la pagina di check-in e il controller.
     *
     * @return array<int, string>
     */
    public function checkinMancanti(): array
    {
        return $this->persone()
            ->reject(fn ($p) => $p->completo)
            ->map(function ($p) {
                $o = $p->ospite;
                $manca = [];

                if (! filled($o->nome) || ! filled($o->cognome)) {
                    $manca[] = 'nome e cognome';
                }
                if (! in_array($o->sesso, ['M', 'F'], true)) {
                    $manca[] = 'sesso';
                }
                if (! $o->data_nascita) {
                    $manca[] = 'data di nascita';
                }
                if (! filled($o->luogo_nascita)) {
                    $manca[] = 'luogo di nascita';
                }
                if (! filled($o->nazionalita)) {
                    $manca[] = 'cittadinanza';
                }
                if ($p->richiedeDoc && ! $p->documentoOk) {
                    $manca[] = 'documento (tipo, numero e luogo di rilascio)';
                }
                if ($p->richiedePrivacy && ! $p->privacyOk) {
                    $manca[] = 'privacy';
                }

                return trim($o->cognome . ' ' . $o->nome) . ': ' . implode(', ', $manca);
            })
            ->values()
            ->all();
    }

    /**
     * Dati sufficienti per generare le schedine Alloggiati Web.
     * Tutti: anagrafica completa. Adulti: anche documento completo (raccolto sempre in app).
     */
    public function alloggiatiCompleto(): bool
    {
        if ($this->postiMancanti() > 0) {
            return false;
        }

        return $this->persone()->every(function ($p) {
            if (! $p->ospite->anagraficaCompleta()) {
                return false;
            }
            if (! $p->bambino && ! $p->ospite->documentoCompleto()) {
                return false;
            }
            return true;
        });
    }
}
