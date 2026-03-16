<?php

class ApiController extends Controller
{
    protected ?array $apiUser = null;

    protected function success(array $data = [], string $message = 'OK', int $statusCode = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    protected function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    protected function requireApiAuth(): void
    {
        $token = api_bearer_token();
        if (!$token) {
            $this->error('Unauthorized: token not found', 401);
        }

        $tokenModel = $this->model(ApiTokenModel::class);
        $user = $tokenModel->findUserByToken($token);

        if (!$user) {
            $this->error('Unauthorized: invalid token', 401);
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
}
