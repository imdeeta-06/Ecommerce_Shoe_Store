<?php

$enabled = filter_var((string)(getenv('SMTP_ENABLED') ?: 'false'), FILTER_VALIDATE_BOOLEAN);
$appEnvironment = strtolower(trim((string)(getenv('APP_ENV') ?: 'local')));
$credentialsRotated = filter_var(
    (string)(getenv('SMTP_CREDENTIALS_ROTATED') ?: 'false'),
    FILTER_VALIDATE_BOOLEAN
);

// Production không được dùng lại App Password từng xuất hiện trong ảnh/chat.
if ($appEnvironment === 'production' && !$credentialsRotated) {
    $enabled = false;
}

return [
    'enabled' => $enabled,
    'credentials_rotated' => $credentialsRotated,
    'host' => trim((string)(getenv('SMTP_HOST') ?: 'smtp.gmail.com')),
    'port' => (int)(getenv('SMTP_PORT') ?: 587),
    'username' => trim((string)(getenv('SMTP_USERNAME') ?: '')),
    'password' => (string)(getenv('SMTP_PASSWORD') ?: ''),
    'encryption' => strtolower(trim((string)(getenv('SMTP_ENCRYPTION') ?: 'tls'))),
    'from_email' => trim((string)(getenv('SMTP_FROM_EMAIL') ?: '')),
    'from_name' => trim((string)(getenv('SMTP_FROM_NAME') ?: 'Liên Hoa Shop')),
];
