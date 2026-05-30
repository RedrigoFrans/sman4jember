<?php
// File helper sementara untuk memeriksa log laravel di VPS
$logPath = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logPath)) {
    $lines = file($logPath);
    $lastLines = array_slice($lines, -200);
    echo "<h3>Laravel Server Logs (Last 200 lines):</h3>";
    echo "<pre style='background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars(implode("", $lastLines)) . "</pre>";
} else {
    echo "Log file not found at: " . realpath($logPath);
}
