<?php
namespace App\Core;

class Router {
    protected $routes = [];

    public function add($route, $controller, $action) {
        $route = self::normalizePath($route);
        $controller = str_replace('/', '\\', trim($controller, '\\/'));

        if (isset($this->routes[$route])) {
            throw new \InvalidArgumentException("Duplicate route registered: {$route}");
        }

        $this->routes[$route] = [
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function dispatch($url) {
        $url = self::normalizePath($url);

        if (!array_key_exists($url, $this->routes)) {
            $this->notFound();
            return;
        }

        $controllerName = $this->resolveController($this->routes[$url]['controller']);
        if (!class_exists($controllerName)) {
            $this->serverError("Controller not found: {$controllerName}");
            return;
        }

        $controller = new $controllerName();
        $action = $this->routes[$url]['action'];
        if (!is_callable([$controller, $action])) {
            $this->serverError("Action not found: {$controllerName}::{$action}");
            return;
        }

        $controller->$action();
    }

    public function has($route) {
        return isset($this->routes[self::normalizePath($route)]);
    }

    public function all() {
        return $this->routes;
    }

    public static function normalizePath($path) {
        $path = parse_url((string) $path, PHP_URL_PATH);
        $path = str_replace('\\', '/', $path ?: '/');
        $path = preg_replace('#/+#', '/', $path);
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function resolveController($controller) {
        if (strpos($controller, 'App\\Controller\\') === 0) {
            return $controller;
        }

        return 'App\\Controller\\' . $controller;
    }

    private function notFound() {
        http_response_code(404);
        echo '404 Not Found';
    }

    private function serverError($message) {
        http_response_code(500);
        $debug = strtolower((string) getenv('APP_DEBUG'));

        if (in_array($debug, ['1', 'true', 'yes', 'on'], true)) {
            echo '500 Server Error: ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            return;
        }

        echo '500 Server Error';
    }
}
