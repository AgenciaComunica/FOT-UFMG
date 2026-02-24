<?php

namespace App\Mail;

use App\Models\Edital;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EditalEncerradoDocentesMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Edital $edital)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Inscrições encerradas - '.$this->edital->titulo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.edital-encerrado-docentes',
        );
    }
}

