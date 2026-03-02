<?php

namespace App\Mail;

use App\Models\Inscricao;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InscricaoEditarLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Inscricao $inscricao,
        public string $editUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Link para edição da inscrição - '.$this->inscricao->protocolo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inscricao-editar-link',
        );
    }
}

