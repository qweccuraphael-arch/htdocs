<?php
// api/stream.php

require_once dirname(__DIR__) . '/app/controllers/StreamController.php';

$songId = (int) ($_GET['id'] ?? 0);
if (!$songId) {
    http_response_code(400);
    die('Song ID required');
}

$controller = new StreamController();
$controller->handleStream($songId);
?>