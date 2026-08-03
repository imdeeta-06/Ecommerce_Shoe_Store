<?php
$requestUri = $_SERVER['REQUEST_URI'];
$folderName = '/' . basename(__DIR__);
if (strpos($requestUri, $folderName) === 0) {
    echo "BASE_URL is " . $folderName . '/';
} else {
    $baseDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    echo "BASE_URL is " . rtrim($baseDir, '/') . '/';
}
