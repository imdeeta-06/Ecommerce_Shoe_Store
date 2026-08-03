<?php
$baseDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$BASE_URL = rtrim($baseDir, '/') . '/';

$url = $_SERVER['REQUEST_URI'];
$basePath = parse_url($BASE_URL, PHP_URL_PATH);
$basePathNoSlash = rtrim($basePath, '/');
if ($basePathNoSlash !== '' && strpos($url, $basePathNoSlash) === 0) {
    $url = substr($url, strlen($basePathNoSlash));
}
$url = '/' . ltrim($url, '/');

echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "BASE_URL: " . $BASE_URL . "\n";
echo "basePath: " . $basePath . "\n";
echo "basePathNoSlash: " . $basePathNoSlash . "\n";
echo "Final URL: " . $url . "\n";
