<?php

namespace App\Services;

use RuntimeException;

class MailService {
    public static function isConfigured(): bool {
        $config = self::config();
        return !empty($config['enabled'])
            && trim((string)$config['host']) !== ''
            && filter_var($config['from_email'], FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function sendHtml(string $to, string $subject, string $html): void {
        if (!self::isConfigured()) {
            throw new RuntimeException('SMTP chưa được cấu hình hoặc đang tắt.');
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email người nhận không hợp lệ.');
        }

        $config = self::config();
        $encryption = strtolower((string)($config['encryption'] ?? 'tls'));
        $host = (string)$config['host'];
        $port = (int)($config['port'] ?? 587);
        $socketHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
        $errno = 0;
        $error = '';
        $socket = @stream_socket_client(
            $socketHost . ':' . $port,
            $errno,
            $error,
            15,
            STREAM_CLIENT_CONNECT
        );
        if (!$socket) {
            throw new RuntimeException('Không kết nối được SMTP: ' . ($error ?: 'lỗi không xác định'));
        }

        stream_set_timeout($socket, 15);
        try {
            self::expect($socket, [220]);
            self::command($socket, 'EHLO paceup.local', [250]);

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                $cryptoEnabled = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($cryptoEnabled !== true) {
                    throw new RuntimeException('Không thể bật mã hóa TLS cho SMTP.');
                }
                self::command($socket, 'EHLO paceup.local', [250]);
            }

            $username = trim((string)($config['username'] ?? ''));
            if ($username !== '') {
                self::command($socket, 'AUTH LOGIN', [334]);
                self::command($socket, base64_encode($username), [334]);
                self::command($socket, base64_encode((string)($config['password'] ?? '')), [235]);
            }

            self::command($socket, 'MAIL FROM:<' . $config['from_email'] . '>', [250]);
            self::command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::command($socket, 'DATA', [354]);

            $headers = [
                'From: ' . self::encodeHeader((string)$config['from_name']) . ' <' . $config['from_email'] . '>',
                'To: <' . $to . '>',
                'Subject: ' . self::encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: base64'
            ];
            $message = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($html));
            self::write($socket, $message . "\r\n.");
            self::expect($socket, [250]);
            self::command($socket, 'QUIT', [221, 250]);
        } finally {
            fclose($socket);
        }
    }

    private static function config(): array {
        static $config;
        if ($config === null) {
            $config = require __DIR__ . '/../../config/mail.php';
        }
        return $config;
    }

    private static function command($socket, string $command, array $expected): void {
        self::write($socket, $command);
        self::expect($socket, $expected);
    }

    private static function write($socket, string $data): void {
        fwrite($socket, $data . "\r\n");
    }

    private static function expect($socket, array $expected): void {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int)substr(trim($response), 0, 3);
        if (!in_array($code, $expected, true)) {
            throw new RuntimeException('SMTP trả về mã ' . $code . ': ' . trim($response));
        }
    }

    private static function encodeHeader(string $value): string {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
