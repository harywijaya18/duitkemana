<?php

class ApiController extends Controller
{
    protected ?array $apiUser = null;

    protected function success(array $data = [], string $message = 'OK', int $statusCode = 200): void
    {
        $this->applyApiVersionHeaders();
        $this->logApiResponse($statusCode, '');
        $this->json([
            'success' => true,
            'message' => $message,
            'code' => 'SUCCESS',
            'data' => $data,
            'meta' => $this->responseMeta($statusCode),
        ], $statusCode);
    }

    protected function error(string $message, int $statusCode = 400, array $errors = [], ?string $errorCode = null): void
    {
        $this->applyApiVersionHeaders();
        $code = $this->normalizeErrorCode($errorCode ?: $this->defaultErrorCode($statusCode));
        $this->logApiResponse($statusCode, $code);
        $this->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $errors,
                'status' => $statusCode,
            ],
            'errors' => $errors,
            'meta' => $this->responseMeta($statusCode),
        ], $statusCode);
    }

    protected function requireApiAuth(): void
    {
        $token = api_bearer_token();
        if (!$token) {
            $this->error('Unauthorized: token not found', 401, [], 'AUTH_TOKEN_MISSING');
        }

        $tokenModel = $this->model(ApiTokenModel::class);
        $user = $tokenModel->findUserByToken($token);

        if (!$user) {
            $this->error('Unauthorized: invalid token', 401, [], 'AUTH_TOKEN_INVALID');
        }

        $this->apiUser = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'currency' => $user['currency'],
        ];
    }

    protected function apiUser(): array
    {
        return $this->apiUser ?? [];
    }

    private function applyApiVersionHeaders(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

        if (strpos($path, '/api/v1/') !== false) {
            header('X-API-Version: v1');
            header('X-API-Deprecated: false');
            return;
        }

        if (strpos($path, '/api/') !== false) {
            header('X-API-Version: v0');
            header('X-API-Deprecated: true');
            header('Sunset: Wed, 31 Dec 2026 23:59:59 GMT');
            header('Deprecation: true');
            header('Link: </api/v1>; rel="successor-version"');
        }
    }

    private function responseMeta(int $statusCode): array
    {
        return [
            'timestamp' => gmdate('c'),
            'request_id' => $this->requestId(),
            'status' => $statusCode,
            'api_version' => $this->apiVersion(),
            'path' => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '',
        ];
    }

    private function requestId(): string
    {
        $existing = trim((string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        try {
            return bin2hex(random_bytes(12));
        } catch (Throwable $e) {
            return sha1((string) microtime(true) . (string) mt_rand());
        }
    }

    private function apiVersion(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        return strpos($path, '/api/v1/') !== false ? 'v1' : 'v0';
    }

    private function defaultErrorCode(int $statusCode): string
    {
        $map = [
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'VALIDATION_FAILED',
            429 => 'RATE_LIMITED',
            500 => 'INTERNAL_ERROR',
            503 => 'SERVICE_UNAVAILABLE',
        ];

        return $map[$statusCode] ?? 'API_ERROR';
    }

    private function normalizeErrorCode(string $code): string
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return 'API_ERROR';
        }
        return preg_replace('/[^A-Z0-9_]/', '_', $code) ?: 'API_ERROR';
    }

    private function logApiResponse(int $statusCode, string $errorCode): void
    {
        try {
            /** @var ApiRequestLogModel $logModel */
            $logModel = $this->model(ApiRequestLogModel::class);
            $requestStart = (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
            $durationMs = (int) max(0, round((microtime(true) - $requestStart) * 1000));

            $logModel->logEvent([
                'user_id' => isset($this->apiUser['id']) ? (int) $this->apiUser['id'] : null,
                'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
                'path' => (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''),
                'query_string' => (string) ($_SERVER['QUERY_STRING'] ?? ''),
                'status_code' => $statusCode,
                'error_code' => $errorCode,
                'duration_ms' => $durationMs,
                'ip_address' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ]);
        } catch (Throwable $e) {
            // Logging must never break API responses.
        }
    }
}
