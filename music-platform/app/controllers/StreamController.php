<?php
// app/controllers/StreamController.php

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__) . '/models/Song.php';
require_once dirname(__DIR__) . '/models/Stream.php';
require_once dirname(__DIR__) . '/models/Earnings.php';
require_once dirname(__DIR__) . '/helpers/functions.php';
require_once dirname(__DIR__) . '/helpers/security.php';

class StreamController {
    private Song      $songModel;
    private Stream    $streamModel;
    private Earnings  $earningsModel;
    private PDO       $db;

    public function __construct() {
        $this->db            = getDB();
        $this->songModel     = new Song();
        $this->streamModel   = new Stream();
        $this->earningsModel = new Earnings();
    }

    public function handleStream(int $songId) {
        // Rate limit: 100 streams/minute per IP
        $ip = getClientIP();
        if (!rateLimitCheck("stream_{$ip}", 100, 60)) {
            http_response_code(429);
            die('Too many requests. Please wait a moment.');
        }

        $song = $this->songModel->getById($songId);
        if (!$song) {
            http_response_code(404);
            die('Song not found.');
        }

        $filePath = STORAGE_PATH . $song['file_path'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            die('File not found.');
        }

        $this->db->beginTransaction();
        try {
            // Log the stream
            $this->streamModel->log($songId, $song['artist_id']);

            // Record earnings
            $this->earningsModel->record(
                $song['artist_id'],
                $songId,
                EARNINGS_PER_STREAM,
                'stream',
                'artist'
            );

            if (ADMIN_EARNINGS_PER_STREAM > 0) {
                $this->earningsModel->record(
                    $song['artist_id'],
                    $songId,
                    ADMIN_EARNINGS_PER_STREAM,
                    'stream',
                    'admin'
                );
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            logActivity('error', 'Stream tracking failed', ['error' => $e->getMessage()]);
        }

        // Stream the file
        $filename = sanitize($song['title']) . '_BeatWave.mp3';
        header('Content-Type: audio/mpeg');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Accept-Ranges: bytes');
        header('Cache-Control: no-cache');
        header('X-Content-Type-Options: nosniff');
        ob_clean();
        flush();
        readfile($filePath);
        exit;
    }
}
