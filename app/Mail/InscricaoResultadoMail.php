<?php

namespace App\Mail;

use App\Models\Inscricao;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InscricaoResultadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Inscricao $inscricao,
        public string $statusPublico,
        public string $statusUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Resultado da inscrição - '.$this->inscricao->protocolo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inscricao-resultado',
        );
    }
}

