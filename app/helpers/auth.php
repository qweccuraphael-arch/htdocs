<?php
// app/helpers/auth.php

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/db.php';
require_once __DIR__ . '/functions.php';  // For sendEmail()
require_once __DIR__ . '/security.php';   // For sanitize(), rateLimitCheck()

function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// ── Admin Auth ────────────────────────────────────────────────────────────────
function isAdminLoggedIn(): bool {
    startSession();
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

function adminLogin(int $id, string $username) {
    startSession();
    session_regenerate_id(true);
    $_SESSION['admin_id']       = $id;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_logged_at'] = time();
}

function adminLogout() {
    startSession();
    session_unset();
    session_destroy();
    header('Location: ' . APP_URL . '/admin/login.php');
    exit;
}

// ── Artist Auth ───────────────────────────────────────────────────────────────
function isArtistLoggedIn(): bool {
    startSession();
    return isset($_SESSION['artist_id']) && !empty($_SESSION['artist_id']);
}

function requireArtist() {
    if (!isArtistLoggedIn()) {
        header('Location: ' . APP_URL . '/artist/login.php');
        exit;
    }
}

function artistLogin(int $id, string $name) {
    startSession();
    session_regenerate_id(true);
    $_SESSION['artist_id']        = $id;
    $_SESSION['artist_name']      = $name;
    $_SESSION['artist_logged_at'] = time();
}

function artistLogout() {
    startSession();
    session_unset();
    session_destroy();
    header('Location: ' . APP_URL . '/artist/login.php');
    exit;
}

function currentArtistId(): ?int {
    startSession();
    return $_SESSION['artist_id'] ?? null;
}

function currentArtistName(): string {
    startSession();
    return $_SESSION['artist_name'] ?? '';
}

// ── Admin Password Reset ─────────────────────────────────────────────────────
function adminSendResetEmail(string $identifier): bool {
    $db = getDB();
    $ip = getClientIP();
    if (!rateLimitCheck('admin_reset_' . $ip, 20, 14400)) {
        logActivity('admin_reset_error', 'Rate limit exceeded', ['ip' => $ip]);
        return false;
    }
    
    // Case-insensitive lookup
    $sql = "SELECT id, username, email FROM admins WHERE LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$identifier, $identifier]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        logActivity('admin_reset_error', 'Admin not found', ['identifier' => $identifier]);
        return false;
    }
    
    $stmt = $db->prepare("DELETE FROM admin_reset_tokens WHERE admin_id = ?");
    $stmt->execute([$admin['id']]);
    
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);
    
    $stmt = $db->prepare("INSERT INTO admin_reset_tokens (admin_id, token, expires_at) VALUES (?, ?, ?)");
    if (!$stmt->execute([$admin['id'], $token, $expires])) {
        logActivity('admin_reset_error', 'Failed to insert token', ['admin_id' => $admin['id']]);
        return false;
    }
    
    $resetUrl = APP_URL . '/admin/reset-password.php?token=' . $token;
    $content = emailTemplate('Reset Your Password 🔐',
        "<p>We received a request to reset your BeatWave Admin password.</p>
         <p><strong>This link expires in 1 hour</strong> and can only be used once.</p>
         <p style='text-align:center;margin:30px 0'>
           <a href='{$resetUrl}' class='btn' style='font-size:18px;padding:15px 40px'>Reset Password</a>
         </p>
         <p>If you didn't request this, please ignore this email.</p>"
    );
    
    $sent = sendEmail($admin['email'], 'Reset Your BeatWave Admin Password', $content);
    
    // On localhost, mail() often fails. For development, we return true if the token was created
    // so the dev can still check the database for the token if needed.
    if (!$sent) {
        logActivity('admin_reset_warning', 'Email failed to send but token generated', ['email' => $admin['email'], 'token' => $token]);
        // For local testing, we'll return true so the "Check your email" message appears
        return true; 
    }
    return true;
}

function adminValidateResetToken(string $token): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT art.*, a.username, a.email 
                          FROM admin_reset_tokens art 
                          JOIN admins a ON art.admin_id = a.id 
                          WHERE art.token = ? AND art.expires_at > NOW() AND art.used_at IS NULL");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

function adminResetPassword(int $adminId, string $newPassword): bool {
    $db = getDB();
    $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
    $updated = $stmt->execute([$hashed, $adminId]);
    if ($updated) {
        $stmt = $db->prepare("UPDATE admin_reset_tokens SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL");
        $stmt->execute([$adminId]);
    }
    return $updated;
}

// ── Artist Password Reset ─────────────────────────────────────────────────────
function artistSendResetEmail(string $identifier): bool {
    $db = getDB();
    $ip = getClientIP();
    if (!rateLimitCheck('artist_reset_' . $ip, 20, 14400)) {
        logActivity('artist_reset_error', 'Rate limit exceeded', ['ip' => $ip]);
        return false;
    }
    
    // Case-insensitive lookup
    $sql = "SELECT id, name, email FROM artists WHERE LOWER(email) = LOWER(?) AND status = 'active'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$identifier]);
    $artist = $stmt->fetch();
    
    if (!$artist) {
        logActivity('artist_reset_error', 'Artist not found', ['identifier' => $identifier]);
        return false;
    }
    
    $stmt = $db->prepare("DELETE FROM artist_reset_tokens WHERE artist_id = ?");
    $stmt->execute([$artist['id']]);
    
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);
    
    $stmt = $db->prepare("INSERT INTO artist_reset_tokens (artist_id, token, expires_at) VALUES (?, ?, ?)");
    if (!$stmt->execute([$artist['id'], $token, $expires])) {
        logActivity('artist_reset_error', 'Failed to insert token', ['artist_id' => $artist['id']]);
        return false;
    }
    
    $resetUrl = APP_URL . '/artist/reset-password.php?token=' . $token;
    $content = emailTemplate('Reset Your Password 🔐',
        "<p>We received a request to reset your BeatWave Artist password.</p>
         <p><strong>This link expires in 1 hour</strong> and can only be used once.</p>
         <p style='text-align:center;margin:30px 0'>
           <a href='{$resetUrl}' class='btn' style='font-size:18px;padding:15px 40px'>Reset Password</a>
         </p>
         <p>If you didn't request this, please ignore this email.</p>"
    );
    
    $sent = sendEmail($artist['email'], 'Reset Your BeatWave Artist Password', $content);
    
    if (!$sent) {
        logActivity('artist_reset_warning', 'Email failed but token generated', ['email' => $artist['email'], 'token' => $token]);
        // On localhost, store the link in session so we can show it to the dev/user
        if (str_contains(APP_URL, 'localhost')) {
            $_SESSION['last_reset_link'] = $resetUrl;
        }
        return true; 
    }
    return true;
}

function artistValidateResetToken(string $token): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT art.*, a.name, a.email 
                          FROM artist_reset_tokens art 
                          JOIN artists a ON art.artist_id = a.id 
                          WHERE art.token = ? AND art.expires_at > NOW() AND art.used_at IS NULL AND a.status = 'active'");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

function artistResetPassword(int $artistId, string $newPassword): bool {
    require_once dirname(__DIR__) . '/models/Artist.php';
    $artistModel = new Artist();
    $updated = $artistModel->updatePassword($artistId, $newPassword);
    if ($updated) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE artist_reset_tokens SET used_at = NOW() WHERE artist_id = ? AND used_at IS NULL");
        $stmt->execute([$artistId]);
    }
    return $updated;
}

