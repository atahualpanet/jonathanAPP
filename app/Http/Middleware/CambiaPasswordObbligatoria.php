<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Finché l'utente ha una password temporanea può vedere solo la pagina di cambio password (e uscire). */
class CambiaPasswordObbligatoria
{
    public function handle(Request $request, Closure $next): Response
    {
        $utente = $request->user();

        if ($utente && ($utente->deve_cambiare_password ?? false)
            && ! $request->routeIs('password.cambia', 'password.cambia.salva', 'logout')) {
            return redirect()->route('password.cambia');
        }

        return $next($request);
    }
}
