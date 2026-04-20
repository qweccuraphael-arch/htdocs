<?php
// app/controllers/ArtistController.php

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__) . '/models/Artist.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';
require_once dirname(__DIR__) . '/helpers/security.php';

class ArtistController {
    private Artist $artistModel;

    public function __construct() {
        $this->artistModel = new Artist();
    }

    public function register(array $post, array $files): array {
        $name  = sanitize($post['name']  ?? '');
        $email = filter_var($post['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $phone = sanitize($post['phone'] ?? '');
        $pass  = $post['password'] ?? '';
        $bio   = sanitize($post['bio']   ?? '');

        if (!$name || !$email || strlen($pass) < 6) {
            return ['success' => false, 'error' => 'Name, valid email and password (6+ chars) required.'];
        }
        if ($this->artistModel->getByEmail($email)) {
            return ['success' => false, 'error' => 'Email already registered.'];
        }

        // Optional photo upload
        $photoName = null;
        if (!empty($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/png','image/webp'];
            if (isValidMimeType($files['photo']['tmp_name'], $allowed)) {
                $photoName = uniqid('artist_') . '_' . safeFilename($files['photo']['name']);
                $photoDir  = dirname(__DIR__, 2) . '/storage/photos/';
                @mkdir($photoDir, 0755, true);
                move_uploaded_file($files['photo']['tmp_name'], $photoDir . $photoName);
            }
        }

        $id = $this->artistModel->create([
            'name'     => $name,
            'email'    => $email,
            'password' => $pass,
            'phone'    => $phone,
            'bio'      => $bio,
            'photo'    => $photoName,
        ]);

        // Send welcome email + SMS
        sendWelcomeEmail($email, $name);
        if ($phone) smsWelcome($phone, $name);

        // Notify admin
        $adminEmail = 'admin@yourdomain.com';
        sendEmail($adminEmail, "New Artist Registration: {$name}",
            "<p>A new artist <strong>{$name}</strong> ({$email}) just registered on BeatWave.</p>"
        );

        return ['success' => true, 'artist_id' => $id];
    }

    public function login(string $email, string $password): array {
        $artist = $this->artistModel->verifyLogin($email, $password);
        if (!$artist) {
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }
        if ($artist['status'] !== 'active') {
            return ['success' => false, 'error' => 'Your account is suspended.'];
        }
        artistLogin($artist['id'], $artist['name']);
        return ['success' => true, 'artist' => $artist];
    }
}
