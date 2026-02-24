<?php

namespace App\Mail;

use App\Models\Edital;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EditalPublicadoDocentesMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Edital $edital)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Você foi designado para a banca do edital: '.$this->edital->titulo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.edital-publicado-docentes',
            with: [
                'edital' => $this->edital,
            ],
        );
    }
}

