<?php

return [
    'max_pdf_kb' => env('INSCRICAO_MAX_PDF_KB', 10240),
    'honeypot_field' => env('INSCRICAO_HONEYPOT_FIELD', 'website'),
    'edit_link_hours' => env('INSCRICAO_EDIT_LINK_HOURS', 24),
    'disk_min_free_mb' => env('INSCRICAO_DISK_MIN_FREE_MB', 512),
    'disk_alert_cooldown_minutes' => env('INSCRICAO_DISK_ALERT_COOLDOWN_MINUTES', 60),
    'disk_alert_email' => env('ADMIN_ALERT_EMAIL'),
];
