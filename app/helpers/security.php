<?php
// app/helpers/security.php

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateToken(): string {
    return bin2hex(random_bytes(32));
}

function verifyToken(string $token, string $sessionKey = 'csrf_token'): bool {
    return isset($_SESSION[$sessionKey]) && hash_equals($_SESSION[$sessionKey], $token);
}

function setCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function checkCSRF() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyToken($token)) {
        http_response_code(403);
        die('CSRF validation failed.');
    }
}

function isValidMimeType(string $filePath, array $allowed): bool {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($filePath);
    return in_array($mime, $allowed, true);
}

function safeFilename(string $name): string {
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    return strtolower(substr($name, 0, 200));
}

function rateLimitCheck(string $key, int $maxAttempts = 5, int $window = 300): bool {
    $cacheFile = LOG_PATH . 'ratelimit_' . md5($key) . '.json';
    $now       = time();
    $data      = [];

    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true) ?? [];
    }

    // Remove old entries
    $data = array_filter($data, fn($t) => ($now - $t) < $window);
    $data[] = $now;

    file_put_contents($cacheFile, json_encode(array_values($data)));

    return count($data) <= $maxAttempts;
}
