<?php

return [
    'imap' => [
        'host' => env('MAIL_IMAP_HOST', 'mail.iranserver.com'),
        'port' => env('MAIL_IMAP_PORT', 993),
        'encryption' => env('MAIL_IMAP_ENCRYPTION', 'ssl'),
    ],
    'smtp' => [
        'host' => env('MAIL_SMTP_HOST', 'mail.iranserver.com'),
        'port' => env('MAIL_SMTP_PORT', 465),
        'encryption' => env('MAIL_SMTP_ENCRYPTION', 'ssl'),
    ],
];
