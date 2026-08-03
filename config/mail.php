<?php

return [
    // Bật sau khi điền thông tin SMTP thật. Mặc định tắt để môi trường local
    // không tự gửi email ra ngoài.
    'enabled' => filter_var(getenv('PACEUP_SMTP_ENABLED') ?: '0', FILTER_VALIDATE_BOOLEAN),
    'host' => getenv('PACEUP_SMTP_HOST') ?: '',
    'port' => (int)(getenv('PACEUP_SMTP_PORT') ?: 587),
    'username' => getenv('PACEUP_SMTP_USERNAME') ?: '',
    'password' => getenv('PACEUP_SMTP_PASSWORD') ?: '',
    'encryption' => getenv('PACEUP_SMTP_ENCRYPTION') ?: 'tls',
    'from_email' => getenv('PACEUP_MAIL_FROM') ?: 'no-reply@paceup.local',
    'from_name' => getenv('PACEUP_MAIL_FROM_NAME') ?: 'PaceUp'
];
