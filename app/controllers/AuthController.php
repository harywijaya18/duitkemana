<?php

class AuthController extends Controller
{
    private UserModel $userModel;
    private CategoryModel $categoryModel;

    public function __construct()
    {
        $this->userModel = $this->model(UserModel::class);
        $this->categoryModel = $this->model(CategoryModel::class);
    }

    public function login(): void
    {
        if (auth_user()) {
            redirect(is_admin_user() ? '/admin/dashboard' : '/');
        }

        $this->view('login');
    }

    public function doLogin(): void
    {
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/login');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            flash('error', t('Email or password is incorrect.'));
            redirect('/login');
        }

        if (($user['status'] ?? 'active') === 'suspended') {
            flash('error', 'Akun Anda sedang dinonaktifkan. Hubungi admin.');
            redirect('/login');
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'currency' => $user['currency'],
        ];

        $this->userModel->touchLastLogin((int) $user['id']);

        redirect(is_admin_user($_SESSION['user']) ? '/admin/dashboard' : '/');
    }

    public function register(): void
    {
        if (auth_user()) {
            redirect('/');
        }

        $this->view('register');
    }

    public function doRegister(): void
    {
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/register');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $currency = trim($_POST['currency'] ?? 'IDR');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            flash('error', t('Please complete data correctly. Password min 6 chars.'));
            save_old_input($_POST);
            redirect('/register');
        }

        if ($this->userModel->findByEmail($email)) {
            flash('error', t('Email already used.'));
            save_old_input($_POST);
            redirect('/register');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userId = $this->userModel->create($name, $email, $hash, $currency ?: 'IDR');
        $this->categoryModel->createDefaultForUser($userId);

        flash('success', t('Registration successful. Please login.'));
        clear_old_input();
        redirect('/login');
    }

    public function logout(): void
    {
        if (!verify_csrf()) {
            redirect('/');
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        redirect('/login');
    }
}
