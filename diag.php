<?php

$basePath = __DIR__;
$configPath = $basePath . '/config/database.php';
$config = file_exists($configPath) ? require $configPath : null;

header('Content-Type: text/plain; charset=UTF-8');

echo "=== APP DIAGNOSTIC ===\n";
echo 'time=' . date('c') . "\n";
echo 'host=' . ($_SERVER['HTTP_HOST'] ?? '-') . "\n";
echo 'request_uri=' . ($_SERVER['REQUEST_URI'] ?? '-') . "\n";
echo 'document_root=' . ($_SERVER['DOCUMENT_ROOT'] ?? '-') . "\n";
echo 'script_filename=' . ($_SERVER['SCRIPT_FILENAME'] ?? '-') . "\n";
echo 'app_base=' . $basePath . "\n";
echo 'config_exists=' . (file_exists($configPath) ? 'yes' : 'no') . "\n";

if (!is_array($config)) {
    echo "db_config_status=invalid\n";
    exit;
}

echo "\n=== DB CONFIG ===\n";
echo 'db_host=' . (string) ($config['host'] ?? '-') . "\n";
echo 'db_port=' . (string) ($config['port'] ?? '-') . "\n";
echo 'db_name=' . (string) ($config['dbname'] ?? '-') . "\n";
echo 'db_user=' . (string) ($config['username'] ?? '-') . "\n";
echo 'db_password=' . (!empty($config['password']) ? 'SET' : 'EMPTY') . "\n";
echo 'db_charset=' . (string) ($config['charset'] ?? '-') . "\n";

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    (string) ($config['host'] ?? ''),
    (string) ($config['port'] ?? '3306'),
    (string) ($config['dbname'] ?? ''),
    (string) ($config['charset'] ?? 'utf8mb4')
);

echo "\n=== DB CONNECTION TEST ===\n";
try {
    $pdo = new PDO($dsn, (string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "pdo_connect=OK\n";

    $stmt = $pdo->query('SELECT DATABASE() AS db_name, CURRENT_USER() AS current_user');
    $row = $stmt->fetch();
    echo 'current_database=' . (string) ($row['db_name'] ?? '-') . "\n";
    echo 'current_user=' . (string) ($row['current_user'] ?? '-') . "\n";

    $tables = ['users', 'transactions', 'categories', 'income_records', 'recurring_bills'];
    foreach ($tables as $table) {
        try {
            $count = (int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
            echo 'table_' . $table . '=OK rows:' . $count . "\n";
        } catch (Throwable $e) {
            echo 'table_' . $table . '=ERROR ' . $e->getMessage() . "\n";
        }
    }
} catch (Throwable $e) {
    echo 'pdo_connect=ERROR ' . $e->getMessage() . "\n";
}

echo "\nNOTE: Delete this file after debugging.\n";