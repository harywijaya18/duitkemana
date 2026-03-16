<?php

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_log(string $message): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    if (defined('APP_LOG_FILE')) {
        @file_put_contents(APP_LOG_FILE, $line, FILE_APPEND);
    }
    error_log($message);
}

function redirect(string $path): void
{
    session_write_close();
    header('Location: ' . base_url($path));
    exit;
}

function base_url(string $path = ''): string
{
    $normalized = ltrim($path, '/');
    return BASE_URL . ($normalized ? '/' . $normalized : '');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function require_auth(): void
{
    if (empty($_SESSION['user'])) {
        redirect('/login');
    }
}

function auth_cookie_payload(): ?array
{
    $raw = $_COOKIE[AUTH_COOKIE_NAME] ?? '';
    if (!is_string($raw) || $raw === '') {
        return null;
    }

    $decoded = base64_decode(strtr($raw, '-_', '+/'), true);
    if (!is_string($decoded) || $decoded === '') {
        return null;
    }

    $payload = json_decode($decoded, true);
    if (!is_array($payload) || empty($payload['data']) || empty($payload['sig'])) {
        return null;
    }

    $data = $payload['data'];
    $sig = (string) $payload['sig'];
    $jsonData = json_encode($data);
    if (!is_string($jsonData)) {
        return null;
    }

    $expected = hash_hmac('sha256', $jsonData, AUTH_COOKIE_SECRET);
    if (!hash_equals($expected, $sig)) {
        return null;
    }

    if (!isset($data['id'], $data['name'], $data['email'], $data['currency'])) {
        return null;
    }

    return [
        'id' => (int) $data['id'],
        'name' => (string) $data['name'],
        'email' => (string) $data['email'],
        'currency' => (string) $data['currency'],
    ];
}

function persist_auth_cookie(array $user): void
{
    $data = [
        'id' => (int) ($user['id'] ?? 0),
        'name' => (string) ($user['name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'currency' => (string) ($user['currency'] ?? 'IDR'),
    ];

    $jsonData = json_encode($data);
    if (!is_string($jsonData)) {
        return;
    }

    $payload = [
        'data' => $data,
        'sig' => hash_hmac('sha256', $jsonData, AUTH_COOKIE_SECRET),
    ];

    $encoded = base64_encode((string) json_encode($payload));
    $cookieValue = strtr($encoded, '+/', '-_');

    $httpsServerFlag = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    $requestScheme = strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? ''));
    $serverPort = (string) ($_SERVER['SERVER_PORT'] ?? '');
    $isHttps = ($httpsServerFlag === 'on' || $httpsServerFlag === '1' || $requestScheme === 'https' || $serverPort === '443');

    setcookie(AUTH_COOKIE_NAME, $cookieValue, [
        'expires' => time() + 86400 * 7,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function clear_auth_cookie(): void
{
    $httpsServerFlag = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    $requestScheme = strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? ''));
    $serverPort = (string) ($_SERVER['SERVER_PORT'] ?? '');
    $isHttps = ($httpsServerFlag === 'on' || $httpsServerFlag === '1' || $requestScheme === 'https' || $serverPort === '443');

    setcookie(AUTH_COOKIE_NAME, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function auth_user(): ?array
{
    if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    $cookieUser = auth_cookie_payload();
    if ($cookieUser !== null) {
        $_SESSION['user'] = $cookieUser;
        return $cookieUser;
    }

    return null;
}

function admin_emails(): array
{
    $emails = defined('ADMIN_EMAILS') ? ADMIN_EMAILS : [];
    return array_values(array_filter(array_map(static function ($email): string {
        return strtolower(trim((string) $email));
    }, $emails)));
}

function is_admin_user(?array $user = null): bool
{
    $candidate = $user ?? auth_user();
    $email = strtolower(trim((string) ($candidate['email'] ?? '')));
    if ($email === '') {
        return false;
    }

    return in_array($email, admin_emails(), true);
}

function require_admin(): void
{
    require_auth();
    if (!is_admin_user()) {
        flash('error', 'Admin access required.');
        redirect('/');
    }
}

function currency_format(float $amount, ?string $currency = null): string
{
    $userCurrency = $currency ?? ($_SESSION['user']['currency'] ?? 'IDR');
    $rates        = defined('EXCHANGE_RATES_IDR') ? EXCHANGE_RATES_IDR : ['IDR' => 1];
    $rate         = (float) ($rates[$userCurrency] ?? 1);

    if ($userCurrency !== 'IDR' && $rate > 1) {
        $converted    = $amount / $rate;
        $decimals     = $converted < 100 ? 2 : 0;
        return $userCurrency . ' ' . number_format($converted, $decimals, '.', ',');
    }

    return $userCurrency . ' ' . number_format($amount, 0, ',', '.');
}

function old(string $key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

function validate_amount($amount): bool
{
    return is_numeric($amount) && (float) $amount > 0;
}

function save_old_input(array $input): void
{
    $_SESSION['old'] = $input;
}

function clear_old_input(): void
{
    unset($_SESSION['old']);
}

function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

    if ($basePath !== '' && $basePath !== '/') {
        $path = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $path);
    }

    return $path ?: '/';
}

function is_active_path(string $path): bool
{
    return current_path() === $path;
}

function receipt_url(?string $filename): ?string
{
    if (!$filename) {
        return null;
    }

    $base = rtrim(BASE_URL, '/');
    if ($base !== '' && str_ends_with($base, '/public')) {
        $base = substr($base, 0, -7);
    }

    return $base . '/storage/uploads/' . rawurlencode($filename);
}

function supported_languages(): array
{
    return ['id', 'en'];
}

function current_language(): string
{
    static $resolved = null;

    if ($resolved !== null) {
        return $resolved;
    }

    $lang = $_SESSION['lang'] ?? null;
    if (!is_string($lang) || !in_array($lang, supported_languages(), true)) {
        $header = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        $lang = str_starts_with($header, 'id') ? 'id' : 'en';
        $_SESSION['lang'] = $lang;
    }

    $resolved = $lang;
    return $resolved;
}

function set_language(string $lang): void
{
    if (!in_array($lang, supported_languages(), true)) {
        return;
    }

    $_SESSION['lang'] = $lang;
}

function language_options(): array
{
    return [
        'id' => 'Bahasa Indonesia',
        'en' => 'English',
    ];
}

function t(string $key, array $replace = []): string
{
    static $cache = [];

    $lang = current_language();
    if (!isset($cache[$lang])) {
        $langFile = APP_PATH . '/lang/' . $lang . '.php';
        $fallbackFile = APP_PATH . '/lang/en.php';
        $fallback = file_exists($fallbackFile) ? require $fallbackFile : [];
        $translations = file_exists($langFile) ? require $langFile : [];
        $cache[$lang] = array_merge($fallback, $translations);
    }

    $text = $cache[$lang][$key] ?? $key;
    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string) $value, $text);
    }

    return $text;
}

