<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocenteCredenciaisIniciaisMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nome,
        public string $email,
        public string $senhaTemporaria,
        public string $loginUrl,
        public string $forgotPasswordUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Acesso inicial - Plataforma da Secretaria',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.docente-credenciais-iniciais',
        );
    }
}

