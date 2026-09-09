<?php
require __DIR__ . '/../app/Core/App.php';
$routes = App\Core\App::router()->all();
$failures = [];
foreach ($routes as $path => $route) {
    $class = 'App\\Controller\\' . $route['controller'];
    if (!class_exists($class) || !method_exists($class, $route['action'])
        || !(new ReflectionMethod($class, $route['action']))->isPublic()) {
        $failures[] = "$path -> $class::{$route['action']}";
    }
}
echo count($routes) . ' routes checked; ' . count($failures) . " missing actions\n";
foreach ($failures as $failure) echo "$failure\n";
exit($failures ? 1 : 0);
