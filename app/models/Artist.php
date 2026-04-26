<?php
// app/models/Artist.php

require_once dirname(__DIR__, 2) . '/config/db.php';

class Artist {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM artists WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM artists WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function getByGoogleId(string $googleId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM artists WHERE google_id = ?");
        $stmt->execute([$googleId]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(): array {
        return $this->db->query("SELECT * FROM artists ORDER BY created_at DESC")->fetchAll();
    }

    public function create(array $data): int {
        $data['password']   = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['is_verified'] = $data['is_verified'] ?? 0;
        $data['otp_code']    = $data['otp_code'] ?? null;
        $data['google_id']   = $data['google_id'] ?? null;

        $stmt = $this->db->prepare(
            "INSERT INTO artists (name, email, google_id, password, otp_code, is_verified, phone, bio, photo, status, created_at)
             VALUES (:name, :email, :google_id, :password, :otp_code, :is_verified, :phone, :bio, :photo, 'active', :created_at)"
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function verifyEmail(int $id): bool {
        $stmt = $this->db->prepare("UPDATE artists SET is_verified = 1, otp_code = NULL WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateOTP(int $id, string $otp): bool {
        $stmt = $this->db->prepare("UPDATE artists SET otp_code = ? WHERE id = ?");
        return $stmt->execute([$otp, $id]);
    }

    public function verifyLogin(string $email, string $password): ?array {
        $artist = $this->getByEmail($email);
        if ($artist && password_verify($password, $artist['password'])) {
            return $artist;
        }
        return null;
    }

    public function update(int $id, array $data): bool {
        $sets   = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($data)));
        $data['id'] = $id; // Changed from :id to id for consistency if needed, but keeping PDO style
        $stmt = $this->db->prepare("UPDATE artists SET {$sets} WHERE id = :id");
        return $stmt->execute($data);
    }

    public function updateBankDetails(int $id, array $details): bool {
        $stmt = $this->db->prepare(
            "UPDATE artists SET 
                bank_name = :bank_name, 
                bank_code = :bank_code, 
                account_number = :account_number, 
                account_name = :account_name,
                paystack_recipient_code = :paystack_recipient_code
             WHERE id = :id"
        );
        $details['id'] = $id;
        return $stmt->execute($details);
    }

    public function updatePassword(int $id, string $newPassword): bool {
        $stmt = $this->db->prepare("UPDATE artists SET password = ? WHERE id = ?");
        return $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $id]);
    }

    public function getStats(int $artistId): array {
        $row = $this->db->prepare(
            "SELECT 
                COUNT(s.id) AS total_songs,
                SUM(s.download_count) AS total_downloads,
                COALESCE(SUM(e.amount), 0) AS total_earnings
             FROM songs s
             LEFT JOIN earnings e ON e.artist_id = s.artist_id
             WHERE s.artist_id = ?"
        );
        $row->execute([$artistId]);
        return $row->fetch();
    }
}
