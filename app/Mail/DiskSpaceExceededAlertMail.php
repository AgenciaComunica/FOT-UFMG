<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DiskSpaceExceededAlertMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $contexto,
        public readonly ?int $freeMb,
        public readonly int $thresholdMb,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Alerta de espaço em disco - Portal de Inscrições')
            ->view('emails.disk-space-exceeded-alert');
    }
}
