<?php
// app/helpers/auth.php

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/db.php';
require_once __DIR__ . '/functions.php';  // For sendEmail()
require_once __DIR__ . '/security.php';   // For sanitize(), rateLimitCheck(), getClientIP()

function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
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

// ── Admin Password Reset ─────────────────────────────────────────────────────
/**
 * Send password reset email to admin (by email or username).
 */
function adminSendResetEmail(string $identifier): bool {
    $db = getDB();
    
    // Rate limit: 3 attempts/hour per IP
    $ip = getClientIP();
    if (!rateLimitCheck('admin_reset_' . $ip, 3, 3600)) {
        logActivity('reset_rate_limit', 'Too many reset attempts', ['ip' => $ip]);
        return false;
    }
    
    // Find admin by email OR username
    $sql = "SELECT id, username, email FROM admins WHERE email = ? OR username = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([sanitize($identifier), sanitize($identifier)]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        logActivity('reset_not_found', 'Admin not found for reset', ['identifier' => $identifier]);
        return false;  // Don't reveal if exists
    }
    
    // Delete old tokens
    $stmt = $db->prepare("DELETE FROM admin_reset_tokens WHERE admin_id = ?");
    $stmt->execute([$admin['id']]);
    
    // Create new token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);  // 1 hour
    
    $stmt = $db->prepare("INSERT INTO admin_reset_tokens (admin_id, token, expires_at) VALUES (?, ?, ?)");
    $success = $stmt->execute([$admin['id'], $token, $expires]);
    
    if (!$success) return false;
    
    // Send email
    $resetUrl = APP_URL . '/admin/reset-password.php?token=' . $token;
    $content = emailTemplate(
        'Reset Your Password 🔐',
        "<p>We received a request to reset your BeatWave Admin password.</p>
         <p><strong>This link expires in 1 hour</strong> and can only be used once.</p>
         <p style='text-align:center;margin:30px 0'>
           <a href='{$resetUrl}' class='btn' style='font-size:18px;padding:15px 40px'>Reset Password</a>
         </p>
         <p>If you didn't request this, please ignore this email.</p>
         <p><strong>Security tip:</strong> Use a strong password (12+ chars, numbers/symbols).</p>"
    );
    
    return sendEmail($admin['email'], 'Reset Your BeatWave Admin Password', $content);
}

/**
 * Validate reset token.
 */
function adminValidateResetToken(string $token): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT art.*, a.username, a.email 
                          FROM admin_reset_tokens art 
                          JOIN admins a ON art.admin_id = a.id 
                          WHERE art.token = ? AND art.expires_at > NOW() AND art.used_at IS NULL");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

/**
 * Complete password reset and mark token used.
 */
function adminResetPassword(int $adminId, string $newPassword): bool {
    $db = getDB();
    
    // Update password
    $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
    $updated = $stmt->execute([$hashed, $adminId]);
    
    if (!$updated) return false;
    
    // Mark token used
    $stmt = $db->prepare("UPDATE admin_reset_tokens SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL");
    $stmt->execute([$adminId]);
    
    logActivity('password_reset', 'Admin password reset successful', ['admin_id' => $adminId]);
    return true;
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

// ── Artist Password Reset ────────────────────────────────────────────────────
/**
 * Send password reset email to artist (by email).
 */
function artistSendResetEmail(string $email): bool {
    $db = getDB();
    
    // Rate limit: 3 attempts/hour per IP
    $ip = getClientIP();
    if (!rateLimitCheck('artist_reset_' . $ip, 3, 3600)) {
        logActivity('artist_reset_rate_limit', 'Too many reset attempts', ['ip' => $ip]);
        return false;
    }
    
    $artist = (new Artist())->getByEmail($email);
    if (!$artist) {
        logActivity('artist_reset_not_found', 'Artist not found for reset', ['email' => $email]);
        return false;  // Don't reveal if exists
    }
    
    // Delete old tokens
    $stmt = $db->prepare("DELETE FROM artist_reset_tokens WHERE artist_id = ?");
    $stmt->execute([$artist['id']]);
    
    // Create new token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);  // 1 hour
    
    $stmt = $db->prepare("INSERT INTO artist_reset_tokens (artist_id, token, expires_at) VALUES (?, ?, ?)");
    $success = $stmt->execute([$artist['id'], $token, $expires]);
    
    if (!$success) return false;
    
    // Send email
    $resetUrl = APP_URL . '/artist/reset-password.php?token=' . $token;
    $content = emailTemplate(
        'Reset Your Artist Password 🎤',
        "<p>Hi <strong>{$artist['name']}</strong>,</p>
         <p>We received a request to reset your BeatWave Artist password.</p>
         <p><strong>This link expires in 1 hour</strong> and can only be used once.</p>
         <p style='text-align:center;margin:30px 0'>
           <a href='{$resetUrl}' class='btn' style='font-size:18px;padding:15px 40px'>Reset Password</a>
         </p>
         <p>If you didn't request this, please ignore this email.</p>"
    );
    
    return sendEmail($artist['email'], 'Reset Your BeatWave Artist Password', $content);
}

/**
 * Validate artist reset token.
 */
function artistValidateResetToken(string $token): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT art.*, a.name, a.email 
                          FROM artist_reset_tokens art 
                          JOIN artists a ON art.artist_id = a.id 
                          WHERE art.token = ? AND art.expires_at > NOW() AND art.used_at IS NULL");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

/**
 * Complete artist password reset and mark token used.
 */
function artistResetPassword(int $artistId, string $newPassword): bool {
    $db = getDB();
    
    // Update password
    $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE artists SET password = ? WHERE id = ?");
    $updated = $stmt->execute([$hashed, $artistId]);
    
    if (!$updated) return false;
    
    // Mark token used
    $stmt = $db->prepare("UPDATE artist_reset_tokens SET used_at = NOW() WHERE artist_id = ? AND used_at IS NULL");
    $stmt->execute([$artistId]);
    
    logActivity('artist_password_reset', 'Artist password reset successful', ['artist_id' => $artistId]);
    return true;
}
?>
