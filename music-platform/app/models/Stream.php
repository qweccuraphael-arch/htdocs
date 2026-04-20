<?php
// app/models/Stream.php

require_once dirname(__DIR__, 2) . '/config/db.php';

class Stream {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function log(int $songId, int $artistId): int {
        $stmt = $this->db->prepare(
            "INSERT INTO streams (song_id, artist_id, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $songId,
            $artistId,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getRecentByArtist(int $artistId, int $limit = 20): array {
        $stmt = $this->db->prepare(
            "SELECT s.*, sg.title AS song_title FROM streams s JOIN songs sg ON s.song_id = sg.id
             WHERE s.artist_id = ? ORDER BY s.created_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $artistId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit,    PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getStatsAdmin(): array {
        return $this->db->query(
            "SELECT DATE(created_at) AS day, COUNT(*) AS total
             FROM streams
             GROUP BY day ORDER BY day DESC LIMIT 30"
        )->fetchAll();
    }

    public function countToday(): int {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM streams WHERE DATE(created_at) = CURDATE()"
        )->fetchColumn();
    }

    public function countTotal(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM streams")->fetchColumn();
    }
}