<?php
// api/auth.php
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/models/Artist.php';
require_once dirname(__DIR__) . '/app/helpers/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']); exit;
}

$data  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
$pass  = $data['password'] ?? '';

if (!$email || !$pass) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password required']); exit;
}

$model  = new Artist();
$artist = $model->verifyLogin($email, $pass);

if (!$artist) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']); exit;
}

if ($artist['status'] !== 'active') {
    http_response_code(403);
    echo json_encode(['error' => 'Account suspended']); exit;
}

// Return safe artist data (no password)
unset($artist['password']);
echo json_encode(['success' => true, 'artist' => $artist]);
