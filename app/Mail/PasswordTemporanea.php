<?php

namespace App\Mail;

use App\Models\Utente;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordTemporanea extends Mailable
{
    public function __construct(public Utente $utente, public string $temporanea)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Jonathan PMS — la tua password temporanea');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password-temporanea');
    }
}
