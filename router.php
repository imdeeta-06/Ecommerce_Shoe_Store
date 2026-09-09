<?php
/**
 * Router script cho PHP built-in development server.
 * Serve static files (CSS, JS, images...) trực tiếp,
 * còn lại chuyển qua index.php (MVC router).
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

$protectedPrefixes = ['/app/', '/config/', '/Database/', '/scripts/', '/tests/', '/.git/', '/.vs/', '/public/views/'];
$protectedFiles = [
    '/seed.php',
    '/migrate_auth.php',
    '/check.php',
    '/check2.php',
    '/check_connection.php',
    '/test_db.php',
    '/debug2.php',
    '/debug_route.php',
    '/public/assets/index.php',
    '/README.md',
    '/patch.js',
    '/.env'
];

$isProtected = in_array($uri, $protectedFiles, true) || strpos($uri, '/.env.') === 0;
$isRootPhp = preg_match('#^/[^/]+\.php$#i', $uri) === 1 && strcasecmp($uri, '/index.php') !== 0;
$isProtected = $isProtected || $isRootPhp;
foreach ($protectedPrefixes as $prefix) {
    if (strpos($uri . '/', rtrim($prefix, '/') . '/') === 0) {
        $isProtected = true;
        break;
    }
}

if ($isProtected) {
    http_response_code(404);
    echo '404 Not Found';
    return true;
}

if ($uri !== '/' && file_exists($file) && is_file($file)) {
    return false; // Serve file tĩnh trực tiếp
}

require_once __DIR__ . '/index.php';
