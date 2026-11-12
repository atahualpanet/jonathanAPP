<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BistroController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfiloController;
use App\Http\Controllers\FrontDeskController;
use App\Http\Controllers\MinibarController;
use App\Http\Controllers\HousekeepingController;
use App\Http\Controllers\MagazzinoController;
use App\Http\Controllers\HousekeepingPulizieController;
use App\Http\Controllers\OperatoriController;
use App\Http\Controllers\PasswordController;

// ---------- Accesso ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');

    // Password dimenticata
    Route::get('/password/dimenticata', [PasswordController::class, 'showForgotForm'])->name('password.dimenticata');
    Route::post('/password/dimenticata', [PasswordController::class, 'sendTemporaryPassword'])->name('password.dimenticata.post');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// ---------- Area riservata (serve il login) ----------
Route::middleware('auth')->group(function () {

// Home della suite
Route::get('/', [DashboardController::class, 'home'])->name('dashboard.home');

// Profilo personale
Route::get('/profilo', [ProfiloController::class, 'index'])->name('profilo.index');
Route::post('/profilo', [ProfiloController::class, 'salva'])->name('profilo.salva');
Route::post('/profilo/password', [ProfiloController::class, 'password'])->name('profilo.password');

// Cambio password obbligatorio (dopo un reset: la password temporanea va sostituita)
Route::get('/password/cambia', [PasswordController::class, 'form'])->name('password.cambia');
Route::post('/password/cambia', [PasswordController::class, 'salva'])->name('password.cambia.salva');

// Tableau Front Desk
Route::get('/frontdesk', [FrontDeskController::class, 'prenotazioni'])->name('frontdesk.prenotazioni');

// Nuova prenotazione — PRIMA della rotta dinamica /prenotazioni/{prenotazione} qui sotto,
// altrimenti Laravel legge "nuova" come se fosse l'id di una prenotazione e va in errore.
Route::get('/prenotazioni/nuova', [FrontDeskController::class, 'nuovaForm'])->name('prenotazioni.nuova');
Route::post('/prenotazioni/nuova', [FrontDeskController::class, 'nuovaCrea'])->name('prenotazioni.nuova.crea');
Route::get('/prenotazioni/nuova/disponibilita', [FrontDeskController::class, 'nuovaDisponibilita'])->name('prenotazioni.nuova.disponibilita');

// Drag & drop del tableau (sposta / allunga / accorcia)
Route::post('/prenotazioni/{prenotazione}/sposta', [FrontDeskController::class, 'sposta'])->name('prenotazioni.sposta');

// Scheda prenotazione (si apre cliccando una barra del tableau)
Route::get('/prenotazioni/{prenotazione}', [FrontDeskController::class, 'scheda'])->name('prenotazioni.scheda');

// Modifica prenotazione (soggiorno, persone, caparra, canale, note) e chiusura prima dell'arrivo (annulla / no-show)
Route::get('/prenotazioni/{prenotazione}/modifica', [FrontDeskController::class, 'prenotazioneModifica'])->name('prenotazioni.modifica');
Route::post('/prenotazioni/{prenotazione}/modifica', [FrontDeskController::class, 'prenotazioneAggiorna'])->name('prenotazioni.modifica.salva');
Route::post('/prenotazioni/{prenotazione}/annulla', [FrontDeskController::class, 'prenotazioneAnnulla'])->name('prenotazioni.annulla');
Route::post('/prenotazioni/{prenotazione}/no-show', [FrontDeskController::class, 'prenotazioneNoShow'])->name('prenotazioni.noshow');

// Ospiti della prenotazione: titolare + accompagnatori + bambini (dati anagrafici, documento, privacy)
Route::post('/prenotazioni/{prenotazione}/ospiti', [FrontDeskController::class, 'ospiteAggiungi'])->name('prenotazioni.ospiti.aggiungi');
Route::get('/prenotazioni/{prenotazione}/ospiti/{ospite}', [FrontDeskController::class, 'ospiteModifica'])->name('prenotazioni.ospiti.modifica');
Route::post('/prenotazioni/{prenotazione}/ospiti/{ospite}', [FrontDeskController::class, 'ospiteAggiorna'])->name('prenotazioni.ospiti.salva');
Route::post('/prenotazioni/{prenotazione}/ospiti/{ospite}/documento', [FrontDeskController::class, 'ospiteSalvaDocumento'])->name('prenotazioni.ospiti.documento');
Route::post('/prenotazioni/{prenotazione}/ospiti/{ospite}/privacy', [FrontDeskController::class, 'checkinAccettaPrivacy'])->name('prenotazioni.ospiti.privacy');
Route::post('/prenotazioni/{prenotazione}/ospiti/{ospite}/rimuovi', [FrontDeskController::class, 'ospiteRimuovi'])->name('prenotazioni.ospiti.rimuovi');

// Check-in: una sola pagina, un solo invio (azione=salva oppure azione=completa)
Route::get('/prenotazioni/{prenotazione}/check-in', [FrontDeskController::class, 'checkin'])->name('prenotazioni.checkin');
Route::post('/prenotazioni/{prenotazione}/check-in/completa', [FrontDeskController::class, 'checkinCompleta'])->name('prenotazioni.checkin.completa');

// Folio / conto
Route::get('/prenotazioni/{prenotazione}/check-out', [FrontDeskController::class, 'checkout'])->name('prenotazioni.checkout');
Route::post('/prenotazioni/{prenotazione}/check-out', [FrontDeskController::class, 'checkoutConferma'])->name('prenotazioni.checkout.conferma');
Route::get('/prenotazioni/{prenotazione}/folio', [FrontDeskController::class, 'folio'])->name('prenotazioni.folio');
Route::post('/prenotazioni/{prenotazione}/folio/apri', [FrontDeskController::class, 'folioApri'])->name('prenotazioni.folio.apri');
Route::post('/prenotazioni/{prenotazione}/folio/voce', [FrontDeskController::class, 'folioAggiungiVoce'])->name('prenotazioni.folio.voce');
Route::post('/prenotazioni/{prenotazione}/folio/pagamento', [FrontDeskController::class, 'folioRegistraPagamento'])->name('prenotazioni.folio.pagamento');
Route::post('/prenotazioni/{prenotazione}/folio/chiudi', [FrontDeskController::class, 'folioChiudi'])->name('prenotazioni.folio.chiudi');
Route::post('/prenotazioni/{prenotazione}/folio/voci/{voce}/sposta', [FrontDeskController::class, 'folioSpostaVoce'])->whereNumber('voce')->name('prenotazioni.folio.voce.sposta');
Route::post('/prenotazioni/{prenotazione}/folio/voci/{voce}/storna', [FrontDeskController::class, 'folioStornaVoce'])->whereNumber('voce')->name('prenotazioni.folio.voce.storna');
Route::post('/prenotazioni/{prenotazione}/folio/unisci', [FrontDeskController::class, 'folioUnisci'])->name('prenotazioni.folio.unisci');
Route::post('/prenotazioni/{prenotazione}/folio/{folio}/fattura', [FrontDeskController::class, 'folioFatturaEmetti'])->where('folio', 'A|B')->name('prenotazioni.folio.fattura.emetti');
Route::get('/prenotazioni/{prenotazione}/folio/{folio}/fattura', [FrontDeskController::class, 'folioFatturaVedi'])->where('folio', 'A|B')->name('prenotazioni.folio.fattura');

// Adempimenti: pagina unica per l'hotel (il link dalla scheda prenotazione ci arriva con la camera evidenziata)
Route::get('/prenotazioni/{prenotazione}/adempimenti', [FrontDeskController::class, 'adempimentiVaiA'])->name('prenotazioni.adempimenti');
Route::post('/prenotazioni/{prenotazione}/adempimenti/invia', [FrontDeskController::class, 'adempimentiInvia'])->name('prenotazioni.adempimenti.invia');
Route::get('/adempimenti', [FrontDeskController::class, 'adempimenti'])->name('adempimenti.mensile');
Route::post('/adempimenti/alloggiati/invia-tutte', [FrontDeskController::class, 'adempimentiInviaMassivo'])->name('adempimenti.alloggiati.massivo');
Route::get('/adempimenti/alloggiati/invii/{invio}/pdf', [FrontDeskController::class, 'adempimentiInvioPdf'])->whereNumber('invio')->name('adempimenti.alloggiati.pdf');
Route::get('/adempimenti/alloggiati/invii/{invio}/txt', [FrontDeskController::class, 'adempimentiInvioTxt'])->whereNumber('invio')->name('adempimenti.alloggiati.txt');
Route::get('/adempimenti/storico', [FrontDeskController::class, 'adempimentiStorico'])->name('adempimenti.storico');
Route::get('/adempimenti/storico/search', [FrontDeskController::class, 'adempimentiStoricoSearch'])->name('adempimenti.storico.search');
Route::post('/adempimenti/istat/invia', [FrontDeskController::class, 'adempimentiInviaIstatOggi'])->name('adempimenti.istat.invia');

// Vista mensile dell'occupazione
Route::get('/occupazione', [FrontDeskController::class, 'occupazioneMensile'])->name('occupazione.mensile');

// Camere
Route::get('/camere', [FrontDeskController::class, 'index'])->name('camere.index');

// Housekeeping (versione desktop)
Route::get('/housekeeping', [HousekeepingController::class, 'index'])->name('housekeeping.index');
Route::post('/housekeeping/assegna', [HousekeepingController::class, 'assegna'])->name('housekeeping.assegna');
Route::post('/housekeeping/assegna-piano', [HousekeepingController::class, 'assegnaPiano'])->name('housekeeping.assegna.piano');
Route::post('/housekeeping/copia', [HousekeepingController::class, 'copiaDaIeri'])->name('housekeeping.copia');

// Housekeeping mobile (il personale di piano): "Il mio piano"
Route::get('/hk', [HousekeepingController::class, 'piano'])->name('hk.piano');
Route::get('/hk/camera/{camera}', [HousekeepingPulizieController::class, 'mostra'])->whereNumber('camera')->name('hk.camera');
Route::post('/hk/camera/{camera}/inizia', [HousekeepingPulizieController::class, 'inizia'])->whereNumber('camera')->name('hk.inizia');
Route::post('/hk/camera/{camera}/voce', [HousekeepingPulizieController::class, 'voce'])->whereNumber('camera')->name('hk.voce');
Route::post('/hk/camera/{camera}/nota', [HousekeepingPulizieController::class, 'nota'])->whereNumber('camera')->name('hk.nota');
Route::post('/hk/camera/{camera}/completa', [HousekeepingPulizieController::class, 'completa'])->whereNumber('camera')->name('hk.completa');
Route::get('/hk/camera/{camera}/completata', [HousekeepingPulizieController::class, 'completata'])->whereNumber('camera')->name('hk.completata');

// Operatori e permessi (solo i profili con il flag "gestore" nella tabella `ruoli`)
Route::get('/operatori', [OperatoriController::class, 'index'])->name('operatori.index');
Route::get('/operatori/nuovo', [OperatoriController::class, 'nuovo'])->name('operatori.nuovo');
Route::post('/operatori/nuovo', [OperatoriController::class, 'crea'])->name('operatori.crea');
Route::get('/operatori/{utente}/modifica', [OperatoriController::class, 'modifica'])->whereNumber('utente')->name('operatori.modifica');
Route::post('/operatori/{utente}/modifica', [OperatoriController::class, 'aggiorna'])->whereNumber('utente')->name('operatori.aggiorna');
Route::post('/operatori/{utente}/elimina', [OperatoriController::class, 'elimina'])->whereNumber('utente')->name('operatori.elimina');
Route::post('/operatori/{utente}/reset-password', [OperatoriController::class, 'resetPassword'])->whereNumber('utente')->name('operatori.reset');
Route::post('/operatori/{utente}/attiva', [OperatoriController::class, 'attiva'])->whereNumber('utente')->name('operatori.attiva');
Route::post('/operatori/{utente}/ruolo', [OperatoriController::class, 'assegnaRuolo'])->whereNumber('utente')->name('operatori.ruolo');
Route::get('/permessi', [OperatoriController::class, 'permessi'])->name('permessi.index');
Route::post('/permessi', [OperatoriController::class, 'salvaPermessi'])->name('permessi.salva');
Route::get('/permessi/profili', [OperatoriController::class, 'profili'])->name('profili.index');
Route::post('/permessi/profili', [OperatoriController::class, 'salvaProfili'])->name('profili.salva');
Route::post('/permessi/profili/{ruolo}/elimina', [OperatoriController::class, 'eliminaProfilo'])->whereNumber('ruolo')->name('profili.elimina');

// Configurazioni software
Route::get('/configurazioni', [ConfigController::class, 'index'])->name('configurazioni');
Route::post('/configurazioni/salva', [ConfigController::class, 'update'])->name('configurazioni.salva');
Route::post('/configurazioni/logo', [ConfigController::class, 'uploadLogo'])->name('configurazioni.logo');
Route::delete('/configurazioni/logo', [ConfigController::class, 'rimuoviLogo'])->name('configurazioni.logo.rimuovi');

// Minibar: listino, dotazione per tipologia e controllo della governante (addebito automatico sul folio B)
Route::get('/minibar', [MinibarController::class, 'listino'])->name('minibar.listino');
Route::post('/minibar/listino-base', [MinibarController::class, 'caricaListinoBase'])->name('minibar.base');
Route::post('/minibar/articoli', [MinibarController::class, 'articoloCrea'])->name('minibar.articoli.crea');
Route::post('/minibar/articoli/{articolo}', [MinibarController::class, 'articoloAggiorna'])->whereNumber('articolo')->name('minibar.articoli.aggiorna');
Route::post('/minibar/dotazioni/{tipologia}', [MinibarController::class, 'dotazioneSalva'])->whereNumber('tipologia')->name('minibar.dotazione.salva');
Route::get('/camere/{camera}/minibar', [MinibarController::class, 'controllo'])->whereNumber('camera')->name('camere.minibar');
Route::post('/camere/{camera}/minibar', [MinibarController::class, 'controlloSalva'])->whereNumber('camera')->name('camere.minibar.salva');

// Bistro: mappa tavoli, comande, cucina, conto con addebito in camera (folio B), listino e tavoli
Route::get('/bistro', [BistroController::class, 'mappa'])->name('bistro.mappa');
Route::get('/bistro/cucina', [BistroController::class, 'cucina'])->name('bistro.cucina');
Route::post('/bistro/comande/{comanda}/pronto', [BistroController::class, 'pronto'])->whereNumber('comanda')->name('bistro.pronto');
Route::get('/bistro/camera/verifica', [BistroController::class, 'verificaCamera'])->name('bistro.camera.verifica');
Route::get('/bistro/scorte', [BistroController::class, 'scorte'])->name('bistro.scorte');
Route::post('/bistro/scorte/{reparto}/{articolo}', [BistroController::class, 'conteggio'])->whereNumber(['reparto', 'articolo'])->name('bistro.conteggio');
Route::get('/bistro/incassi', [BistroController::class, 'incassi'])->name('bistro.incassi');
Route::get('/bistro/addebiti-iva', [BistroController::class, 'addebitiIva'])->name('bistro.iva');
Route::post('/bistro/addebiti-iva/{voce}', [BistroController::class, 'addebitoIvaSalva'])->whereNumber('voce')->name('bistro.iva.salva');
Route::get('/bistro/cassa', [BistroController::class, 'cassa'])->name('bistro.cassa');
Route::post('/bistro/cassa/righe', [BistroController::class, 'cassaAggiungi'])->name('bistro.cassa.aggiungi');
Route::post('/bistro/cassa/righe/{riga}/meno', [BistroController::class, 'cassaMeno'])->whereNumber('riga')->name('bistro.cassa.meno');
Route::post('/bistro/cassa/paga', [BistroController::class, 'cassaPaga'])->name('bistro.cassa.paga');
Route::post('/bistro/cassa/annulla', [BistroController::class, 'cassaAnnulla'])->name('bistro.cassa.annulla');
Route::get('/bistro/tavoli/{tavolo}', [BistroController::class, 'comanda'])->whereNumber('tavolo')->name('bistro.comanda');
Route::post('/bistro/tavoli/{tavolo}/apri', [BistroController::class, 'apri'])->whereNumber('tavolo')->name('bistro.apri');
Route::post('/bistro/tavoli/{tavolo}/righe', [BistroController::class, 'aggiungi'])->whereNumber('tavolo')->name('bistro.aggiungi');
Route::post('/bistro/righe/{riga}/meno', [BistroController::class, 'meno'])->whereNumber('riga')->name('bistro.meno');
Route::post('/bistro/tavoli/{tavolo}/invia', [BistroController::class, 'invia'])->whereNumber('tavolo')->name('bistro.invia');
Route::post('/bistro/tavoli/{tavolo}/chiedi-conto', [BistroController::class, 'chiediConto'])->whereNumber('tavolo')->name('bistro.conto.chiedi');
Route::get('/bistro/tavoli/{tavolo}/conto', [BistroController::class, 'conto'])->whereNumber('tavolo')->name('bistro.conto');
Route::post('/bistro/tavoli/{tavolo}/paga', [BistroController::class, 'paga'])->whereNumber('tavolo')->name('bistro.paga');
Route::get('/bistro/listino', [BistroController::class, 'listino'])->name('bistro.listino');
Route::post('/bistro/listino-base', [BistroController::class, 'caricaListinoBase'])->name('bistro.base');
Route::post('/bistro/articoli', [BistroController::class, 'articoloCrea'])->name('bistro.articoli.crea');
Route::post('/bistro/articoli/{articolo}', [BistroController::class, 'articoloAggiorna'])->whereNumber('articolo')->name('bistro.articoli.aggiorna');
Route::post('/bistro/scorte/{reparto}/{articolo}/richiedi', [BistroController::class, 'richiedi'])->whereNumber(['reparto', 'articolo'])->name('bistro.richiedi');
Route::post('/bistro/richieste/{richiesta}/annulla', [BistroController::class, 'richiestaAnnulla'])->whereNumber('richiesta')->name('bistro.richiesta.annulla');
Route::post('/bistro/trasferimenti/{trasferimento}/ricevi', [BistroController::class, 'ricevi'])->whereNumber('trasferimento')->name('bistro.ricevi');
Route::post('/bistro/tavoli', [BistroController::class, 'tavoloCrea'])->name('bistro.tavoli.crea');
Route::post('/bistro/tavoli/{tavolo}/disattiva', [BistroController::class, 'tavoloDisattiva'])->whereNumber('tavolo')->name('bistro.tavoli.disattiva');

// Magazzino & Fornitori: scorte, carichi, soglie e ordini ai fornitori (solo amministratore e direttore)
Route::get('/magazzino', [MagazzinoController::class, 'index'])->name('magazzino.index');
Route::get('/magazzino/articoli/nuovo', [MagazzinoController::class, 'articolo'])->name('magazzino.articolo');
Route::post('/magazzino/articoli', [MagazzinoController::class, 'articoloSalva'])->name('magazzino.articolo.crea');
Route::get('/magazzino/articoli/{articolo}', [MagazzinoController::class, 'articolo'])->whereNumber('articolo')->name('magazzino.articolo.modifica');
Route::post('/magazzino/articoli/{articolo}', [MagazzinoController::class, 'articoloSalva'])->whereNumber('articolo')->name('magazzino.articolo.salva');
Route::post('/magazzino/articoli/{articolo}/movimento', [MagazzinoController::class, 'movimentoSalva'])->whereNumber('articolo')->name('magazzino.movimento');
Route::get('/magazzino/movimenti', [MagazzinoController::class, 'movimenti'])->name('magazzino.movimenti');
Route::get('/magazzino/fornitori', [MagazzinoController::class, 'fornitori'])->name('magazzino.fornitori');
Route::post('/magazzino/fornitori', [MagazzinoController::class, 'fornitoreCrea'])->name('magazzino.fornitori.crea');
Route::post('/magazzino/fornitori/{fornitore}', [MagazzinoController::class, 'fornitoreAggiorna'])->whereNumber('fornitore')->name('magazzino.fornitori.aggiorna');
Route::post('/magazzino/fornitori/{fornitore}/elimina', [MagazzinoController::class, 'fornitoreElimina'])->whereNumber('fornitore')->name('magazzino.fornitori.elimina');
Route::get('/magazzino/reparti', [MagazzinoController::class, 'reparti'])->name('magazzino.reparti');
Route::post('/magazzino/reparti', [MagazzinoController::class, 'repartoCrea'])->name('magazzino.reparti.crea');
Route::post('/magazzino/reparti/{reparto}/articoli/{articolo}/soglia', [MagazzinoController::class, 'giacenzaSalva'])->whereNumber(['reparto', 'articolo'])->name('magazzino.giacenza');
Route::post('/magazzino/reparti/{reparto}/articoli/{articolo}/trasferisci', [MagazzinoController::class, 'trasferisci'])->whereNumber(['reparto', 'articolo'])->name('magazzino.trasferisci');
Route::post('/magazzino/richieste/{richiesta}/evadi', [MagazzinoController::class, 'richiestaEvadi'])->whereNumber('richiesta')->name('magazzino.richiesta.evadi');
Route::post('/magazzino/richieste/{richiesta}/annulla', [MagazzinoController::class, 'richiestaAnnulla'])->whereNumber('richiesta')->name('magazzino.richiesta.annulla');
Route::post('/magazzino/trasferimenti/{trasferimento}/annulla', [MagazzinoController::class, 'trasferimentoAnnulla'])->whereNumber('trasferimento')->name('magazzino.trasferimento.annulla');
Route::get('/magazzino/ordini', [MagazzinoController::class, 'ordini'])->name('magazzino.ordini');
Route::post('/magazzino/ordini/genera', [MagazzinoController::class, 'generaBozze'])->name('magazzino.ordini.genera');
Route::post('/magazzino/ordini', [MagazzinoController::class, 'ordineNuovo'])->name('magazzino.ordini.nuovo');
Route::get('/magazzino/ordini/{ordine}', [MagazzinoController::class, 'ordine'])->whereNumber('ordine')->name('magazzino.ordine');
Route::post('/magazzino/ordini/{ordine}/righe', [MagazzinoController::class, 'rigaAggiungi'])->whereNumber('ordine')->name('magazzino.ordine.riga.aggiungi');
Route::post('/magazzino/ordini/{ordine}/righe/{riga}', [MagazzinoController::class, 'rigaAggiorna'])->whereNumber(['ordine', 'riga'])->name('magazzino.ordine.riga.aggiorna');
Route::post('/magazzino/ordini/{ordine}/righe/{riga}/elimina', [MagazzinoController::class, 'rigaElimina'])->whereNumber(['ordine', 'riga'])->name('magazzino.ordine.riga.elimina');
Route::post('/magazzino/ordini/{ordine}/approva', [MagazzinoController::class, 'approva'])->whereNumber('ordine')->name('magazzino.ordine.approva');
Route::post('/magazzino/ordini/{ordine}/invia', [MagazzinoController::class, 'invia'])->whereNumber('ordine')->name('magazzino.ordine.invia');
Route::post('/magazzino/ordini/{ordine}/consegna', [MagazzinoController::class, 'consegna'])->whereNumber('ordine')->name('magazzino.ordine.consegna');
Route::post('/magazzino/ordini/{ordine}/annulla', [MagazzinoController::class, 'annulla'])->whereNumber('ordine')->name('magazzino.ordine.annulla');

//Direct Dashboard
Route::get('/direct', function () {
    return view('direct.index');
})->name('direct.index');
});

