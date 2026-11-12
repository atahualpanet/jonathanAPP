<?php

namespace App\Http\Controllers;

use App\Mail\PasswordTemporanea;
use App\Models\Utente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    // ------------------------------------------------------------------
    // Cambio password obbligatorio (dopo reset / password temporanea)
    // ------------------------------------------------------------------

    public function form(Request $request)
    {
        // chi non è obbligato a cambiarla usa "Profilo personale"
        if (! $request->user()->deve_cambiare_password) {
            return redirect()->route('profilo.index');
        }

        return view('auth.cambia-password');
    }

    public function salva(Request $request)
    {
        $dati = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ], [
            'current_password.current_password' => 'La password temporanea non è corretta.',
            'password.different'                => 'La nuova password deve essere diversa da quella temporanea.',
            'password.confirmed'                => 'Le due password non coincidono.',
            'password.min'                      => 'La password deve avere almeno 8 caratteri.',
        ]);

        $utente = $request->user();
        $utente->password = $dati['password']; // hash applicato dal cast
        $utente->deve_cambiare_password = false;
        $utente->save();

        return redirect()->route('dashboard.home')->with('ok', 'Password aggiornata.');
    }

    // ------------------------------------------------------------------
    // Password dimenticata (self-service dalla pagina di login)
    // ------------------------------------------------------------------

    public function showForgotForm()
    {
        return view('auth.password-dimenticata');
    }

    public function sendTemporaryPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Inserisci l\'indirizzo email.',
            'email.email'    => 'Inserisci un indirizzo email valido.',
        ]);

        $email = $request->input('email');

        // Messaggio generico di sicurezza (non riveliamo se l'email esiste o meno)
        $messaggioSuccesso = 'Se l\'indirizzo email è presente nel sistema, riceverai a breve una email con la nuova password temporanea. Al primo accesso ti verrà chiesto di sceglierne una personale.';

        $utente = Utente::where('email', $email)->where('attivo', true)->first();

        if (! $utente) {
            // Non riveliamo se l'email esiste: mostriamo sempre successo
            return back()->with('status', $messaggioSuccesso);
        }

        // Genera password temporanea (stesso alfabeto usato nel reset operatori)
        $alfabeto = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $temporanea = '';
        for ($i = 0; $i < 12; $i++) {
            $temporanea .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        // Salva stato precedente per eventuale rollback
        $prima = DB::table('utenti')->where('id', $utente->id)
            ->first(['password', 'deve_cambiare_password', 'remember_token']);

        $utente->password = $temporanea; // hash applicato dal cast
        $utente->deve_cambiare_password = true;
        $utente->setRememberToken(Str::random(60));
        $utente->save();

        // Tenta l'invio reale della mail
        try {
            Mail::to($utente->email)->send(new PasswordTemporanea($utente, $temporanea));
        } catch (\Throwable $e) {
            // Per ora modalità "virtuale": logghiamo la password temporanea e proseguiamo
            Log::warning('Password dimenticata: invio email fallito per utente ' . $utente->id . ' (' . $utente->email . '). Password temporanea: ' . $temporanea . ' — Errore: ' . $e->getMessage());

            // Non facciamo rollback: la password è stata comunque aggiornata
            // (l'amministratore può recuperarla dai log in fase di sviluppo)
        }

        // Chiude eventuali sessioni già aperte di questo utente
        if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $utente->id)->delete();
        }

        return back()->with('status', $messaggioSuccesso);
    }
}
