<?php
// app/models/Earnings.php

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

class Earnings {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function record(
        int $artistId,
        int $songId,
        float $amount,
        string $type = 'download',
        string $beneficiaryType = 'artist'
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO earnings (artist_id, song_id, amount, beneficiary_type, type, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$artistId, $songId, $amount, $beneficiaryType, $type]);
        return (int) $this->db->lastInsertId();
    }

    public function getByArtist(int $artistId, string $period = 'all'): array {
        $where = "WHERE e.artist_id = ? AND e.beneficiary_type = 'artist'";
        if ($period === 'month')  $where .= ' AND MONTH(e.created_at) = MONTH(NOW()) AND YEAR(e.created_at) = YEAR(NOW())';
        if ($period === 'week')   $where .= ' AND e.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        if ($period === 'today')  $where .= ' AND DATE(e.created_at) = CURDATE()';

        $stmt = $this->db->prepare(
            "SELECT e.*, s.title AS song_title FROM earnings e JOIN songs s ON e.song_id = s.id
             {$where} ORDER BY e.created_at DESC"
        );
        $stmt->execute([$artistId]);
        return $stmt->fetchAll();
    }

    public function totalByArtist(int $artistId, string $period = 'all'): float {
        $where = "WHERE artist_id = ? AND beneficiary_type = 'artist'";
        if ($period === 'month') $where .= ' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())';
        if ($period === 'week')  $where .= ' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        if ($period === 'today') $where .= ' AND DATE(created_at) = CURDATE()';

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) FROM earnings {$where}");
        $stmt->execute([$artistId]);
        return (float) $stmt->fetchColumn();
    }

    public function getMonthlyChart(int $artistId): array {
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(amount) AS total
             FROM earnings WHERE artist_id = ?
             GROUP BY month ORDER BY month DESC LIMIT 12"
        );
        $stmt->execute([$artistId]);
        return array_reverse($stmt->fetchAll());
    }

    public function getAllArtistsSummary(): array {
        return $this->db->query(
            "SELECT a.id, a.name, a.email,
                    COUNT(DISTINCT s.id) AS song_count,
                    COALESCE(SUM(s.download_count), 0) AS total_downloads,
                    COALESCE(SUM(e.amount), 0) AS total_earnings
             FROM artists a
             LEFT JOIN songs s ON s.artist_id = a.id AND s.status = 'approved'
             LEFT JOIN earnings e ON e.artist_id = a.id AND e.beneficiary_type = 'artist'
             GROUP BY a.id
             ORDER BY total_earnings DESC"
        )->fetchAll();
    }

    public function platformTotal(): float {
        return (float) $this->db->query(
            "SELECT COALESCE(SUM(amount),0) FROM earnings WHERE beneficiary_type = 'artist'"
        )->fetchColumn();
    }

    public function adminTotal(string $period = 'all'): float {
        $where = "WHERE beneficiary_type = 'admin'";
        if ($period === 'month') {
            $where .= ' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())';
        }
        if ($period === 'week') {
            $where .= ' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        }
        if ($period === 'today') {
            $where .= ' AND DATE(created_at) = CURDATE()';
        }

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) FROM earnings {$where}");
        $stmt->execute();
        return (float) $stmt->fetchColumn();
    }

    public function artistTotalGross(): float {
        return (float) $this->db->query(
            "SELECT COALESCE(SUM(amount),0) FROM earnings WHERE beneficiary_type = 'artist'"
        )->fetchColumn();
    }
}
