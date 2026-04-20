<?php
// app/helpers/auth.php

require_once dirname(__DIR__, 2) . '/config/app.php';

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
