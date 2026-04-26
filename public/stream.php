<?php
// Secure audio streaming endpoint with range requests (MP3 partial content)
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/app/models/Song.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit('Missing song ID');
}
$songModel = new Song();
$song = $songModel->getById($id);
if (!$song || !$song['file_path']) {
    http_response_code(404);
    exit('Song not found');
}

$audioPath = STORAGE_PATH . $song['file_path'];
if (!file_exists($audioPath)) {
    http_response_code(404);
    exit('Audio file missing');
}

// Log stream play
logActivity('stream', 'Song streamed', ['song_id' => $id, 'ip' => getClientIP()]);

// Stream headers
$size = filesize($audioPath);
$length = $size;
$start = 0;
$end = $size - 1;

header('Content-Type: audio/mpeg');
header('Accept-Ranges: bytes');

if (isset($_SERVER['HTTP_RANGE'])) {
    // Partial content (seeking)
    preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches);
    $start = (int) $matches[1];
    $end = $matches[2] ? (int) $matches[2] : $size;
    $end = min($end, $size - 1);
    $length = $end - $start + 1;

    http_response_code(206);
    header("Content-Range: bytes $start-$end/$size");
} 

header("Content-Length: $length");
header('Cache-Control: public, max-age=3600');
header('X-Content-Duration: ' . ($song['duration'] ?? 0));

$fp = fopen($audioPath, 'rb');
fseek($fp, $start);
$buffer = 1024 * 8; // 8KB chunks
while (!feof($fp) && ($p = ftell($fp)) <= $end) {
    set_time_limit(0);
    echo fread($fp, $buffer);
    flush();
}
fclose($fp);
exit;

