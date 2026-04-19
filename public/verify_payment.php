<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/helpers/paystack.php';
require_once dirname(__DIR__) . '/app/models/Earnings.php';

$reference = $_GET['reference'] ?? '';

if (!$reference) {
    die("No reference found.");
}

$data = paystack_verify_payment($reference);

if ($data && $data['status'] === 'success') {
    $song_id = $data['metadata']['song_id'];
    $artist_id = $data['metadata']['artist_id'];
    $amount = $data['amount'] / 100; // convert from pesewas to GHS

    // Record this as a real earning for the artist
    (new Earnings())->record($artist_id, $song_id, $amount, 'bonus'); 
    
    echo "<h2>✅ Payment Successful!</h2>";
    echo "<p>Thank you for supporting the artist. Your transaction reference is: <strong>$reference</strong></p>";
    echo "<a href='song.php?id=$song_id' style='padding:10px 20px; background:#d4af37; color:#000; text-decoration:none; border-radius:5px;'>Go back to Song</a>";
} else {
    echo "<h2>❌ Payment Verification Failed</h2>";
}
