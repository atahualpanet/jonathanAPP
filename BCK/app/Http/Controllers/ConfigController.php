<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Camera;
use App\Models\TipologiaCamera;

class ConfigController extends Controller
{
    private function configPath(): string
    {
        return config_path('hotel_config.json');
    }

    private function leggiConfig(): array
    {
        $path = $this->configPath();
        if (!file_exists($path)) {
            return [];
        }
        return json_decode(file_get_contents($path), true) ?: [];
    }

    private function salvaConfig(array $config): void
    {
        file_put_contents(
            $this->configPath(),
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

public function index()
{
    $config = $this->leggiConfig();
    $linguaCorrente = session('locale', $config['localizzazione']['lingua_default'] ?? 'it');
    
    // Carica i dati per la Tab Camere
    $camere = Camera::with('tipologia')->orderBy('numero')->get();
    $tipologie = TipologiaCamera::where('attiva', 1)->get();

    // Passa TUTTE le variabili alla vista
    return view('configurazioni.index', compact('config', 'linguaCorrente', 'camere', 'tipologie'));
}

    public function update(Request $request)
{
    $request->validate([
        'locale'              => 'required|in:it,en,es,fr',
        'nome'                => 'required|string|max:255',
        'nome_commerciale'    => 'nullable|string|max:255',
        'codice_struttura'    => 'nullable|string|max:50',
        'stella'              => 'nullable|integer|min:1|max:5',
        'categoria'           => 'nullable|string|max:50',
        'valuta'              => 'nullable|string|size:3',
        'simbolo_valuta'      => 'nullable|string|max:5',
        'fuso_orario'         => 'nullable|string|max:64',
        'sito_web'            => 'nullable|url|max:255',
        'email_generale'      => 'nullable|email|max:255',
        'telefono_generale'   => 'nullable|string|max:50',
        'colore_primario'     => 'nullable|string|max:7',
        'colore_secondario'   => 'nullable|string|max:7',
    ]);

    $config = $this->leggiConfig();

    // Sezione hotel
    $config['hotel']['nome']             = $request->nome;
    $config['hotel']['nome_commerciale'] = $request->nome_commerciale ?: $request->nome;
    $config['hotel']['codice_struttura'] = $request->codice_struttura;
    $config['hotel']['stella']           = $request->stella;
    $config['hotel']['categoria']        = $request->categoria;
    $config['hotel']['valuta']           = strtoupper($request->valuta ?: 'EUR');
    $config['hotel']['simbolo_valuta']   = $request->simbolo_valuta ?: '€';
    $config['hotel']['fuso_orario']      = $request->fuso_orario;
    $config['hotel']['sito_web']         = $request->sito_web;
    $config['hotel']['email_generale']   = $request->email_generale;
    $config['hotel']['telefono_generale']= $request->telefono_generale;
    $config['hotel']['colore_primario']  = $request->colore_primario;
    $config['hotel']['colore_secondario']= $request->colore_secondario;

    // Sezione fatturazione / dati fiscali
$request->validate([
    'ragione_sociale'      => 'nullable|string|max:255',
    'partita_iva'          => 'nullable|string|max:32',
    'codice_fiscale'       => 'nullable|string|max:32',
    'codice_univoco'       => 'nullable|string|max:16',
    'pec'                  => 'nullable|email|max:255',
    'regime_fiscale'       => 'nullable|string|max:50',
    'fatt_indirizzo'       => 'nullable|string|max:255',
    'fatt_cap'             => 'nullable|string|max:10',
    'fatt_citta'           => 'nullable|string|max:100',
    'fatt_provincia'       => 'nullable|string|max:5',
    'fatt_paese'           => 'nullable|string|max:100',
    'fatt_telefono'        => 'nullable|string|max:50',
    'email_amministrazione'=> 'nullable|email|max:255',
    'email_fatturazione'   => 'nullable|email|max:255',
    'iban'                 => 'nullable|string|max:34',
    'banca'                => 'nullable|string|max:255',
    'intestatario_conto'   => 'nullable|string|max:255',
    'aliquota_iva_default' => 'nullable|numeric|min:0|max:100',
    'aliquota_iva_ristorazione' => 'nullable|numeric|min:0|max:100',
]);

$config['fatturazione']['ragione_sociale']       = $request->ragione_sociale;
$config['fatturazione']['partita_iva']           = $request->partita_iva;
$config['fatturazione']['codice_fiscale']        = $request->codice_fiscale;
$config['fatturazione']['codice_univoco']        = $request->codice_univoco;
$config['fatturazione']['pec']                   = $request->pec;
$config['fatturazione']['regime_fiscale']        = $request->regime_fiscale;
$config['fatturazione']['indirizzo']             = $request->fatt_indirizzo;
$config['fatturazione']['cap']                   = $request->fatt_cap;
$config['fatturazione']['citta']                 = $request->fatt_citta;
$config['fatturazione']['provincia']             = $request->fatt_provincia;
$config['fatturazione']['paese']                 = $request->fatt_paese;
$config['fatturazione']['telefono']              = $request->fatt_telefono;
$config['fatturazione']['email_amministrazione'] = $request->email_amministrazione;
$config['fatturazione']['email_fatturazione']    = $request->email_fatturazione;
$config['fatturazione']['iban']                  = $request->iban;
$config['fatturazione']['banca']                 = $request->banca;
$config['fatturazione']['intestatario_conto']    = $request->intestatario_conto;
$config['fatturazione']['aliquota_iva_default']  = $request->aliquota_iva_default;
$config['fatturazione']['aliquota_iva_ristorazione'] = $request->aliquota_iva_ristorazione;
    // Lingua
    $config['localizzazione']['lingua_default'] = $request->locale;

    $this->salvaConfig($config);
    session(['locale' => $request->locale]);

    return back()->with('success', 'Configurazioni aggiornate con successo.');
}

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        $config = $this->leggiConfig();

        // Rimuovi il logo precedente se esiste
        if (!empty($config['hotel']['logo_caricato'])) {
            $old = str_replace('storage/', '', $config['hotel']['logo_caricato']);
            Storage::disk('public')->delete($old);
        }

        // Salva il nuovo logo in storage/app/public/loghi
        $path = $request->file('logo')->store('loghi', 'public');
        $config['hotel']['logo_caricato'] = 'storage/' . $path;

        $this->salvaConfig($config);

        return back()->with('success', 'Logo aggiornato con successo.');
    }

    public function rimuoviLogo()
    {
        $config = $this->leggiConfig();

        if (!empty($config['hotel']['logo_caricato'])) {
            $old = str_replace('storage/', '', $config['hotel']['logo_caricato']);
            Storage::disk('public')->delete($old);
        }

        $config['hotel']['logo_caricato'] = null;
        $this->salvaConfig($config);

        return back()->with('success', 'Logo rimosso.');
    }


public function salvaCamera(Request $request)
{
    $request->validate([
        'numero' => 'required|string|max:20|unique:camere,numero',
        'piano' => 'required|integer|min:0',
        'tipologia_camera_id' => 'required|exists:tipologie_camera,id',
        'stato_pulizia' => 'required|string|in:pulita,sporca,manutenzione',
    ]);

    Camera::create([
        'numero' => $request->numero,
        'piano' => $request->piano,
        'tipologia_camera_id' => $request->tipologia_camera_id,
        'stato_pulizia' => $request->stato_pulizia,
        'attiva' => true,
    ]);

    return back()->with('success', 'Camera aggiunta con successo al catalogo.');
}

public function dettaglioCamera(Camera $camera)
{
    $camera->load('tipologia');
    return view('camere.dettaglio', compact('camera'));
}

public function salvaDettagliCamera(Request $request, Camera $camera)
{
    $request->validate([
        'note' => 'nullable|string',
        'foto' => 'nullable|image|max:2048',
        'piantina' => 'nullable|image|max:2048',
    ]);

    $camera->note = $request->note;

    if ($request->hasFile('foto')) {
        // Rimuovi la vecchia foto se esiste
        if ($camera->foto_path) Storage::disk('public')->delete($camera->foto_path);
        $camera->foto_path = $request->file('foto')->store('camere/foto', 'public');
    }

    if ($request->hasFile('piantina')) {
        if ($camera->piantina_path) Storage::disk('public')->delete($camera->piantina_path);
        $camera->piantina_path = $request->file('piantina')->store('camere/piantine', 'public');
    }

    $camera->save();

    return back()->with('success', 'Dettagli camera aggiornati.');
}

}