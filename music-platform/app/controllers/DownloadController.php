<?php
// app/controllers/DownloadController.php

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__) . '/models/Song.php';
require_once dirname(__DIR__) . '/models/Download.php';
require_once dirname(__DIR__) . '/models/Earnings.php';
require_once dirname(__DIR__) . '/models/Artist.php';
require_once dirname(__DIR__) . '/helpers/functions.php';
require_once dirname(__DIR__) . '/helpers/security.php';

class DownloadController {
    private Song $songModel;
    private Download $downloadModel;
    private Earnings $earningsModel;
    private Artist $artistModel;
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
        $this->songModel = new Song();
        $this->downloadModel = new Download();
        $this->earningsModel = new Earnings();
        $this->artistModel = new Artist();
    }

    public function handleDownload(int $songId) {
        $ip = getClientIP();
        if (!rateLimitCheck("dl_{$ip}", 10, 60)) {
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
            $this->downloadModel->log($songId, $song['artist_id']);
            $this->songModel->incrementDownload($songId);

            $this->earningsModel->record(
                $song['artist_id'],
                $songId,
                ARTIST_EARNINGS_PER_DOWNLOAD,
                'download',
                'artist'
            );

            if (ADMIN_EARNINGS_PER_DOWNLOAD > 0) {
                $this->earningsModel->record(
                    $song['artist_id'],
                    $songId,
                    ADMIN_EARNINGS_PER_DOWNLOAD,
                    'download',
                    'admin'
                );
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            logActivity('error', 'Download tracking failed', ['error' => $e->getMessage(), 'song_id' => $songId]);
            // We still allow the file download to proceed if it's a critical UX, 
            // but usually, it's better to fail if tracking/earnings are the core business.
            http_response_code(500);
            die('Internal server error.');
        }

        $artist = $this->artistModel->getById($song['artist_id']);
        if ($artist && !empty($artist['phone'])) {
            $artistMessage = "BeatWave: '{$song['title']}' was downloaded. Artist earning: GHS " .
                number_format(ARTIST_EARNINGS_PER_DOWNLOAD, 2) .
                ". Admin earning: GHS " . number_format(ADMIN_EARNINGS_PER_DOWNLOAD, 2) . '.';
            sendSMS($artist['phone'], $artistMessage);
        }

        if (defined('ADMIN_ALERT_PHONE') && ADMIN_ALERT_PHONE !== '') {
            $adminMessage = "BeatWave admin alert: '{$song['title']}' by {$song['artist_name']} downloaded. " .
                'Artist: GHS ' . number_format(ARTIST_EARNINGS_PER_DOWNLOAD, 2) .
                ', Admin: GHS ' . number_format(ADMIN_EARNINGS_PER_DOWNLOAD, 2) . '.';
            sendSMS(ADMIN_ALERT_PHONE, $adminMessage);
        }

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
