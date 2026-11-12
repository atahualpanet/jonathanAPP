<?php

namespace App\Http\Controllers;

use App\Models\Prenotazione;
use App\Models\Camera;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function home()
    {
        $oggi = Carbon::today();

        $arriviOggi = Prenotazione::whereDate('data_arrivo', $oggi)->count();
        $partenzeOggi = Prenotazione::whereDate('data_partenza', $oggi)->count();
        $inCasaCount = Prenotazione::whereDate('data_arrivo', '<=', $oggi)
                            ->whereDate('data_partenza', '>', $oggi)
                            ->count();

        // Dati Housekeeping
        $camereDaPulire = Camera::where('stato_pulizia', 'sporca')->count();
        $camereInPulizia = Camera::where('stato_pulizia', 'in_pulizia')->count();
        $camereInManutenzione = Camera::where('stato_pulizia', 'manutenzione')->count();

        return view('dashboard.home', compact(
            'arriviOggi', 'partenzeOggi', 'inCasaCount', 
            'camereDaPulire', 'camereInPulizia', 'camereInManutenzione'
        ));
    }
}