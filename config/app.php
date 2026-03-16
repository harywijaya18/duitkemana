<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'app');
define('PUBLIC_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'public');
define('UPLOAD_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads');
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

$adminEmailsRaw = getenv('ADMIN_EMAILS') ?: 'admin@duitkemana.com';
$adminEmails = array_values(array_filter(array_map('trim', explode(',', (string) $adminEmailsRaw))));
define('ADMIN_EMAILS', $adminEmails);

date_default_timezone_set('Asia/Jakarta');

spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/core/' . $class . '.php',
        APP_PATH . '/controllers/' . $class . '.php',
        APP_PATH . '/models/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

require_once APP_PATH . '/helpers/helpers.php';
require_once APP_PATH . '/helpers/pdf_helper.php';
require_once APP_PATH . '/helpers/api_helper.php';
