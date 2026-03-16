<?php

class ApiAuthController extends ApiController
{
    private UserModel $userModel;
    private ApiTokenModel $tokenModel;
    private CategoryModel $categoryModel;

    public function __construct()
    {
        $this->userModel = $this->model(UserModel::class);
        $this->tokenModel = $this->model(ApiTokenModel::class);
        $this->categoryModel = $this->model(CategoryModel::class);
    }

    public function register(): void
    {
        $input = api_read_json_body();

        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = (string) ($input['password'] ?? '');
        $currency = trim($input['currency'] ?? 'IDR');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $this->error('Validation failed', 422, ['name/email/password is invalid'], 'AUTH_VALIDATION_FAILED');
        }

        if ($this->userModel->findByEmail($email)) {
            $this->error('Email already exists', 409, [], 'AUTH_EMAIL_EXISTS');
        }

        $userId = $this->userModel->create($name, $email, password_hash($password, PASSWORD_DEFAULT), $currency ?: 'IDR');
        $this->categoryModel->createDefaultForUser($userId);

        $deviceName = trim($input['device_name'] ?? 'mobile-app');
        $tokens = $this->tokenModel->issueTokenPair((int) $userId, $deviceName);

        $this->success([
            'token' => $tokens['access_token'],
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_at' => $tokens['access_expires_at'],
            'refresh_expires_at' => $tokens['refresh_expires_at'],
            'user' => $this->userModel->findById($userId),
        ], 'Register successful', 201);
    }

    public function login(): void
    {
        $input = api_read_json_body();
        $email = trim($input['email'] ?? '');
        $password = (string) ($input['password'] ?? '');

        $user = $this->userModel->findByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            $this->error('Invalid credentials', 401, [], 'AUTH_INVALID_CREDENTIALS');
        }

        if (($user['status'] ?? 'active') === 'suspended') {
            $this->error('Account suspended', 403, [], 'AUTH_ACCOUNT_SUSPENDED');
        }

        $deviceName = trim($input['device_name'] ?? 'mobile-app');
        $tokens = $this->tokenModel->issueTokenPair((int) $user['id'], $deviceName);

        $this->success([
            'token' => $tokens['access_token'],
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_at' => $tokens['access_expires_at'],
            'refresh_expires_at' => $tokens['refresh_expires_at'],
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'currency' => $user['currency'],
            ],
        ], 'Login successful');
    }

    public function refresh(): void
    {
        $input = api_read_json_body();
        $refreshToken = trim((string) ($input['refresh_token'] ?? ''));
        if ($refreshToken === '') {
            $this->error('Refresh token is required', 422, ['refresh_token is required'], 'AUTH_REFRESH_TOKEN_REQUIRED');
        }

        $tokens = $this->tokenModel->refreshAccessToken($refreshToken);
        if (!$tokens) {
            $this->error('Invalid or expired refresh token', 401, [], 'AUTH_REFRESH_TOKEN_INVALID');
        }

        $this->success([
            'token' => $tokens['access_token'],
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_at' => $tokens['access_expires_at'],
            'refresh_expires_at' => $tokens['refresh_expires_at'],
        ], 'Token refreshed');
    }

    public function logout(): void
    {
        $token = api_bearer_token();
        if (!$token) {
            $this->error('Unauthorized', 401, [], 'AUTH_TOKEN_MISSING');
        }

        $this->tokenModel->deleteToken($token);
        $this->success([], 'Logout successful');
    }
}
