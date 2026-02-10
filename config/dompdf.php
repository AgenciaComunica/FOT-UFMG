<?php

return [
    'public_path' => (function () {
        $candidates = [
            base_path('../public_html/saas'),
            base_path('../public_html'),
            base_path('public'),
        ];

        foreach ($candidates as $path) {
            $real = realpath($path);
            if ($real !== false) {
                return $real;
            }
        }

        return base_path('public');
    })(),
];
