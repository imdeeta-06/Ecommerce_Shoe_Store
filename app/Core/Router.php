<?php
namespace App\Core;

class Router {
    protected $routes = [];
    
    public function add($route, $controller, $action) {
        $this->routes[$route] = ['controller' => $controller, 'action' => $action];
    }
    
    public function dispatch($url) {
        // Simple routing
        $url = strtok($url, '?');
        if ($url === '') $url = '/';
        
        // Exact match
        if (array_key_exists($url, $this->routes)) {
            $controllerName = "App\\Controller\\" . $this->routes[$url]['controller'];
            $controller = new $controllerName();
            $action = $this->routes[$url]['action'];
            $controller->$action();
            return;
        }
        
        http_response_code(404);
        echo "404 Not Found";
    }
}