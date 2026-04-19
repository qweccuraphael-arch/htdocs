<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/models/Song.php';
require_once dirname(__DIR__) . '/app/helpers/paystack.php';

$id = (int)($_GET['id'] ?? 0);
$song = (new Song())->getById($id);

if (!$song) {
    die("Song not found.");
}

// In a real app, you'd get the user's email from a session or form
$email = "customer-" . time() . "@example.com"; 
$amount = 2.00; // Let's set a fixed price for testing
$callback_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/verify_payment.php";

$url = paystack_initialize_payment($email, $amount, $callback_url, [
    'song_id' => $id,
    'artist_id' => $song['artist_id']
]);

if ($url) {
    header("Location: " . $url);
    exit;
} else {
    echo "Error: Could not connect to Paystack.";
}
