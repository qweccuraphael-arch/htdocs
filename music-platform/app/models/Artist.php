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

    public function getAll(): array {
        return $this->db->query("SELECT * FROM artists ORDER BY created_at DESC")->fetchAll();
    }

    public function create(array $data): int {
        $data['password']   = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['created_at'] = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO artists (name, email, password, phone, bio, photo, status, created_at)
             VALUES (:name, :email, :password, :phone, :bio, :photo, 'active', :created_at)"
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function verifyLogin(string $email, string $password): ?array {
        $artist = $this->getByEmail($email);
        if ($artist && password_verify($password, $artist['password'])) {
            return $artist;
        }
        return null;
    }

    public function update(int $id, array $data): bool {
        $allowed = ['name', 'email', 'phone', 'bio', 'photo', 'status'];
        $filteredData = array_intersect_key($data, array_flip($allowed));

        if (empty($filteredData)) {
            return false;
        }

        $sets = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($filteredData)));
        $filteredData['id'] = $id;

        $stmt = $this->db->prepare("UPDATE artists SET {$sets} WHERE id = :id");
        return $stmt->execute($filteredData);
    }

    public function updatePassword(int $id, string $newPassword): bool {
        $stmt = $this->db->prepare("UPDATE artists SET password = ? WHERE id = ?");
        return $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $id]);
    }

    public function updatePaymentDetails(int $id, array $paymentData): bool {
        $allowedFields = [
            'payment_method', 'bank_name', 'account_number', 'account_name',
            'mobile_network', 'mobile_number', 'paypal_email', 'payment_verified'
        ];
        
        $data = array_intersect_key($paymentData, array_flip($allowedFields));
        if (empty($data)) return false;

        $sets = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($data)));
        $data[':id'] = $id;
        
        $stmt = $this->db->prepare("UPDATE artists SET {$sets} WHERE id = :id");
        return $stmt->execute($data);
    }

    public function getPaymentDetails(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT payment_method, bank_name, account_number, account_name,
                    mobile_network, mobile_number, paypal_email, payment_verified
             FROM artists WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getStats(int $artistId): array {
        // Total songs by artist
        $stmt = $this->db->prepare("SELECT COUNT(*) as total_songs FROM songs WHERE artist_id = ?");
        $stmt->execute([$artistId]);
        $totalSongs = $stmt->fetchColumn();

        // Total downloads for artist's songs
        $stmt = $this->db->prepare("SELECT COUNT(*) as total_downloads FROM downloads d JOIN songs s ON d.song_id = s.id WHERE s.artist_id = ?");
        $stmt->execute([$artistId]);
        $totalDownloads = $stmt->fetchColumn();

        // Total earnings
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) as total_earnings FROM earnings WHERE artist_id = ?");
        $stmt->execute([$artistId]);
        $totalEarnings = (float) $stmt->fetchColumn();

        return [
            'total_songs' => (int) $totalSongs,
            'total_downloads' => (int) $totalDownloads,
            'total_earnings' => $totalEarnings
        ];
    }
}
