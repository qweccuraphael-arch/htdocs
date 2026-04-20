<?php
// app/models/Song.php

require_once dirname(__DIR__, 2) . '/config/db.php';

class Song {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll(int $page = 1, int $limit = 20, string $search = '', string $genre = ''): array {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT s.*, a.name AS artist_name, a.photo AS artist_photo
                FROM songs s JOIN artists a ON s.artist_id = a.id
                WHERE s.status = 'approved'";
        $params = [];
        
        if ($search !== '') {
            $sql .= " AND (s.title LIKE ? OR a.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($genre !== '') {
            $sql .= " AND s.genre = ?";
            $params[] = $genre;
        }
        
        $sql .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(string $search = '', string $genre = ''): int {
        $sql = "SELECT COUNT(*) FROM songs s JOIN artists a ON s.artist_id = a.id WHERE s.status = 'approved'";
        $params = [];
        
        if ($search !== '') {
            $sql .= " AND (s.title LIKE ? OR a.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($genre !== '') {
            $sql .= " AND s.genre = ?";
            $params[] = $genre;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT s.*, a.name AS artist_name, a.photo AS artist_photo, a.bio AS artist_bio
             FROM songs s JOIN artists a ON s.artist_id = a.id
             WHERE s.id = ? AND s.status = 'approved'"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getByArtist(int $artistId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM songs WHERE artist_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$artistId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFeatured(int $limit = 6): array {
        $stmt = $this->db->prepare(
            "SELECT s.*, a.name AS artist_name FROM songs s JOIN artists a ON s.artist_id = a.id
             WHERE s.status = 'approved' AND s.is_featured = 1
             ORDER BY s.download_count DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTrending(int $limit = 10): array {
        $stmt = $this->db->prepare(
            "SELECT s.*, a.name AS artist_name FROM songs s JOIN artists a ON s.artist_id = a.id
             WHERE s.status = 'approved'
             ORDER BY s.download_count DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO songs (artist_id, title, genre, album, year, file_path, cover_art, duration, file_size, status, created_at)
             VALUES (:artist_id, :title, :genre, :album, :year, :file_path, :cover_art, :duration, :file_size, 'pending', NOW())"
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE songs SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function incrementDownload(int $id): bool {
        $stmt = $this->db->prepare("UPDATE songs SET download_count = download_count + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getGenres(): array {
        $stmt = $this->db->query("SELECT DISTINCT genre FROM songs WHERE status = 'approved' ORDER BY genre");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAllAdmin(string $status = ''): array {
        $sql = "SELECT s.*, a.name AS artist_name FROM songs s JOIN artists a ON s.artist_id = a.id";
        $params = [];
        if ($status !== '') {
            $sql .= " WHERE s.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY s.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete(int $id): bool {
        $songStmt = $this->db->prepare("SELECT file_path, cover_art FROM songs WHERE id = ?");
        $songStmt->execute([$id]);
        $row = $songStmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            @unlink(STORAGE_PATH . $row['file_path']);
            if ($row['cover_art']) @unlink(dirname(STORAGE_PATH) . '/covers/' . $row['cover_art']);
        }
        $stmt = $this->db->prepare("DELETE FROM songs WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

