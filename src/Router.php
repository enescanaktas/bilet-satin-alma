<?php

namespace App;

class Router
{
    private $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Try exact match first
        if (isset($this->routes[$method][$uri])) {
            $handler = $this->routes[$method][$uri];
            call_user_func($handler);
            return;
        }

        // Try pattern matching
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            // Convert route pattern to regex
            $regex = preg_replace('/\{[^\}]+\}/', '([^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';
            
            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches); // Remove full match
                call_user_func_array($handler, $matches);
                return;
            }
        }

        http_response_code(404);
        echo "404 - Page not found";
    }
}
