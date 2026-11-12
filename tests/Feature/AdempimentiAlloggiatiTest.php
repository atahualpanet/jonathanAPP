<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\InvioAlloggiati;
use App\Models\Ospite;
use App\Models\Prenotazione;
use App\Models\TipologiaCamera;
use App\Models\Utente;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdempimentiAlloggiatiTest extends TestCase
{
    use RefreshDatabase;

    private TipologiaCamera $tip;
    private int $n = 100;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Utente::create([
            'nome' => 'Test', 'cognome' => 'Reception', 'email' => 't@example.com', 'password' => 'secret123',
            'ruolo' => 'amministratore', 'attivo' => true, 'deve_cambiare_password' => false,
        ]));

        $this->tip = TipologiaCamera::create(['nome' => 'Doppia', 'codice' => 'DBL', 'capacita_adulti' => 2, 'prezzo_base' => 100]);
    }

    /** Prenotazione in arrivo oggi, per una sola persona; $completa = dati sufficienti per Alloggiati. */
    private function prenotazione(bool $completa = true): Prenotazione
    {
        $num = (string) $this->n++;
        $camera = Camera::create(['tipologia_camera_id' => $this->tip->id, 'numero' => $num, 'piano' => 1, 'stato_pulizia' => 'pulita', 'attiva' => true]);
        $arrivo = Carbon::today()->toDateString();
        $partenza = Carbon::today()->addDays(2)->toDateString();

        $ospite = Ospite::create([
            'nome' => 'Mario', 'cognome' => 'Rossi' . $num,
            'data_nascita' => $completa ? '1980-05-10' : null,
            'nazionalita' => $completa ? 'IT' : null,
            'tipo_documento' => $completa ? 'carta_identita' : null,
            'numero_documento' => $completa ? 'AX' . $num : null,
        ]);
        $p = Prenotazione::create([
            'codice' => 'R-A-' . $num, 'ospite_id' => $ospite->id, 'data_arrivo' => $arrivo, 'data_partenza' => $partenza,
            'adulti' => 1, 'bambini' => 0, 'stato' => 'confermata', 'canale' => 'diretto', 'importo_totale' => 0, 'importo_caparra' => 0,
        ]);
        $p->camere()->attach($camera->id, ['data_arrivo' => $arrivo, 'data_partenza' => $partenza, 'tariffa' => 100]);

        return $p;
    }

    public function test_il_massivo_invia_solo_le_complete_in_un_unico_invio(): void
    {
        $a = $this->prenotazione();
        $b = $this->prenotazione();
        $incompleta = $this->prenotazione(false);

        $this->post(route('adempimenti.alloggiati.massivo'))->assertSessionHasNoErrors();

        $this->assertSame(1, InvioAlloggiati::count());
        $invio = InvioAlloggiati::first();
        $this->assertSame('massivo', $invio->tipo);
        $this->assertCount(2, $invio->schedine);
        $this->assertSame($invio->id, $a->fresh()->invio_alloggiati_id);
        $this->assertSame($invio->id, $b->fresh()->invio_alloggiati_id);
        $this->assertNull($incompleta->fresh()->alloggiati_inviato_il);
    }

    public function test_il_massivo_si_fa_una_volta_al_giorno(): void
    {
        $this->prenotazione();
        $this->post(route('adempimenti.alloggiati.massivo'));

        $this->prenotazione();
        $this->post(route('adempimenti.alloggiati.massivo'))->assertSessionHasErrors('adempimenti');

        $this->assertSame(1, InvioAlloggiati::count());
    }

    public function test_il_singolo_non_si_puo_fare_prima_del_massivo(): void
    {
        $p = $this->prenotazione();

        $this->post(route('prenotazioni.adempimenti.invia', $p))->assertSessionHasErrors('adempimenti');

        $this->assertSame(0, InvioAlloggiati::count());
        $this->assertNull($p->fresh()->alloggiati_inviato_il);
    }

    public function test_il_singolo_dopo_il_massivo_registra_un_invio_a_parte(): void
    {
        $this->prenotazione();
        $this->post(route('adempimenti.alloggiati.massivo'));

        $tardiva = $this->prenotazione();
        $this->post(route('prenotazioni.adempimenti.invia', $tardiva))->assertSessionHasNoErrors();

        $this->assertSame(2, InvioAlloggiati::count());
        $singolo = InvioAlloggiati::where('tipo', 'singolo')->first();
        $this->assertCount(1, $singolo->schedine);
        $this->assertSame($singolo->id, $tardiva->fresh()->invio_alloggiati_id);

        // già inviata: un secondo invio singolo viene rifiutato
        $this->post(route('prenotazioni.adempimenti.invia', $tardiva))->assertSessionHasErrors('adempimenti');
        $this->assertSame(2, InvioAlloggiati::count());
    }

    public function test_la_pagina_mostra_il_pulsante_singolo_solo_dopo_il_massivo(): void
    {
        $this->prenotazione();
        $this->get(route('adempimenti.mensile'))->assertOk()->assertSee('Invia tutte')->assertDontSee('Invia singolarmente');

        $this->post(route('adempimenti.alloggiati.massivo'));
        $this->prenotazione();

        $this->get(route('adempimenti.mensile'))->assertOk()->assertSee('Invia singolarmente')->assertSee('Vedi PDF');
    }

    public function test_il_pdf_dell_invio_si_apre_ed_e_un_pdf(): void
    {
        $this->prenotazione();
        $this->post(route('adempimenti.alloggiati.massivo'));
        $invio = InvioAlloggiati::first();

        $r = $this->get(route('adempimenti.alloggiati.pdf', $invio));

        $r->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $r->getContent());
    }
}
