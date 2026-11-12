<!DOCTYPE html>
<html lang="it">
<body style="font-family: Arial, Helvetica, sans-serif; color:#0A2239; line-height:1.5;">
    <p>Buongiorno {{ $utente->nome }},</p>
    <p>la tua password di accesso a Jonathan PMS è stata reimpostata. Usa questi dati per entrare:</p>
    <p>Email:</p>
    <p><b>{{ $utente->email }}</b></p>
    <p>Password temporanea:</p>
    <p><b>{{ $temporanea }}</b></p>
    <p><a href="{{ url('/login') }}">Vai alla pagina di accesso</a></p>
    <p>Al primo accesso ti verrà chiesto di scegliere una nuova password personale (almeno 8 caratteri).</p>
    <p style="color:#64748b; font-size:13px;">Se non ti aspettavi questa email, avvisa la direzione.</p>
</body>
</html>
