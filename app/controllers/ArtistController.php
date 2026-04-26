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

        $otp = (string)rand(100000, 999999);

        $id = $this->artistModel->create([
            'name'        => $name,
            'email'       => $email,
            'password'    => $pass,
            'otp_code'    => $otp,
            'is_verified' => 0,
            'phone'       => $phone,
            'bio'         => $bio,
            'photo'       => $photoName,
        ]);

        // Send OTP via Email
        sendOTPEmail($email, $name, $otp);
        
        if ($phone) {
            smsWelcome($phone, $name);
            sendSMS($phone, "Hi {$name}, your BeatWave OTP is {$otp}. Verify your account to start uploading!");
        }

        // Notify admin
        $adminEmail = ADMIN_ALERT_EMAIL;
        sendEmail($adminEmail, "New Artist Registration: {$name}",
            "<p>A new artist <strong>{$name}</strong> ({$email}) just registered on BeatWave.</p>"
        );

        return ['success' => true, 'artist_id' => $id, 'email' => $email];
    }

    public function verifyOTP(string $email, string $otp): array {
        $artist = $this->artistModel->getByEmail($email);
        if (!$artist) return ['success' => false, 'error' => 'Account not found.'];
        if ($artist['is_verified']) return ['success' => true, 'message' => 'Already verified.'];
        
        if ($artist['otp_code'] === $otp) {
            $this->artistModel->verifyEmail($artist['id']);
            return ['success' => true, 'message' => 'Email verified successfully!'];
        }
        
        return ['success' => false, 'error' => 'Invalid OTP code.'];
    }

    public function resendOTP(string $email): array {
        $artist = $this->artistModel->getByEmail($email);
        if (!$artist) return ['success' => false, 'error' => 'Account not found.'];
        if ($artist['is_verified']) return ['success' => false, 'error' => 'Already verified.'];

        $otp = (string)rand(100000, 999999);
        $this->artistModel->updateOTP($artist['id'], $otp);
        sendOTPEmail($email, $artist['name'], $otp);
        
        if (!empty($artist['phone'])) {
            sendSMS($artist['phone'], "Your new BeatWave OTP is {$otp}.");
        }

        return ['success' => true, 'message' => 'OTP resent to your email/phone.'];
    }

    public function login(string $email, string $password): array {
        $artist = $this->artistModel->verifyLogin($email, $password);
        if (!$artist) {
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }
        if (!$artist['is_verified']) {
            return ['success' => false, 'needs_verification' => true, 'email' => $email];
        }
        if ($artist['status'] !== 'active') {
            return ['success' => false, 'error' => 'Your account is suspended.'];
        }
        artistLogin($artist['id'], $artist['name']);
        return ['success' => true, 'artist' => $artist];
    }

    public function loginWithGoogle(string $idToken): array {
        // Simple verification via Google API
        $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $idToken;
        $resp = @file_get_contents($url);
        if (!$resp) return ['success' => false, 'error' => 'Invalid Google Token.'];
        
        $payload = json_decode($resp, true);
        if (!isset($payload['sub'])) return ['success' => false, 'error' => 'Invalid Google Payload.'];

        $googleId = $payload['sub'];
        $email = $payload['email'];
        $name = $payload['name'];
        $photo = $payload['picture'] ?? null;

        $artist = $this->artistModel->getByGoogleId($googleId);
        if (!$artist) {
            // Check if email already exists but no Google ID linked
            $artist = $this->artistModel->getByEmail($email);
            if ($artist) {
                // Link Google ID to existing account
                $this->artistModel->update($artist['id'], ['google_id' => $googleId, 'is_verified' => 1]);
            } else {
                // Create new account
                $id = $this->artistModel->create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'password' => bin2hex(random_bytes(10)), // random pass
                    'is_verified' => 1,
                    'status' => 'active',
                    'bio' => 'Signed up via Google.',
                    'phone' => null,
                    'photo' => null // could download from $photo url later
                ]);
                $artist = $this->artistModel->getById($id);
                sendWelcomeEmail($email, $name);
            }
        }

        if ($artist['status'] !== 'active') {
            return ['success' => false, 'error' => 'Your account is suspended.'];
        }

        artistLogin($artist['id'], $artist['name']);
        return ['success' => true, 'artist' => $artist];
    }

    public function submitKYC(int $artistId, array $post, array $files): array {
        $tin = sanitize($post['tin'] ?? '');
        if (!$tin) return ['success' => false, 'error' => 'TIN is required.'];

        // Handle File Uploads
        $kycDir = dirname(__DIR__, 2) . '/storage/kyc/';
        @mkdir($kycDir, 0755, true);

        $form3Path = null;
        if (!empty($files['form_3']) && $files['form_3']['error'] === UPLOAD_ERR_OK) {
            $form3Path = 'form3_' . $artistId . '_' . time() . '.' . pathinfo($files['form_3']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($files['form_3']['tmp_name'], $kycDir . $form3Path);
        }

        $certPath = null;
        if (!empty($files['incorporation_cert']) && $files['incorporation_cert']['error'] === UPLOAD_ERR_OK) {
            $certPath = 'cert_' . $artistId . '_' . time() . '.' . pathinfo($files['incorporation_cert']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($files['incorporation_cert']['tmp_name'], $kycDir . $certPath);
        }

        if (!$form3Path || !$certPath) {
            return ['success' => false, 'error' => 'Both Form 3 and Certificate of Incorporation are required.'];
        }

        // Process Directors
        $directors = [];
        if (isset($post['director_name'])) {
            foreach ($post['director_name'] as $i => $name) {
                if (trim($name)) {
                    $directors[] = [
                        'name' => sanitize($name),
                        'role' => sanitize($post['director_role'][$i] ?? 'Director')
                    ];
                }
            }
        }
        if (count($directors) < 2) {
            return ['success' => false, 'error' => 'At least 2 directors are required.'];
        }

        // Process Beneficial Owners
        $owners = [];
        $totalPercent = 0;
        if (isset($post['owner_name'])) {
            foreach ($post['owner_name'] as $i => $name) {
                if (trim($name)) {
                    $percent = (int)($post['owner_percent'][$i] ?? 0);
                    $owners[] = ['name' => sanitize($name), 'percent' => $percent];
                    $totalPercent += $percent;
                }
            }
        }
        if ($totalPercent < 51) {
            return ['success' => false, 'error' => 'Identified owners must hold at least 51% ownership in total.'];
        }

        // Update Artist Record
        $this->artistModel->update($artistId, [
            'tin' => $tin,
            'form_3' => $form3Path,
            'incorporation_cert' => $certPath,
            'directors' => json_encode($directors),
            'beneficial_owners' => json_encode($owners),
            'kyc_status' => 'pending'
        ]);

        // Notify Admin
        $artist = $this->artistModel->getById($artistId);
        sendEmail(ADMIN_ALERT_EMAIL, "New KYC Submission: " . $artist['name'], 
            "<p>Artist <strong>" . $artist['name'] . "</strong> has submitted their business verification documents.</p><p>Please review them in the admin panel.</p>"
        );

        return ['success' => true, 'message' => 'Verification documents submitted successfully! We will review them shortly.'];
    }
}
