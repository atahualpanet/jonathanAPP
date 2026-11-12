<?php

namespace App\Http\Controllers;

use App\Models\Ruolo;
use Illuminate\Http\Request;
use App\Mail\PasswordTemporanea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OperatoriController extends Controller
{
    /** Utenti e permessi: elenco operatori, con il profilo assegnabile da menu a tendina. */
    public function index()
    {
        $this->soloGestori();

        $ruoli  = Ruolo::tutti();
        $ordine = $ruoli->keys()->all();

        $modello = get_class(auth()->user());

        $operatori = $modello::orderBy('cognome')->orderBy('nome')->get()
            ->sortBy(function ($u) use ($ordine) {
                $i = array_search(Str::lower((string) $u->ruolo), $ordine, true);
                return $i === false ? 99 : $i;
            })
            ->values();

        return view('operatori.index', compact('operatori', 'ruoli'));
    }

    /** Form nuovo operatore. */
    public function nuovo()
    {
        $this->soloGestori();

        return view('operatori.nuovo', ['ruoli' => Ruolo::tutti()]);
    }

    public function crea(Request $request)
    {
        $this->soloGestori();

        $modello = get_class(auth()->user());

        $dati = $request->validate([
            'nome'     => ['required', 'string', 'max:255'],
            'cognome'  => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('utenti', 'email')],
            'ruolo'    => ['required', 'string', Rule::exists('ruoli', 'chiave')],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.unique'       => 'Esiste già un utente con questa email (anche se disattivato o eliminato).',
            'password.confirmed' => 'Le due password non coincidono.',
            'password.min'       => 'La password deve avere almeno 8 caratteri.',
        ]);

        $u = $modello::create([
            'nome'     => trim($dati['nome']),
            'cognome'  => trim($dati['cognome']),
            'email'    => Str::lower(trim($dati['email'])),
            'ruolo'    => $dati['ruolo'],
            'password' => $dati['password'], // l'hash lo applica il cast del modello
            'attivo'   => $request->boolean('attivo', true),
            'deve_cambiare_password' => $request->boolean('cambia_password'),
        ]);

        return redirect()->route('operatori.index')->with('ok', 'Operatore ' . $u->nomeCompleto() . ' creato.');
    }

    /** Form modifica operatore. */
    public function modifica(int $utente)
    {
        $this->soloGestori();

        $modello = get_class(auth()->user());

        return view('operatori.modifica', [
            'u'     => $modello::findOrFail($utente),
            'ruoli' => Ruolo::tutti(),
        ]);
    }

    public function aggiorna(Request $request, int $utente)
    {
        $this->soloGestori();

        $modello = get_class(auth()->user());
        $u = $modello::findOrFail($utente);

        $dati = $request->validate([
            'nome'     => ['required', 'string', 'max:255'],
            'cognome'  => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('utenti', 'email')->ignore($u->id)],
            'ruolo'    => ['required', 'string', Rule::exists('ruoli', 'chiave')],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ], [
            'email.unique'       => 'Esiste già un utente con questa email (anche se disattivato o eliminato).',
            'password.confirmed' => 'Le due password non coincidono.',
            'password.min'       => 'La password deve avere almeno 8 caratteri.',
        ]);

        $attivo = $request->boolean('attivo');
        $nuovo  = Ruolo::perChiave($dati['ruolo']);
        $era    = $u->isGestore() && $u->attivo;
        $sara   = $nuovo->gestore && $attivo;

        if ($u->is(auth()->user()) && (! $nuovo->gestore || ! $attivo)) {
            return back()->withInput()->withErrors(['ruolo' => 'Non puoi togliere a te stesso il profilo da gestore né disattivarti.']);
        }
        if ($era && ! $sara && ! $this->altroGestoreAttivo($u->id)) {
            return back()->withInput()->withErrors(['ruolo' => 'È l\'ultimo gestore attivo: prima nomina un altro gestore.']);
        }

        $u->nome    = trim($dati['nome']);
        $u->cognome = trim($dati['cognome']);
        $u->email   = Str::lower(trim($dati['email']));
        $u->ruolo   = $nuovo->chiave;
        $u->attivo  = $attivo;
        if (! empty($dati['password'])) {
            $u->password = $dati['password']; // hash applicato dal cast
        }
        $u->save();

        return redirect()->route('operatori.index')->with('ok', 'Operatore ' . $u->nomeCompleto() . ' aggiornato.');
    }

    /** Elimina (in archivio: il record resta nel db ma non può più accedere). */
    public function elimina(int $utente)
    {
        $this->soloGestori();

        $modello = get_class(auth()->user());
        $u = $modello::findOrFail($utente);

        if ($u->is(auth()->user())) {
            return back()->withErrors(['ruolo' => 'Non puoi eliminare te stesso.']);
        }
        if ($u->isGestore() && $u->attivo && ! $this->altroGestoreAttivo($u->id)) {
            return back()->withErrors(['ruolo' => 'È l\'ultimo gestore attivo: non si può eliminare.']);
        }

        $nome = $u->nomeCompleto();
        $u->delete();

        return redirect()->route('operatori.index')->with('ok', "Operatore {$nome} eliminato.");
    }

    private function altroGestoreAttivo(int $escludiId): bool
    {
        $chiavi = Ruolo::tutti()->where('gestore', true)->keys()->all();

        return DB::table('utenti')->whereNull('deleted_at')->where('attivo', 1)
            ->where('id', '!=', $escludiId)->whereIn('ruolo', $chiavi)->exists();
    }

    /** Reset password: ne genera una temporanea, la manda per email e obbliga a cambiarla al primo accesso. */
    public function resetPassword(int $utente)
    {
        $this->soloGestori();

        $modello = get_class(auth()->user());
        $u = $modello::findOrFail($utente);

        if ($u->is(auth()->user())) {
            return back()->withErrors(['ruolo' => 'Per la tua password usa "Profilo personale".']);
        }

        $alfabeto = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // niente caratteri ambigui (0/O, 1/l/I)
        $temporanea = '';
        for ($i = 0; $i < 12; $i++) {
            $temporanea .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        // stato precedente, per tornare indietro se la mail non parte
        $prima = DB::table('utenti')->where('id', $u->id)
            ->first(['password', 'deve_cambiare_password', 'remember_token']);

        $u->password = $temporanea; // hash applicato dal cast
        $u->deve_cambiare_password = true;
        $u->setRememberToken(Str::random(60));
        $u->save();

        try {
            // l'invio sta FUORI da ogni transazione: se l'SMTP è lento non tiene bloccate le tabelle
            Mail::to($u->email)->send(new PasswordTemporanea($u, $temporanea));
        } catch (\Throwable $e) {
            Log::error('Reset password: invio email fallito per utente ' . $u->id . ': ' . $e->getMessage());

            DB::table('utenti')->where('id', $u->id)->update([
                'password'               => $prima->password,
                'deve_cambiare_password' => $prima->deve_cambiare_password,
                'remember_token'         => $prima->remember_token,
            ]);

            return back()->withErrors(['ruolo' => 'Email non inviata, password NON modificata. Controlla le impostazioni MAIL nel file .env.']);
        }

        // chiude le sessioni già aperte di questo utente (se le sessioni sono nel db)
        if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $u->id)->delete();
        }

        return back()->with('ok', 'Password temporanea inviata a ' . $u->email . '. Al primo accesso dovrà sceglierne una nuova.');
    }

    /** Attiva / disattiva un operatore (un disattivato non può più accedere). */
    public function attiva(int $utente)
    {
        $this->soloGestori();

        $modello = get_class(auth()->user());
        $u = $modello::findOrFail($utente);

        if ($u->is(auth()->user())) {
            return back()->withErrors(['attivo' => 'Non puoi disattivare te stesso.']);
        }

        if ($u->attivo && $u->isGestore() && ! $this->altroGestoreAttivo($u->id)) {
            return back()->withErrors(['attivo' => 'È l\'ultimo gestore attivo: non si può disattivare.']);
        }

        $u->attivo = ! $u->attivo;
        $u->save();

        return back()->with('ok', $u->nomeCompleto() . ($u->attivo ? ' riattivato.' : ' disattivato.'));
    }

    /** Assegna un profilo a un utente. */
    public function assegnaRuolo(Request $request, int $utente)
    {
        $this->soloGestori();

        $dati = $request->validate([
            'ruolo' => ['required', 'string', Rule::exists('ruoli', 'chiave')],
        ]);

        $modello = get_class(auth()->user());
        $u = $modello::findOrFail($utente);
        $nuovo = Ruolo::perChiave($dati['ruolo']);

        // per non chiudere fuori se stessi
        if ($u->is(auth()->user()) && ! $nuovo->gestore) {
            return back()->withErrors(['ruolo' => 'Non puoi togliere a te stesso un profilo da gestore: chiedi a un altro gestore.']);
        }

        $u->ruolo = $nuovo->chiave;
        $u->save();

        return back()->with('ok', $u->nomeCompleto() . ' ora è ' . $nuovo->nome . '.');
    }

    /** Permessi per profilo: matrice funzioni x profili, con caselle da spuntare. */
    public function permessi()
    {
        $this->soloGestori();

        return view('operatori.permessi', [
            'ruoli'    => Ruolo::tutti(),
            'funzioni' => config('permessi.funzioni'),
            'nota'     => config('permessi.nota'),
        ]);
    }

    public function salvaPermessi(Request $request)
    {
        $this->soloGestori();

        $validi = array_keys(config('permessi.funzioni'));
        $matrice = $request->input('matrice', []);

        foreach (Ruolo::tutti() as $ruolo) {
            $scelte = array_values(array_intersect($validi, (array) ($matrice[$ruolo->id] ?? [])));
            $ruolo->update(['funzioni' => $scelte]);
        }
        Ruolo::dimentica();

        return redirect()->route('permessi.index')->with('ok', 'Permessi salvati.');
    }

    /** Elenco dei profili: rinomina, moduli, gestore, aggiungi, elimina. */
    public function profili()
    {
        $this->soloGestori();

        $utentiPerRuolo = DB::table('utenti')->whereNull('deleted_at')
            ->select('ruolo', DB::raw('count(*) as n'))->groupBy('ruolo')->pluck('n', 'ruolo');

        return view('operatori.profili', [
            'ruoli'          => Ruolo::tutti(),
            'moduli'         => config('permessi.moduli'),
            'utentiPerRuolo' => $utentiPerRuolo,
        ]);
    }

    public function salvaProfili(Request $request)
    {
        $this->soloGestori();

        $righe  = (array) $request->input('profili', []);
        $moduliValidi = array_keys(config('permessi.moduli'));
        $mioRuolo = Str::lower((string) auth()->user()->ruolo);

        DB::transaction(function () use ($righe, $moduliValidi, $mioRuolo, $request) {
            foreach (Ruolo::tutti() as $ruolo) {
                if (! isset($righe[$ruolo->id])) {
                    continue;
                }
                $riga = $righe[$ruolo->id];
                $nome = trim((string) ($riga['nome'] ?? ''));
                if ($nome === '') {
                    throw ValidationException::withMessages(['profili' => 'Il nome di un profilo non può essere vuoto.']);
                }
                $gestore = ! empty($riga['gestore']);
                if ($ruolo->chiave === $mioRuolo && ! $gestore) {
                    throw ValidationException::withMessages(['profili' => 'Non puoi togliere il flag "gestore" al tuo stesso profilo.']);
                }
                $ruolo->update([
                    'nome'    => Str::limit($nome, 100, ''),
                    'moduli'  => array_values(array_intersect($moduliValidi, (array) ($riga['moduli'] ?? []))),
                    'gestore' => $gestore,
                ]);
            }

            // nuovo profilo (campo in fondo alla pagina)
            $nuovo = trim((string) $request->input('nuovo_nome', ''));
            if ($nuovo !== '') {
                $chiave = Str::limit(Str::slug($nuovo, '_'), 50, '');
                if ($chiave === '') {
                    throw ValidationException::withMessages(['nuovo_nome' => 'Nome del nuovo profilo non valido.']);
                }
                if (Ruolo::where('chiave', $chiave)->exists()) {
                    throw ValidationException::withMessages(['nuovo_nome' => 'Esiste già un profilo con questo nome.']);
                }
                Ruolo::create([
                    'chiave'   => $chiave,
                    'nome'     => Str::limit($nuovo, 100, ''),
                    'moduli'   => [],
                    'funzioni' => [],
                    'gestore'  => false,
                    'ordine'   => (int) Ruolo::max('ordine') + 1,
                ]);
            }

            if (! Ruolo::where('gestore', true)->exists()) {
                throw ValidationException::withMessages(['profili' => 'Deve esserci almeno un profilo gestore.']);
            }
        });

        Ruolo::dimentica();

        return redirect()->route('profili.index')->with('ok', 'Profili salvati.');
    }

    public function eliminaProfilo(int $ruolo)
    {
        $this->soloGestori();

        $r = Ruolo::findOrFail($ruolo);

        // conta anche gli utenti eliminati: il valore resterebbe orfano se li ripristini
        $usati = DB::table('utenti')->where('ruolo', $r->chiave)->count();
        if ($usati > 0) {
            return back()->withErrors(['profili' => "Il profilo «{$r->nome}» è assegnato a {$usati} utenti: riassegnali prima di eliminarlo."]);
        }
        if ($r->gestore && Ruolo::where('gestore', true)->count() === 1) {
            return back()->withErrors(['profili' => 'Non puoi eliminare l\'ultimo profilo gestore.']);
        }

        $r->delete();
        Ruolo::dimentica();

        return redirect()->route('profili.index')->with('ok', "Profilo «{$r->nome}» eliminato.");
    }

    private function soloGestori(): void
    {
        abort_unless(auth()->user()?->isGestore(), 403);
    }
}
