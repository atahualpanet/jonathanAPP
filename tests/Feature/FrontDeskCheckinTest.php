<?php

namespace Tests\Feature;

use App\Http\Controllers\FrontDeskController;
use App\Models\Camera;
use App\Models\Ospite;
use App\Models\Prenotazione;
use App\Models\TipologiaCamera;
use App\Models\Utente;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FrontDeskCheckinTest extends TestCase
{
    use RefreshDatabase;

    private Camera $c1;
    private Camera $c2;
    private Prenotazione $p;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->actingAs(Utente::create([
            'nome' => 'Test', 'cognome' => 'Reception', 'email' => 't@example.com', 'password' => 'secret123',
            'ruolo' => 'amministratore', 'attivo' => true, 'deve_cambiare_password' => false,
        ]));

        $tip = TipologiaCamera::create(['nome' => 'Doppia', 'codice' => 'DBL', 'capacita_adulti' => 2, 'prezzo_base' => 100]);
        $this->c1 = Camera::create(['tipologia_camera_id' => $tip->id, 'numero' => '101', 'piano' => 1, 'stato_pulizia' => 'pulita', 'attiva' => true]);
        $this->c2 = Camera::create(['tipologia_camera_id' => $tip->id, 'numero' => '102', 'piano' => 1, 'stato_pulizia' => 'pulita', 'attiva' => true]);

        $this->p = $this->nuovaPrenotazione($this->c1, 2);
    }

    private function nuovaPrenotazione(Camera $camera, int $adulti, int $dalGiorno = 0, int $notti = 3): Prenotazione
    {
        $arrivo = Carbon::today()->addDays($dalGiorno)->toDateString();
        $partenza = Carbon::today()->addDays($dalGiorno + $notti)->toDateString();
        $ospite = Ospite::create(['nome' => 'Mario', 'cognome' => 'Rossi' . $camera->numero . $dalGiorno]);
        $p = Prenotazione::create([
            'codice' => 'R-T-' . $camera->numero . $dalGiorno, 'ospite_id' => $ospite->id,
            'data_arrivo' => $arrivo, 'data_partenza' => $partenza, 'adulti' => $adulti, 'bambini' => 0,
            'stato' => 'confermata', 'canale' => 'diretto', 'importo_totale' => 0, 'importo_caparra' => 50,
        ]);
        $p->camere()->attach($camera->id, ['data_arrivo' => $arrivo, 'data_partenza' => $partenza, 'tariffa' => 100]);
        return $p;
    }

    private function formCheckin(array $extra = []): array
    {
        $tit = $this->p->ospite;
        return array_replace_recursive([
            'azione' => 'completa',
            'ospiti' => [$tit->id => [
                'nome' => 'Mario', 'cognome' => $tit->cognome, 'data_nascita' => '1985-04-12', 'nazionalita' => 'it',
                'tipo_documento' => 'carta_identita', 'numero_documento' => 'AB123',
                'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'), 'privacy' => 1,
            ]],
            'nuovi' => [0 => [
                'bambino' => 0, 'nome' => 'Anna', 'cognome' => 'Rossi', 'data_nascita' => '1987-01-02', 'nazionalita' => 'IT',
                'tipo_documento' => 'patente', 'numero_documento' => 'CD456', 'privacy' => 1,
            ]],
        ], $extra);
    }

    public function test_le_pagine_si_aprono(): void
    {
        $this->get(route('prenotazioni.checkin', $this->p))->assertOk()->assertSee('Salva e completa check-in');
        $this->get(route('prenotazioni.modifica', $this->p))->assertOk()->assertSee('Tariffa a notte');
        $this->get(route('prenotazioni.scheda', $this->p))->assertOk();
    }

    public function test_checkin_completo_salva_tutto_e_apre_il_conto(): void
    {
        $r = $this->post(route('prenotazioni.checkin.completa', $this->p), $this->formCheckin());
        $r->assertRedirect(route('prenotazioni.scheda', $this->p));
        $r->assertSessionHasNoErrors();

        $p = $this->p->fresh();
        $this->assertSame('in_casa', $p->stato);
        $this->assertNotNull($p->entrata_il);
        $this->assertSame(2, 1 + $p->accompagnatori()->count());
        $this->assertSame('IT', $p->ospite->nazionalita);
        $this->assertNotNull($p->ospite->documento_file);
        $this->assertTrue((bool) $p->ospite->privacy_accettata);

        $conto = DB::table('conti')->where('prenotazione_id', $p->id)->first();
        $this->assertNotNull($conto, 'il conto deve aprirsi da solo');
        $voci = DB::table('voci_conto')->where('conto_id', $conto->id)->get()->keyBy('origine');
        $this->assertEquals(300, $voci['camera:' . $this->c1->id]->importo);
        $this->assertEquals(15, $voci['tassa']->importo);     // 2 adulti x 2,50 x 3 notti
        $this->assertEquals(50, $voci['caparra']->importo);

        $this->get(route('prenotazioni.checkin', $p))->assertOk()->assertSee('Salva modifiche');
    }

    public function test_senza_nazionalita_il_checkin_non_si_completa_ma_i_dati_restano_salvati(): void
    {
        $f = $this->formCheckin();
        $f['ospiti'][$this->p->ospite->id]['nazionalita'] = '';
        $r = $this->post(route('prenotazioni.checkin.completa', $this->p), $f);

        $r->assertSessionHasErrors('checkin');
        $this->assertStringContainsString('nazionalità', implode(' ', session('errors')->get('checkin')));
        $p = $this->p->fresh();
        $this->assertSame('confermata', $p->stato);
        $this->assertSame('AB123', $p->ospite->numero_documento, 'i dati inseriti devono essere stati salvati lo stesso');
        $this->assertSame(1, $p->accompagnatori()->count(), 'anche la seconda persona è stata creata');
        $this->assertNull(DB::table('conti')->where('prenotazione_id', $p->id)->first());
    }

    public function test_azione_salva_non_completa(): void
    {
        $this->post(route('prenotazioni.checkin.completa', $this->p), $this->formCheckin(['azione' => 'salva']))
            ->assertRedirect(route('prenotazioni.checkin', $this->p));
        $this->assertSame('confermata', $this->p->fresh()->stato);
    }

    public function test_camera_non_pronta_chiede_conferma(): void
    {
        $this->c1->update(['stato_pulizia' => 'sporca']);
        $this->get(route('prenotazioni.checkin', $this->p))->assertSee('Procedo comunque');

        $this->post(route('prenotazioni.checkin.completa', $this->p), $this->formCheckin())->assertSessionHasErrors('checkin');
        $this->assertSame('confermata', $this->p->fresh()->stato);

        // Il primo tentativo ha già salvato Anna: ora compare tra le persone registrate e non si reinvia come nuova
        $f = $this->formCheckin(['procedi_comunque' => 1]);
        unset($f['nuovi']);
        $this->post(route('prenotazioni.checkin.completa', $this->p), $f)->assertSessionHasNoErrors();
        $this->assertSame('in_casa', $this->p->fresh()->stato);
    }

    public function test_errori_di_validazione_si_vedono_accanto_al_campo(): void
    {
        $id = $this->p->ospite->id;
        $f = $this->formCheckin();
        $f['ospiti'][$id]['nazionalita'] = 'ITA';
        $r = $this->from(route('prenotazioni.checkin', $this->p))->post(route('prenotazioni.checkin.completa', $this->p), $f);
        $r->assertSessionHasErrors("ospiti.$id.nazionalita");
        $this->assertSame('confermata', $this->p->fresh()->stato);
        $this->followingRedirects()->get(route('prenotazioni.checkin', $this->p));
        $this->get(route('prenotazioni.checkin', $this->p))->assertOk();
    }

    public function test_bambino_da_registrare_non_chiede_privacy(): void
    {
        $p = $this->nuovaPrenotazione($this->c2, 1, 0);
        $p->update(['bambini' => 1]);
        $html = $this->get(route('prenotazioni.checkin', $p))->assertOk()->getContent();
        $this->assertStringContainsString('Bambino', $html);
        $this->assertSame(0, substr_count($html, 'name="nuovi[0][privacy]"'), 'il bambino non ha la casella privacy');
        $this->assertSame(1, substr_count($html, 'data-privacy'), 'solo il titolare ha da accettare la privacy');
    }

    public function test_camera_in_lavori_blocca(): void
    {
        $this->c1->update(['stato_pulizia' => 'manutenzione']);
        $this->post(route('prenotazioni.checkin.completa', $this->p), $this->formCheckin(['procedi_comunque' => 1]))->assertSessionHasErrors('checkin');
        $this->assertSame('confermata', $this->p->fresh()->stato);
    }

    public function test_checkin_anticipato_chiede_conferma(): void
    {
        $futura = $this->nuovaPrenotazione($this->c2, 1, 5);
        $this->assertNotEmpty(app(FrontDeskController::class)->contoDi($futura) ? [] : ['ok']);
        $this->get(route('prenotazioni.checkin', $futura))->assertSee('Check-in anticipato');
    }

    public function test_persone_di_altre_prenotazioni_non_si_toccano(): void
    {
        $altro = Ospite::create(['nome' => 'Estraneo', 'cognome' => 'Altro']);
        $f = $this->formCheckin();
        $f['ospiti'][$altro->id] = ['nome' => 'HACK', 'cognome' => 'HACK'];
        $this->post(route('prenotazioni.checkin.completa', $this->p), $f);
        $this->assertSame('Estraneo', $altro->fresh()->nome);
    }

    public function test_apri_conto_e_idempotente(): void
    {
        $front = app(FrontDeskController::class);
        $a = $front->apriContoPer($this->p);
        $b = $front->apriContoPer($this->p);
        $this->assertSame($a, $b);
        $this->assertSame(1, DB::table('conti')->where('prenotazione_id', $this->p->id)->count());
    }

    private function checkinFatto(): void
    {
        $this->post(route('prenotazioni.checkin.completa', $this->p), $this->formCheckin())->assertSessionHasNoErrors();
    }

    private function formModifica(array $extra = []): array
    {
        $p = $this->p->fresh('camere');
        $c = $p->camere->first();
        return array_replace_recursive([
            'adulti' => $p->adulti, 'bambini' => 0, 'canale' => 'diretto', 'note' => '', 'caparra' => $p->importo_caparra,
            'camere' => [$c->id => [
                'camera_id' => $c->id, 'data_arrivo' => substr($c->pivot->data_arrivo, 0, 10),
                'data_partenza' => substr($c->pivot->data_partenza, 0, 10), 'tariffa' => $c->pivot->tariffa,
            ]],
        ], $extra);
    }

    public function test_modifica_riallinea_il_conto_gia_aperto(): void
    {
        $this->checkinFatto();
        $cid = $this->c1->id;
        $nuovaPartenza = Carbon::today()->addDays(4)->toDateString();

        $this->post(route('prenotazioni.modifica.salva', $this->p), $this->formModifica([
            'adulti' => 2, 'caparra' => 0,
            'camere' => [$cid => ['data_partenza' => $nuovaPartenza, 'tariffa' => 120]],
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $conto = DB::table('conti')->where('prenotazione_id', $this->p->id)->first();
        $voci = DB::table('voci_conto')->where('conto_id', $conto->id)->get()->keyBy('origine');
        $this->assertEquals(480, $voci['camera:' . $cid]->importo);            // 4 notti x 120
        $this->assertSame('Camera 101 · 4 notti', $voci['camera:' . $cid]->descrizione);
        $this->assertEquals(20, $voci['tassa']->importo);                       // 2 x 2,50 x 4
        $this->assertFalse($voci->has('caparra'), 'caparra a zero: la voce sparisce');
        $this->assertEquals(500, DB::table('conti')->where('id', $conto->id)->value('saldo'));
        $this->assertEquals(500, $this->p->fresh()->importo_totale);
    }

    public function test_cambio_camera_sposta_la_stessa_voce(): void
    {
        $this->checkinFatto();
        $conto = DB::table('conti')->where('prenotazione_id', $this->p->id)->first();
        $idVoce = DB::table('voci_conto')->where('conto_id', $conto->id)->where('origine', 'camera:' . $this->c1->id)->value('id');

        $this->post(route('prenotazioni.modifica.salva', $this->p), $this->formModifica([
            'camere' => [$this->c1->id => ['camera_id' => $this->c2->id]],
        ]))->assertSessionHasNoErrors();

        $voce = DB::table('voci_conto')->where('id', $idVoce)->first();
        $this->assertSame('camera:' . $this->c2->id, $voce->origine);
        $this->assertSame('Camera 102 · 3 notti', $voce->descrizione);
        $this->assertSame(1, DB::table('voci_conto')->where('conto_id', $conto->id)->where('tipo', 'addebito_camera')->count());
    }

    public function test_conto_vecchio_senza_origine_viene_riconosciuto(): void
    {
        $this->checkinFatto();
        DB::table('voci_conto')->update(['origine' => null]);
        $this->post(route('prenotazioni.modifica.salva', $this->p), $this->formModifica([
            'camere' => [$this->c1->id => ['tariffa' => 150]],
        ]))->assertSessionHasNoErrors();

        $conto = DB::table('conti')->where('prenotazione_id', $this->p->id)->first();
        $this->assertSame(1, DB::table('voci_conto')->where('conto_id', $conto->id)->where('tipo', 'addebito_camera')->count(), 'nessun duplicato');
        $this->assertEquals(450, DB::table('voci_conto')->where('conto_id', $conto->id)->where('tipo', 'addebito_camera')->value('importo'));
        $this->assertSame(0, DB::table('voci_conto')->where('conto_id', $conto->id)->whereNull('origine')->count(), 'le voci riconosciute vengono marcate');
    }

    public function test_folio_con_fattura_emessa_non_si_tocca(): void
    {
        $this->checkinFatto();
        $conto = DB::table('conti')->where('prenotazione_id', $this->p->id)->first();
        DB::table('fatture')->insert(['conto_id' => $conto->id, 'folio' => 'A', 'stato' => 'emessa', 'anno' => 2026, 'progressivo' => 1,
            'numero' => '2026/1', 'data_emissione' => today(), 'importo_totale' => 365, 'created_at' => now(), 'updated_at' => now()]);

        $r = $this->post(route('prenotazioni.modifica.salva', $this->p), $this->formModifica([
            'camere' => [$this->c1->id => ['tariffa' => 200]],
        ]));
        $r->assertSessionHasNoErrors();
        $this->assertStringContainsString('fattura emessa', session('ok'));
        $this->assertEquals(300, DB::table('voci_conto')->where('conto_id', $conto->id)->where('origine', 'camera:' . $this->c1->id)->value('importo'));
    }

    public function test_modifica_rifiuta_sovrapposizioni_e_annulla_tutto(): void
    {
        $altra = $this->nuovaPrenotazione($this->c2, 1, 0);
        $this->post(route('prenotazioni.modifica.salva', $this->p), $this->formModifica([
            'camere' => [$this->c1->id => ['camera_id' => $this->c2->id, 'tariffa' => 999]],
        ]))->assertSessionHasErrors('modifica');
        $this->assertEquals(100, DB::table('prenotazione_camera')->where('prenotazione_id', $this->p->id)->value('tariffa'), 'rollback completo');
    }

    public function test_non_si_scende_sotto_le_persone_registrate(): void
    {
        $this->checkinFatto();
        $this->post(route('prenotazioni.modifica.salva', $this->p), $this->formModifica(['adulti' => 1]))
            ->assertSessionHasErrors('modifica');
    }

    public function test_modifica_vietata_dopo_la_partenza(): void
    {
        $this->p->update(['stato' => 'partita']);
        $this->post(route('prenotazioni.modifica.salva', $this->p), $this->formModifica())->assertSessionHasErrors('modifica');
        $this->get(route('prenotazioni.modifica', $this->p))->assertOk()->assertSee('non si può più modificare');
    }

    public function test_annulla_e_no_show(): void
    {
        $this->post(route('prenotazioni.annulla', $this->p), [])->assertSessionHasErrors('motivo');
        $this->post(route('prenotazioni.annulla', $this->p), ['motivo' => 'Cancellata dal cliente'])->assertSessionHasNoErrors();
        $p = $this->p->fresh();
        $this->assertSame('annullata', $p->stato);
        $this->assertSame('Cancellata dal cliente', $p->uscita_motivo);
        $this->get(route('prenotazioni.scheda', $p))->assertOk()->assertSee('Motivo dell');

        $futura = $this->nuovaPrenotazione($this->c2, 1, 5);
        $this->post(route('prenotazioni.noshow', $futura), [])->assertSessionHasErrors('modifica');
        $oggi = $this->nuovaPrenotazione($this->c2, 1, 10);
        $oggi->update(['data_arrivo' => today()]);
        $this->post(route('prenotazioni.noshow', $oggi), [])->assertSessionHasNoErrors();
        $this->assertSame('no_show', $oggi->fresh()->stato);
    }

    public function test_non_si_annulla_chi_e_in_casa(): void
    {
        $this->checkinFatto();
        $this->post(route('prenotazioni.annulla', $this->p), ['motivo' => 'x'])->assertSessionHasErrors('modifica');
        $this->assertSame('in_casa', $this->p->fresh()->stato);
    }

    public function test_sposta_dal_tableau_riallinea_il_conto(): void
    {
        $this->checkinFatto();
        $cid = $this->c1->id;
        $this->postJson(route('prenotazioni.sposta', $this->p), [
            'camera_origine' => $cid, 'camera_id' => $cid,
            'data_arrivo' => Carbon::today()->toDateString(), 'data_partenza' => Carbon::today()->addDays(2)->toDateString(),
        ])->assertOk()->assertJson(['ok' => true]);

        $voce = DB::table('voci_conto')->where('origine', 'camera:' . $cid)->first();
        $this->assertEquals(200, $voce->importo);
        $this->assertSame('Camera 101 · 2 notti', $voce->descrizione);
    }

    public function test_comando_apre_i_conti_mancanti(): void
    {
        $this->p->update(['stato' => 'in_casa']);
        $this->artisan('conti:apri-mancanti')->assertSuccessful();
        $this->assertSame(1, DB::table('conti')->where('prenotazione_id', $this->p->id)->count());
        $this->artisan('conti:apri-mancanti')->assertSuccessful();
        $this->assertSame(1, DB::table('conti')->where('prenotazione_id', $this->p->id)->count());
    }

    public function test_pagine_esistenti_continuano_a_funzionare_dopo_il_checkin(): void
    {
        $this->checkinFatto();
        $this->get(route('frontdesk.prenotazioni'))->assertOk();
        $this->get(route('prenotazioni.folio', $this->p))->assertOk()->assertSee('Camera 101');
        $this->get(route('prenotazioni.checkout', $this->p))->assertOk();
        $this->get(route('prenotazioni.ospiti.modifica', [$this->p, $this->p->ospite]))->assertOk();
        $this->get(route('prenotazioni.scheda', $this->p))->assertOk()->assertSee('Conto C-');
        $this->get(route('adempimenti.mensile'))->assertOk();
        // il pulsante "Apri conto" del folio non crea un secondo conto
        $this->post(route('prenotazioni.folio.apri', $this->p));
        $this->assertSame(1, DB::table('conti')->where('prenotazione_id', $this->p->id)->count());
    }

    // ---------------- caparra all'annullamento / no-show ----------------

    public function test_annullare_con_caparra_richiede_di_scegliere_cosa_farne(): void
    {
        $this->post(route('prenotazioni.annulla', $this->p), ['motivo' => 'cancellata dal cliente'])
            ->assertSessionHasErrors('caparra_esito');

        $this->assertSame('confermata', $this->p->fresh()->stato);
    }

    public function test_annullamento_con_rimborso_totale(): void
    {
        $this->post(route('prenotazioni.annulla', $this->p), ['motivo' => 'cancellata', 'caparra_esito' => 'rimborsa'])
            ->assertSessionHasNoErrors()->assertRedirect(route('prenotazioni.scheda', $this->p));

        $p = $this->p->fresh();
        $this->assertSame('annullata', $p->stato);
        $this->assertEquals(0, $p->caparra_trattenuta);
        $this->assertEquals(50, $p->caparra_rimborsata);
        $this->assertNotNull($p->caparra_gestita_il);
        $this->get(route('prenotazioni.scheda', $p))->assertOk()->assertSee('da rimborsare');
    }

    public function test_annullamento_con_trattenuta_parziale(): void
    {
        $this->post(route('prenotazioni.annulla', $this->p), [
            'motivo' => 'cancellata', 'caparra_esito' => 'parziale', 'caparra_trattenuta' => 20,
        ])->assertSessionHasNoErrors();

        $p = $this->p->fresh();
        $this->assertEquals(20, $p->caparra_trattenuta);
        $this->assertEquals(30, $p->caparra_rimborsata);
    }

    public function test_non_si_puo_trattenere_piu_della_caparra(): void
    {
        $this->post(route('prenotazioni.annulla', $this->p), [
            'motivo' => 'cancellata', 'caparra_esito' => 'parziale', 'caparra_trattenuta' => 80,
        ])->assertSessionHasErrors('modifica');

        $p = $this->p->fresh();
        $this->assertSame('confermata', $p->stato);
        $this->assertNull($p->caparra_gestita_il);
    }

    public function test_no_show_con_caparra_trattenuta(): void
    {
        $this->post(route('prenotazioni.noshow', $this->p), ['caparra_esito' => 'trattieni'])
            ->assertSessionHasNoErrors();

        $p = $this->p->fresh();
        $this->assertSame('no_show', $p->stato);
        $this->assertEquals(50, $p->caparra_trattenuta);
        $this->assertEquals(0, $p->caparra_rimborsata);
    }

    public function test_senza_caparra_si_annulla_come_prima(): void
    {
        $this->p->update(['importo_caparra' => 0]);

        $this->post(route('prenotazioni.annulla', $this->p), ['motivo' => 'cancellata'])->assertSessionHasNoErrors();

        $p = $this->p->fresh();
        $this->assertSame('annullata', $p->stato);
        $this->assertNull($p->caparra_gestita_il);
    }

    public function test_con_conto_aperto_la_caparra_trattenuta_pareggia_il_conto(): void
    {
        $contoId = DB::table('conti')->insertGetId([
            'prenotazione_id' => $this->p->id, 'codice' => 'C-TEST-0001', 'stato' => 'aperto', 'saldo' => -50,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('voci_conto')->insert([
            'conto_id' => $contoId, 'folio' => 'A', 'tipo' => 'caparra', 'descrizione' => 'Caparra versata alla prenotazione',
            'importo' => 50, 'origine' => 'caparra', 'registrata_il' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->post(route('prenotazioni.annulla', $this->p), [
            'motivo' => 'cancellata', 'caparra_esito' => 'parziale', 'caparra_trattenuta' => 20,
        ])->assertSessionHasNoErrors();

        $voci = DB::table('voci_conto')->where('conto_id', $contoId)->get()->keyBy('origine');
        $this->assertEquals(20, $voci['caparra_trattenuta']->importo);
        $this->assertEquals(30, $voci['caparra_rimborso']->importo);
        $conto = DB::table('conti')->where('id', $contoId)->first();
        $this->assertSame('chiuso', $conto->stato);
        $this->assertEquals(0, $conto->saldo);
        $this->get(route('prenotazioni.scheda', $this->p))->assertOk();
    }

    // ---------------- ente di rilascio e scadenza del documento ----------------

    public function test_checkin_salva_ente_di_rilascio_e_scadenza(): void
    {
        $id = $this->p->ospite->id;
        $scadenza = Carbon::today()->addYears(3)->toDateString();

        $this->post(route('prenotazioni.checkin.completa', $this->p), $this->formCheckin([
            'ospiti' => [$id => ['ente_rilascio' => 'Comune di Milano', 'scadenza_documento' => $scadenza]],
            'nuovi'  => [0 => ['ente_rilascio' => 'Motorizzazione di Roma', 'scadenza_documento' => $scadenza]],
        ]))->assertSessionHasNoErrors();

        $p = $this->p->fresh(['ospite', 'accompagnatori']);
        $this->assertSame('Comune di Milano', $p->ospite->ente_rilascio);
        $this->assertSame($scadenza, $p->ospite->scadenza_documento->toDateString());
        $this->assertSame('Motorizzazione di Roma', $p->accompagnatori->first()->ente_rilascio);
    }

    public function test_documento_scaduto_e_un_avviso_che_si_puo_confermare(): void
    {
        $id = $this->p->ospite->id;
        $form = $this->formCheckin(['ospiti' => [$id => ['scadenza_documento' => '2020-01-01']]]);

        $this->post(route('prenotazioni.checkin.completa', $this->p), $form)->assertSessionHasErrors('checkin');
        $this->assertSame('confermata', $this->p->fresh()->stato);

        $form['procedi_comunque'] = 1;
        $form['ospiti'][$id]['file'] = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $this->post(route('prenotazioni.checkin.completa', $this->p), $form)->assertSessionHasNoErrors();
        $this->assertSame('in_casa', $this->p->fresh()->stato);
    }

    public function test_salva_documento_dalla_scheda_ospite_con_ente_e_scadenza(): void
    {
        $this->post(route('prenotazioni.ospiti.documento', [$this->p, $this->p->ospite]), [
            'tipo_documento' => 'passaporto', 'numero_documento' => 'YA999',
            'ente_rilascio' => 'Questura di Milano', 'scadenza_documento' => '2031-06-30',
        ])->assertSessionHasNoErrors();

        $o = $this->p->ospite->fresh();
        $this->assertSame('Questura di Milano', $o->ente_rilascio);
        $this->assertSame('2031-06-30', $o->scadenza_documento->toDateString());
        $this->get(route('prenotazioni.checkin', $this->p))->assertOk()->assertSee('Ente di rilascio');
    }
}
