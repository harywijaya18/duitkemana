<?php

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($basePath !== '' && $basePath !== '/') {
            $path = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $path);
        }

        $path = $path ?: '/';

        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo t('404 - Page not found');
            return;
        }

        [$controllerName, $methodName] = $handler;
        $controller = new $controllerName();
        $controller->$methodName();
    }
}
