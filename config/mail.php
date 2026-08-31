<?php

$enabled = filter_var((string)(getenv('SMTP_ENABLED') ?: 'false'), FILTER_VALIDATE_BOOLEAN);

return [
    'enabled' => $enabled,
    'host' => trim((string)(getenv('SMTP_HOST') ?: 'smtp.gmail.com')),
    'port' => (int)(getenv('SMTP_PORT') ?: 587),
    'username' => trim((string)(getenv('SMTP_USERNAME') ?: '')),
    'password' => (string)(getenv('SMTP_PASSWORD') ?: ''),
    'encryption' => strtolower(trim((string)(getenv('SMTP_ENCRYPTION') ?: 'tls'))),
    'from_email' => trim((string)(getenv('SMTP_FROM_EMAIL') ?: '')),
    'from_name' => trim((string)(getenv('SMTP_FROM_NAME') ?: 'Liên Hoa Shop')),
];
