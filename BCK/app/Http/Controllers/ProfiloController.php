<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfiloController extends Controller
{
    public function index()
    {
        return view('profilo.index');
    }

    /** Nome, cognome ed email dell'utente collegato. */
    public function salva(Request $request)
    {
        $utente = $request->user();

        $dati = $request->validate([
            'nome'    => 'required|string|max:255',
            'cognome' => 'required|string|max:255',
            'email'   => ['required', 'email', 'max:255', Rule::unique('utenti', 'email')->ignore($utente->id)],
        ]);

        $utente->update($dati);

        return back()->with('success', 'Dati personali aggiornati.');
    }

    /** Cambio password: chiede quella attuale, la nuova (min 8 caratteri) e la conferma. */
    public function password(Request $request)
    {
        $dati = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'La password attuale non è corretta.',
        ]);

        $request->user()->update(['password' => $dati['password']]); // il cast "hashed" del modello la cifra

        return back()->with('success', 'Password aggiornata.');
    }
}
