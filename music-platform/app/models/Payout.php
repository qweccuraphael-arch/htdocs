<?php
// app/models/Payout.php

require_once dirname(__DIR__, 2) . '/config/db.php';

class Payout {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function request(int $artistId, float $amount, string $paymentMethod, array $paymentDetails): int {
        $available = $this->getAvailableBalance($artistId);
        if ($amount > $available) {
            throw new Exception('Insufficient balance. Available: GHS ' . number_format($available, 2));
        }

        if ($amount <= 0) {
            throw new Exception('Withdrawal amount must be greater than zero.');
        }

        if (empty($paymentDetails['payment_method']) || $paymentDetails['payment_method'] === 'none') {
            throw new Exception('Set your payment method before requesting a withdrawal.');
        }

        // Removed manual payment_verified check for easier artist access

        $stmt = $this->db->prepare(
            "INSERT INTO payouts (artist_id, amount, payment_method, payment_details, status, requested_at)
             VALUES (?, ?, ?, ?, 'pending', NOW())"
        );
        $stmt->execute([
            $artistId,
            $amount,
            $paymentMethod,
            json_encode($paymentDetails)
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getByArtist(int $artistId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM payouts WHERE artist_id = ? ORDER BY requested_at DESC"
        );
        $stmt->execute([$artistId]);
        return $stmt->fetchAll();
    }

    public function getAll(): array {
        return $this->db->query(
            "SELECT p.*, a.name AS artist_name, a.email AS artist_email
             FROM payouts p
             JOIN artists a ON p.artist_id = a.id
             ORDER BY p.requested_at DESC"
        )->fetchAll();
    }

    public function updateStatus(int $payoutId, string $status, ?string $adminNotes = null): bool {
        $sql = "UPDATE payouts SET status = ?, admin_notes = ?";

        if (in_array($status, ['approved', 'paid', 'rejected'], true)) {
            $sql .= ", processed_at = NOW()";
        }

        $sql .= " WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $adminNotes, $payoutId]);
    }

    public function getAvailableBalance(int $artistId): float {
        $earningsStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0)
             FROM earnings
             WHERE artist_id = ? AND beneficiary_type = 'artist'"
        );
        $earningsStmt->execute([$artistId]);
        $earned = (float) $earningsStmt->fetchColumn();

        $payoutStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0)
             FROM payouts
             WHERE artist_id = ? AND status IN ('pending', 'approved', 'paid')"
        );
        $payoutStmt->execute([$artistId]);
        $reserved = (float) $payoutStmt->fetchColumn();

        return max(0.0, $earned - $reserved);
    }

    public function getPendingTotal(): float {
        return (float) $this->db->query(
            "SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE status = 'pending'"
        )->fetchColumn();
    }
}

