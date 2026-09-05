<?php

$enabled = filter_var((string)(\App\Core\App::env('SMTP_ENABLED') ?: 'false'), FILTER_VALIDATE_BOOLEAN);
$appEnvironment = strtolower(trim((string)(\App\Core\App::env('APP_ENV') ?: 'local')));
$credentialsRotated = filter_var(
    (string)(\App\Core\App::env('SMTP_CREDENTIALS_ROTATED') ?: 'false'),
    FILTER_VALIDATE_BOOLEAN
);

// Production không được dùng lại App Password từng xuất hiện trong ảnh/chat.
if ($appEnvironment === 'production' && !$credentialsRotated) {
    $enabled = false;
}

return [
    'enabled' => $enabled,
    'credentials_rotated' => $credentialsRotated,
    'host' => trim((string)(\App\Core\App::env('SMTP_HOST') ?: 'smtp.gmail.com')),
    'port' => (int)(\App\Core\App::env('SMTP_PORT') ?: 587),
    'username' => trim((string)(\App\Core\App::env('SMTP_USERNAME') ?: '')),
    'password' => (string)(\App\Core\App::env('SMTP_PASSWORD') ?: ''),
    'encryption' => strtolower(trim((string)(\App\Core\App::env('SMTP_ENCRYPTION') ?: 'tls'))),
    'from_email' => trim((string)(\App\Core\App::env('SMTP_FROM_EMAIL') ?: '')),
    'from_name' => trim((string)(\App\Core\App::env('SMTP_FROM_NAME') ?: 'Liên Hoa Shop')),
];
