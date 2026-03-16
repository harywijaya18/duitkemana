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
        app_log('[AuthController::doLogin] start');
        
        if (!verify_csrf()) {
            app_log('[AuthController::doLogin] invalid csrf');
            flash('error', t('Invalid request token.'));
            redirect('/login');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        try {
            $user = $this->userModel->findByEmail($email);
        } catch (\Throwable $e) {
            $this->logAuthError('[AuthController::doLogin] DB error: ' . $e->getMessage());
            flash('error', 'Login gagal sementara. Periksa konfigurasi database/server.');
            redirect('/login');
        }

        if (!$user || !password_verify($password, $user['password'])) {
            app_log('[AuthController::doLogin] invalid credentials for ' . $email);
            flash('error', t('Email or password is incorrect.'));
            redirect('/login');
        }

        if (($user['status'] ?? 'active') === 'suspended') {
            app_log('[AuthController::doLogin] suspended user ' . ($user['email'] ?? 'unknown'));
            flash('error', 'Akun Anda sedang dinonaktifkan. Hubungi admin.');
            redirect('/login');
        }

        app_log('[AuthController::doLogin] login success, setting session for ' . $email);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'currency' => $user['currency'],
        ];
        persist_auth_cookie($_SESSION['user']);

        try {
            $this->userModel->touchLastLogin((int) $user['id']);
        } catch (\Throwable $e) {
            $this->logAuthError('[AuthController::doLogin] touchLastLogin error: ' . $e->getMessage());
            // Keep login successful even if telemetry update fails.
        }

        $target = is_admin_user($_SESSION['user']) ? '/admin/dashboard' : '/';
        app_log('[AuthController::doLogin] redirecting to ' . $target);
        redirect($target);
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

        try {
            if ($this->userModel->findByEmail($email)) {
                flash('error', t('Email already used.'));
                save_old_input($_POST);
                redirect('/register');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $userId = $this->userModel->create($name, $email, $hash, $currency ?: 'IDR');

            try {
                $this->categoryModel->createDefaultForUser($userId);
            } catch (\Throwable $e) {
                $this->logAuthError('[AuthController::doRegister] createDefaultForUser error: ' . $e->getMessage());
                // Do not block registration if default category seeding fails.
            }
        } catch (\Throwable $e) {
            $this->logAuthError('[AuthController::doRegister] DB error: ' . $e->getMessage());
            flash('error', 'Registrasi gagal. Periksa koneksi database, struktur tabel, dan hak akses user database.');
            save_old_input($_POST);
            redirect('/register');
        }

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
        clear_auth_cookie();
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        redirect('/login');
    }

    private function logAuthError(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        if (defined('APP_LOG_FILE')) {
            @file_put_contents(APP_LOG_FILE, $line, FILE_APPEND);
        }
        error_log($message);
    }
}
