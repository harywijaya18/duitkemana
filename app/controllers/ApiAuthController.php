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
            $this->error('Validation failed', 422, ['name/email/password is invalid']);
        }

        if ($this->userModel->findByEmail($email)) {
            $this->error('Email already exists', 409);
        }

        $userId = $this->userModel->create($name, $email, password_hash($password, PASSWORD_DEFAULT), $currency ?: 'IDR');
        $this->categoryModel->createDefaultForUser($userId);

        $token = bin2hex(random_bytes(32));
        $deviceName = trim($input['device_name'] ?? 'mobile-app');
        $this->tokenModel->create($userId, $token, $deviceName);

        $this->success([
            'token' => $token,
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
            $this->error('Invalid credentials', 401);
        }

        $token = bin2hex(random_bytes(32));
        $deviceName = trim($input['device_name'] ?? 'mobile-app');
        $this->tokenModel->create((int) $user['id'], $token, $deviceName);

        $this->success([
            'token' => $token,
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'currency' => $user['currency'],
            ],
        ], 'Login successful');
    }

    public function logout(): void
    {
        $token = api_bearer_token();
        if (!$token) {
            $this->error('Unauthorized', 401);
        }

        $this->tokenModel->deleteToken($token);
        $this->success([], 'Logout successful');
    }
}