//Configurazioni hotel
Route::get('/configurazioni', [ConfigController::class, 'index'])->name('configurazioni');
Route::post('/configurazioni/salva', [ConfigController::class, 'update'])->name('configurazioni.salva');

//Camere e prezzi
Route::post('/configurazioni/camere', [ConfigController::class, 'salvaCamera'])->name('configurazioni.camere.salva');
Route::get('/camere/{camera}/dettaglio', [ConfigController::class, 'dettaglioCamera'])->name('camere.dettaglio');
Route::post('/camere/{camera}/dettaglio', [ConfigController::class, 'salvaDettagliCamera'])->name('camere.dettaglio.salva');

//Cerca clienti in memoria
Route::get('/api/ospiti/cerca', [FrontDeskController::class, 'cercaOspiti'])->name('ospiti.cerca');

//Configurazioni minibar 
Route::get('/impostazioni', function () {
    return view('impostazioni.index');
})->name('impostazioni.index');

// ===== IMPOSTAZIONI =====
Route::get('/impostazioni', function () {
    return view('impostazioni.index');
})->name('impostazioni.index');

// Configurazioni software (solo ID 3)
Route::get('/impostazioni/configurazioni-software', [App\Http\Controllers\FrontDeskController::class, 'configurazioniSoftware'])->name('configurazioni.software');
Route::post('/impostazioni/configurazioni-software', [App\Http\Controllers\FrontDeskController::class, 'salvaConfigurazioniSoftware'])->name('configurazioni.software.salva');

// Licenze e moduli (solo ID 3)
Route::get('/impostazioni/licenze', [App\Http\Controllers\FrontDeskController::class, 'licenzeModuli'])->name('impostazioni.licenze');