<?php

class ProfileController extends Controller
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = $this->model(UserModel::class);
    }

    public function index(): void
    {
        require_auth();
        $user = auth_user();
        $profile = $this->userModel->findById((int) $user['id']);

        $this->view('profile', [
            'profile' => $profile,
        ]);
    }

    public function update(): void
    {
        require_auth();
        if (!verify_csrf()) {
            redirect('/profile');
        }

        $user = auth_user();
        $name = trim($_POST['name'] ?? '');
        $currency = trim($_POST['currency'] ?? 'IDR');

        if ($name === '') {
            flash('error', t('Name is required.'));
            redirect('/profile');
        }

        $this->userModel->updateProfile((int) $user['id'], $name, $currency ?: 'IDR');
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['currency'] = $currency ?: 'IDR';

        flash('success', t('Profile updated.'));
        redirect('/profile');
    }
}
