<?php

$basePath = dirname(__DIR__);
$bootstrapLogFile = $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app-debug.log';
$bootstrapLog = static function (string $message) use ($bootstrapLogFile): void {
    $line = '[' . date('Y-m-d H:i:s') . '] [bootstrap] ' . $message . PHP_EOL;
    @file_put_contents($bootstrapLogFile, $line, FILE_APPEND);
};

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionDir = $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0775, true);
    }
    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        session_save_path($sessionDir);
        $bootstrapLog('session_save_path set to ' . $sessionDir);
    } else {
        $bootstrapLog('session_save_path fallback to default=' . session_save_path());
    }

    $httpsServerFlag = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    $requestScheme = strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? ''));
    $serverPort = (string) ($_SERVER['SERVER_PORT'] ?? '');
    $isHttps = ($httpsServerFlag === 'on' || $httpsServerFlag === '1' || $requestScheme === 'https' || $serverPort === '443');

    session_name('duitkemana_session');
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    if (!session_start()) {
        $bootstrapLog('session_start failed');
        error_log('[app.php] session_start failed');
    } else {
        $hasCookie = isset($_COOKIE[session_name()]) ? 'yes' : 'no';
        $bootstrapLog('session_start ok id=' . session_id() . ' secure=' . ($isHttps ? '1' : '0') . ' incoming_cookie=' . $hasCookie);
    }
}

define('BASE_PATH', $basePath);
define('APP_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'app');
define('PUBLIC_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'public');
define('UPLOAD_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads');
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

$logDir = BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
define('APP_LOG_FILE', $logDir . DIRECTORY_SEPARATOR . 'app-debug.log');
if (is_dir($logDir) && is_writable($logDir)) {
    @ini_set('log_errors', '1');
    @ini_set('error_log', $logDir . DIRECTORY_SEPARATOR . 'php-error.log');
}

$adminEmailsRaw = getenv('ADMIN_EMAILS') ?: 'admin@duitkemana.com';
$adminEmails = array_values(array_filter(array_map('trim', explode(',', (string) $adminEmailsRaw))));
define('ADMIN_EMAILS', $adminEmails);
define('API_RATE_LIMIT_MAX', (int) (getenv('API_RATE_LIMIT_MAX') ?: 120));
define('API_RATE_LIMIT_WINDOW_SECONDS', (int) (getenv('API_RATE_LIMIT_WINDOW_SECONDS') ?: 60));

// Supported currencies with their IDR exchange rates (updated 2026-03)
// These are approximate rates; update via .env EXCHANGE_RATES_JSON for production.
$defaultRates = [
    'IDR' => 1,
    'USD' => 16250,
    'EUR' => 17600,
    'SGD' => 12100,
    'MYR' => 3450,
    'JPY' => 108,
    'AUD' => 10500,
    'GBP' => 20700,
    'CNY' => 2250,
    'SAR' => 4330,
];
$envRates = getenv('EXCHANGE_RATES_JSON');
if ($envRates) {
    $parsed = json_decode($envRates, true);
    if (is_array($parsed)) {
        $defaultRates = array_merge($defaultRates, $parsed);
    }
}
define('EXCHANGE_RATES_IDR', $defaultRates);

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

$composerAutoload = BASE_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}
