<?php

namespace App\Console\Commands;

use App\Http\Controllers\FrontDeskController;
use App\Models\Prenotazione;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Da lanciare UNA volta dopo l'aggiornamento: apre il conto alle prenotazioni già in casa che non ce l'hanno
 * (da ora il conto si apre da solo al check-in).
 *   php artisan conti:apri-mancanti
 */
class ApriContiMancanti extends Command
{
    protected $signature = 'conti:apri-mancanti';
    protected $description = 'Apre il conto alle prenotazioni in casa che ne sono prive';

    public function handle(): int
    {
        $front = app(FrontDeskController::class);
        $aperti = 0;

        Prenotazione::where('stato', 'in_casa')->get()->each(function (Prenotazione $p) use ($front, &$aperti) {
            $haConto = DB::table('conti')->where('prenotazione_id', $p->id)->exists();
            if (! $haConto) {
                $front->apriContoPer($p);
                $aperti++;
                $this->line('Conto aperto: ' . $p->codice);
            }
        });

        $this->info($aperti . ' conti aperti.');
        return self::SUCCESS;
    }
}
