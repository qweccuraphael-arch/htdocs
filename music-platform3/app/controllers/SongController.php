<?php
// app/controllers/SongController.php

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__) . '/models/Song.php';
require_once dirname(__DIR__) . '/models/Artist.php';
require_once dirname(__DIR__) . '/helpers/functions.php';
require_once dirname(__DIR__) . '/helpers/security.php';

class SongController {
    private Song   $songModel;
    private Artist $artistModel;

    // Allowed MIME types for uploads
    private array $allowedMimes = [
        'audio/mpeg', 'audio/mp3', 'audio/x-mpeg',
        'audio/wav', 'audio/x-wav',
        'audio/flac', 'audio/x-flac',
        'audio/aac', 'audio/x-aac',
    ];
    private array $allowedImageMimes = ['image/jpeg','image/png','image/webp'];
    private int   $maxFileSize  = 52_428_800; // 50 MB

    public function __construct() {
        $this->songModel   = new Song();
        $this->artistModel = new Artist();
    }

    public function uploadSong(array $post, array $files, int $artistId): array {
        // Validate required fields
        $title  = sanitize($post['title']  ?? '');
        $genre  = sanitize($post['genre']  ?? '');
        $album  = sanitize($post['album']  ?? '');
        $year   = (int)($post['year'] ?? date('Y'));

        if (!$title || !$genre) {
            return ['success' => false, 'error' => 'Title and genre are required.'];
        }

        // Validate audio file
        if (empty($files['audio']) || $files['audio']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Please select an audio file.'];
        }

        $audioFile = $files['audio'];
        if ($audioFile['size'] > $this->maxFileSize) {
            return ['success' => false, 'error' => 'Audio file exceeds 50 MB limit.'];
        }
        if (!isValidMimeType($audioFile['tmp_name'], $this->allowedMimes)) {
            return ['success' => false, 'error' => 'Invalid audio format. Allowed: MP3, WAV, FLAC, AAC.'];
        }

        // Save audio
        $audioName = uniqid('song_') . '_' . safeFilename($audioFile['name']);
        $audioPath = STORAGE_PATH . $audioName;
        if (!move_uploaded_file($audioFile['tmp_name'], $audioPath)) {
            return ['success' => false, 'error' => 'Failed to save audio file.'];
        }

        // Optional cover art
        $coverName = null;
        if (!empty($files['cover']) && $files['cover']['error'] === UPLOAD_ERR_OK) {
            $coverFile = $files['cover'];
            if (isValidMimeType($coverFile['tmp_name'], $this->allowedImageMimes)) {
                $coverName = uniqid('cover_') . '_' . safeFilename($coverFile['name']);
                $coverDir  = dirname(STORAGE_PATH) . '/covers/';
                @mkdir($coverDir, 0755, true);
                move_uploaded_file($coverFile['tmp_name'], $coverDir . $coverName);
            }
        }

        // Get duration (requires ffprobe if installed)
        $duration = $this->getAudioDuration($audioPath);

        $songId = $this->songModel->create([
            ':artist_id'  => $artistId,
            ':title'      => $title,
            ':genre'      => $genre,
            ':album'      => $album,
            ':year'       => $year,
            ':file_path'  => $audioName,
            ':cover_art'  => $coverName,
            ':duration'   => $duration,
            ':file_size'  => $audioFile['size'],
        ]);

        return ['success' => true, 'song_id' => $songId];
    }

    private function getAudioDuration(string $path): int {
        if (!function_exists('shell_exec')) return 0;
        $out = @shell_exec("ffprobe -i " . escapeshellarg($path) .
                           " -show_entries format=duration -v quiet -of csv='p=0' 2>&1");
        return $out ? (int) floatval(trim($out)) : 0;
    }

    public function approveSong(int $id): array {
        $song   = $this->songModel->getById($id) ?? $this->getSongAdmin($id);
        if (!$song) return ['success' => false, 'error' => 'Song not found'];

        $this->songModel->updateStatus($id, 'approved');

        $artist = $this->artistModel->getById($song['artist_id']);
        if ($artist) {
            // Email + SMS
            sendSongApprovedEmail($artist['email'], $artist['name'], $song['title']);
            if (!empty($artist['phone'])) smsSongApproved($artist['phone'], $artist['name'], $song['title']);
        }
        return ['success' => true];
    }

    public function rejectSong(int $id, string $reason = ''): array {
        $song = $this->getSongAdmin($id);
        if (!$song) return ['success' => false, 'error' => 'Song not found'];

        $this->songModel->updateStatus($id, 'rejected');

        $artist = $this->artistModel->getById($song['artist_id']);
        if ($artist) {
            if (!empty($artist['phone'])) smsSongRejected($artist['phone'], $artist['name'], $song['title'], $reason);
        }
        return ['success' => true];
    }

    private function getSongAdmin(int $id): ?array {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT s.*, a.name AS artist_name FROM songs s JOIN artists a ON s.artist_id = a.id WHERE s.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