/**
 * Count working days within the salary period for a given month/year.
 *
 * cutoff_day = 0  → full month (1st to last day)
 * cutoff_day = N  → (N+1) of prev month  to  N of this month
 *
 * Returns ['working_days' => int, 'period_start' => 'Y-m-d', 'period_end' => 'Y-m-d']
 */
function count_working_days(int $year, int $month, int $cutoffDay = 0, int $daysPerWeek = 5): array
{
    if ($cutoffDay <= 0) {
        $firstOfMonth  = new DateTime(sprintf('%04d-%02d-01', $year, $month));
        $daysInMonth   = (int) $firstOfMonth->format('t');
        $startDate     = $firstOfMonth;
        $endDate       = new DateTime(sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth));
    } else {
        $prevYear       = $month === 1 ? $year - 1 : $year;
        $prevMonth      = $month === 1 ? 12 : $month - 1;
        $prevDays       = (int) (new DateTime(sprintf('%04d-%02d-01', $prevYear, $prevMonth)))->format('t');
        $startDay       = min($cutoffDay + 1, $prevDays);
        $startDate      = new DateTime(sprintf('%04d-%02d-%02d', $prevYear, $prevMonth, $startDay));

        $currDays       = (int) (new DateTime(sprintf('%04d-%02d-01', $year, $month)))->format('t');
        $endDay         = min($cutoffDay, $currDays);
        $endDate        = new DateTime(sprintf('%04d-%02d-%02d', $year, $month, $endDay));
    }

    $count   = 0;
    $current = clone $startDate;
    while ($current <= $endDate) {
        $dow = (int) $current->format('N'); // 1=Mon … 7=Sun
        if ($daysPerWeek >= 6 && $dow <= 6) {
            $count++;
        } elseif ($daysPerWeek === 5 && $dow <= 5) {
            $count++;
        }
        $current->modify('+1 day');
    }

    return [
        'working_days' => $count,
        'period_start' => $startDate->format('Y-m-d'),
        'period_end'   => $endDate->format('Y-m-d'),
    ];
}
