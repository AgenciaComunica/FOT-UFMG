<?php

namespace App\Mail;

use App\Models\Edital;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadNovidadeEditalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nome,
        public Edital $edital,
        public string $tipoAviso,
        public string $portalUrl,
        public string $editalUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->tipoAviso === 'encerrando'
            ? 'Edital próximo de encerrar - '.$this->edital->titulo
            : 'Novo edital disponível - '.$this->edital->titulo;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.leads.novidade-edital',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
