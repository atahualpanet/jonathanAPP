<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Utente;
use App\Models\TipologiaCamera;
use App\Models\Camera;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Creiamo un utente di test (Reception)
        Utente::create([
            'nome' => 'Mario',
            'cognome' => 'Rossi',
            'email' => 'reception@jonathan.test',
            'password' => Hash::make('password123'),
            'ruolo' => 'reception',
            'attivo' => true,
        ]);

        // 2. Creiamo le Tipologie di Camera
        $singola = TipologiaCamera::create([
            'nome' => 'Camera Singola Standard',
            'codice' => 'SGL',
            'descrizione' => 'Camera singola con letto francese e vista giardino.',
            'capacita_adulti' => 1,
            'capacita_bambini' => 0,
            'prezzo_base' => 75.00,
            'attiva' => true,
        ]);

        $matrimoniale = TipologiaCamera::create([
            'nome' => 'Camera Matrimoniale Deluxe',
            'codice' => 'DLX',
            'descrizione' => 'Spaziosa camera matrimoniale con balcone e vista mare.',
            'capacita_adulti' => 2,
            'capacita_bambini' => 1,
            'prezzo_base' => 120.00,
            'attiva' => true,
        ]);

        $suite = TipologiaCamera::create([
            'nome' => 'Suite Presidenziale',
            'codice' => 'SUITE',
            'descrizione' => 'Suite di lusso con salotto separato e vasca idromassaggio.',
            'capacita_adulti' => 2,
            'capacita_bambini' => 2,
            'prezzo_base' => 250.00,
            'attiva' => true,
        ]);

        // 3. Creiamo le Camere fisiche collegate alle tipologie
        Camera::create([
            'tipologia_camera_id' => $singola->id,
            'numero' => '101',
            'piano' => 1,
            'stato_pulizia' => 'pulita',
            'attiva' => true,
        ]);

        Camera::create([
            'tipologia_camera_id' => $singola->id,
            'numero' => '102',
            'piano' => 1,
            'stato_pulizia' => 'pulita',
            'attiva' => true,
        ]);

        Camera::create([
            'tipologia_camera_id' => $matrimoniale->id,
            'numero' => '201',
            'piano' => 2,
            'stato_pulizia' => 'pulita',
            'attiva' => true,
        ]);

        Camera::create([
            'tipologia_camera_id' => $matrimoniale->id,
            'numero' => '202',
            'piano' => 2,
            'stato_pulizia' => 'sporca',
            'attiva' => true,
        ]);

        Camera::create([
            'tipologia_camera_id' => $suite->id,
            'numero' => '301',
            'piano' => 3,
            'stato_pulizia' => 'pulita',
            'attiva' => true,
        ]);
    }
}