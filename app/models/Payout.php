<?php
// app/models/Payout.php

require_once dirname(__DIR__, 2) . '/config/db.php';

class Payout {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO payouts (artist_id, amount, reference, status)
             VALUES (?, ?, ?, 'pending')"
        );
        $stmt->execute([
            $data['artist_id'],
            $data['amount'],
            $data['reference']
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(string $reference, string $status, ?string $transferCode = null): bool {
        $sql = "UPDATE payouts SET status = ?";
        $params = [$status];
        
        if ($transferCode) {
            $sql .= ", paystack_transfer_code = ?";
            $params[] = $transferCode;
        }
        
        $sql .= " WHERE reference = ?";
        $params[] = $reference;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getByArtist(int $artistId): array {
        // Use requested_at as fallback for older schemas if needed
        $stmt = $this->db->prepare("SELECT * FROM payouts WHERE artist_id = ? ORDER BY id DESC");
        $stmt->execute([$artistId]);
        return $stmt->fetchAll();
    }

    public function getByReference(string $reference): ?array {
        $stmt = $this->db->prepare("SELECT * FROM payouts WHERE reference = ?");
        $stmt->execute([$reference]);
        return $stmt->fetch() ?: null;
    }
}
