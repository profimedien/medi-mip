<?php

return [
    // Kommagetrennt. Gesetzt (Testsystem): Mails gehen nur an diese Adressen, alle anderen werden verworfen und protokolliert.
    'mail_whitelist' => array_values(array_filter(array_map('trim', explode(',', (string) env('MIP_MAIL_WHITELIST', ''))))),

    // Erinnerung an offene Rückmeldungen: so viele Stunden vor Fristende
    'form_reminder_hours' => 48,

    // Geheimnis für den KAS-Cronjob-Aufruf /cron/{secret}
    'cron_secret' => env('CRON_SECRET'),

    // PHP für die Befehle des Schedulers beim Aufruf über /cron (All-Inkl: /usr/bin/php84)
    'php_binary' => env('MIP_PHP_BINARY'),
];
