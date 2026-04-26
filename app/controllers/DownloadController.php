<?php
// app/controllers/DownloadController.php

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__) . '/models/Song.php';
require_once dirname(__DIR__) . '/models/Download.php';
require_once dirname(__DIR__) . '/models/Earnings.php';
require_once dirname(__DIR__) . '/helpers/functions.php';
require_once dirname(__DIR__) . '/helpers/security.php';

class DownloadController {
    private Song      $songModel;
    private Download  $downloadModel;
    private Earnings  $earningsModel;

    public function __construct() {
        $this->songModel     = new Song();
        $this->downloadModel = new Download();
        $this->earningsModel = new Earnings();
    }

    public function handleDownload(int $songId) {
        // Rate limit: 10 downloads/minute per IP
        $ip = getClientIP();
        if (!rateLimitCheck("dl_{$ip}", 10, 60)) {
            http_response_code(429);
            die('Too many requests. Please wait a moment.');
        }

        $song = $this->songModel->getById($songId);
        if (!$song) {
            logActivity('debug', "Song not found: ID $songId");
            http_response_code(404);
            die('Song not found.');
        }

        $filePath = STORAGE_PATH . $song['file_path'];
        if (!file_exists($filePath)) {
            logActivity('debug', "File not found on disk: $filePath");
            http_response_code(404);
            die('File not found.');
        }

        logActivity('debug', "Starting download for song: " . $song['title'] . " ($filePath)");

        // Log the download
        $this->downloadModel->log($songId, $song['artist_id']);

        // Increment counter
        $this->songModel->incrementDownload($songId);

        // Record Artist earnings
        $this->earningsModel->record(
            $song['artist_id'],
            $songId,
            EARNINGS_PER_DOWNLOAD,
            'download',
            'artist'
        );

        // Record Admin earnings
        $this->earningsModel->record(
            $song['artist_id'],
            $songId,
            ADMIN_EARNINGS_PER_DOWNLOAD,
            'download',
            'admin'
        );

        // Stream the file
        $filename = sanitize($song['title']) . '_BeatWave.mp3';
        header('Content-Type: audio/mpeg');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        ob_clean();
        flush();
        readfile($filePath);
        exit;
    }
}
