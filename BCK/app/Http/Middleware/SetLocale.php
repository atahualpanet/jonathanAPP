<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Legge la lingua scelta salvata in sessione (default: 'it')
        $locale = session('locale', 'it');
        
        // Imposta la lingua nell'applicazione in modo sicuro
        App::setLocale($locale);

        return $next($request);
    }
}