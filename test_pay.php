<?php
require_once 'config/app.php';
require_once 'app/helpers/paystack.php';

// Simulate a user buying Song #1
$customer_email = "customer@example.com";
$amount = 5.00; // GHS 5.00
$callback_url = APP_URL . "/public/verify_payment.php"; // You'll need to create this

echo "<h2>Testing Paystack Payment</h2>";
echo "Initializing a GHS 5.00 payment for $customer_email...<br>";

$url = paystack_initialize_payment($customer_email, $amount, $callback_url, ['song_id' => 1]);

if ($url) {
    echo "✅ Success! Redirecting to Paystack in 3 seconds...<br>";
    echo "<a href='$url'>Click here if not redirected</a>";
    echo "<script>setTimeout(() => { window.location.href = '$url'; }, 3000);</script>";
} else {
    echo "❌ Failed to initialize payment. Check your Paystack Secret Key.";
}
