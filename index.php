<?php
$defaultPath = is_dir(__DIR__ . '/music-platform') ? '/music-platform/' : '/dashboard/';

if (isset($_SERVER['REQUEST_METHOD'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

    header('Location: ' . $scheme . '://' . $host . $defaultPath);
    exit;
}

echo "This script should be accessed through a web browser.\n";
echo 'Visit: http://localhost' . $defaultPath . "\n";
