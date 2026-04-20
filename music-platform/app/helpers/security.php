<?php
// app/helpers/security.php

require_once __DIR__ . '/auth.php';

/**
 * Generates a CSRF token and stores it in the session.
 */
function generateCsrfToken(): string {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies if the provided token matches the one in the session.
 */
function verifyCsrfToken(?string $token): bool {
    startSession();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Simple session-based rate limiting.
 */
function rateLimitCheck(string $key, int $limit, int $seconds): bool {
    startSession();
    $now = time();
    $data = $_SESSION['rate_limits'][$key] ?? ['count' => 0, 'start' => $now];

    if ($now - $data['start'] > $seconds) {
        $data = ['count' => 1, 'start' => $now];
    } else {
        $data['count']++;
    }

    $_SESSION['rate_limits'][$key] = $data;
    return $data['count'] <= $limit;
}

/**
 * Basic string sanitization.
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

require_once __DIR__ . '/safeFilename.php';

function isValidMimeType(string $filePath, array $allowedMimes): bool {
    $mime = mime_content_type($filePath);
    return $mime !== false && in_array($mime, $allowedMimes, true);
}
