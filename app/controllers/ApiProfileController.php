<?php

class ApiProfileController extends ApiController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = $this->model(UserModel::class);
    }

    public function me(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();
        $profile = $this->userModel->findById($user['id']);
        $this->success(['profile' => $profile]);
    }

    public function update(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();
        $input = api_read_json_body();

        $name = trim((string) ($input['name'] ?? ''));
        $currency = trim((string) ($input['currency'] ?? 'IDR'));

        if ($name === '') {
            $this->error('Validation failed', 422, ['name is required']);
        }

        $allowedCurrency = ['IDR', 'USD', 'MYR', 'SGD'];
        if (!in_array($currency, $allowedCurrency, true)) {
            $this->error('Validation failed', 422, ['currency is invalid']);
        }

        $this->userModel->updateProfile($user['id'], $name, $currency);
        $profile = $this->userModel->findById($user['id']);

        $this->success(['profile' => $profile], 'Profile updated');
    }
}
