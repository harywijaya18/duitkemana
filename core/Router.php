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

        if (strpos($path, '/api/') === 0) {
            $this->enforceApiRateLimit($path);
        }

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

    private function enforceApiRateLimit(string $path): void
    {
        $limiter = new ApiRateLimiter();
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $key = 'api:' . $ip;

        $result = $limiter->check($key, API_RATE_LIMIT_MAX, API_RATE_LIMIT_WINDOW_SECONDS);

        header('X-RateLimit-Limit: ' . (int) $result['limit']);
        header('X-RateLimit-Remaining: ' . (int) $result['remaining']);
        header('X-RateLimit-Reset: ' . (int) $result['reset_at']);

        if (!empty($result['allowed'])) {
            return;
        }

        $requestId = trim((string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? ''));
        if ($requestId === '') {
            $requestId = bin2hex(random_bytes(12));
        }

        http_response_code(429);
        header('Content-Type: application/json');
        header('Retry-After: ' . (int) $result['retry_after']);

        $payload = [
            'success' => false,
            'message' => 'Too many requests',
            'code' => 'RATE_LIMITED',
            'error' => [
                'code' => 'RATE_LIMITED',
                'message' => 'Too many requests',
                'details' => [],
                'status' => 429,
            ],
            'errors' => [],
            'meta' => [
                'timestamp' => gmdate('c'),
                'request_id' => $requestId,
                'status' => 429,
                'api_version' => strpos($path, '/api/v1/') === 0 ? 'v1' : 'v0',
                'path' => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '',
            ],
        ];

        echo json_encode($payload);
        exit;
    }
}
