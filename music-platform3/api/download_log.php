<?php
// api/download_log.php
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/models/Download.php';
require_once dirname(__DIR__) . '/app/helpers/security.php';

// Simple API key check
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
if ($apiKey !== 'YOUR_INTERNAL_API_KEY') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$model = new Download();
echo json_encode([
    'success'        => true,
    'today'          => $model->countToday(),
    'total'          => $model->countTotal(),
    'recent_30_days' => $model->getStatsAdmin(),
]);
