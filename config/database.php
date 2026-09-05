<?php

$required = static function (string $key): string {
    $value = getenv($key);
    if ($value === false || trim((string)$value) === '') {
        throw new RuntimeException("Thiếu cấu hình {$key}. Sao chép .env.example sang .env và điền thông tin database.");
    }
    return trim((string)$value);
};

return [
    'host' => trim((string)(getenv('DB_HOST') ?: '127.0.0.1')),
    'port' => (int)(getenv('DB_PORT') ?: 3306),
    'unix_socket' => trim((string)(getenv('DB_UNIX_SOCKET') ?: '')),
    'dbname' => $required('DB_NAME'),
    'user' => $required('DB_USER'),
    'password' => $required('DB_PASSWORD'),
    'charset' => 'utf8mb4'
];
