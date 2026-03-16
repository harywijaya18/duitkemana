<?php
// Simple debug log viewer - delete after fixing
$logFile = __DIR__ . '/../storage/logs/app-debug.log';
if (!file_exists($logFile)) {
    die('Log file not found: ' . $logFile);
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES);
$lines = array_slice($lines, -100); // Last 100 lines
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Log</title>
    <style>
        body { font-family: monospace; background: #111; color: #0f0; padding: 20px; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
        .error { color: #f00; }
        .success { color: #0f0; }
    </style>
</head>
<body>
<h2>Last 100 log lines:</h2>
<pre>
<?php
foreach ($lines as $line) {
    if (stripos($line, 'error') !== false) {
        echo '<span class="error">' . htmlspecialchars($line) . '</span>' . "\n";
    } elseif (stripos($line, 'success') !== false || stripos($line, 'ok') !== false) {
        echo '<span class="success">' . htmlspecialchars($line) . '</span>' . "\n";
    } else {
        echo htmlspecialchars($line) . "\n";
    }
}
?>
</pre>
<p><a href="?clear=1">Clear log</a></p>
</body>
</html>
<?php
if ($_GET['clear'] ?? false) {
    file_put_contents($logFile, '');
    header('Refresh: 1');
}
