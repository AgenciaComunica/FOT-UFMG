<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;

class CustomizeFormatter
{
    public function __invoke(Logger $logger): void
    {
        // Example output:
        // [2026-03-02 16:45:12 America/Sao_Paulo] production.ERROR: message {...}
        $format = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $dateFormat = 'Y-m-d H:i:s e';

        $formatter = new LineFormatter($format, $dateFormat, true, true);
        $formatter->includeStacktraces(true);

        $monolog = $logger->getLogger();

        foreach ($monolog->getHandlers() as $handler) {
            $handler->setFormatter($formatter);
        }
    }
}
