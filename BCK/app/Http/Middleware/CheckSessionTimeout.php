<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSessionTimeout
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $lastActivity = session('last_activity_time');
            $timeout = config('session.lifetime') * 60; // minuti -> secondi

            if ($lastActivity && (time() - $lastActivity > $timeout)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'Sessione scaduta per inattività. Effettua di nuovo l\'accesso.']);
            }

            session(['last_activity_time' => time()]);
        }

        return $next($request);
    }
}