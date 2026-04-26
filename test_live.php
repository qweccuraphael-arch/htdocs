<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/helpers/functions.php';

// UPDATE THIS TO YOUR REAL EMAIL TO TEST
$target = 'your-email@example.com'; 

echo "--- LIVE NOTIFICATION TEST ---\n";
echo "Target: $target\n\n";

if (SMTP_USER === 'your@gmail.com') {
    echo "⚠️ WARNING: You haven't updated music-platform3/config/app.php with real credentials yet!\n";
    echo "This test will likely fail.\n\n";
}

echo "1. Sending Registration OTP...\n";
$res1 = sendOTPEmail($target, "Test User", "999888");
echo "Result: " . ($res1 ? "✅ SUCCESS" : "❌ FAILED") . "\n\n";

echo "2. Sending Withdrawal Alert...\n";
$res2 = sendWithdrawalEmail($target, "Test User", 50.00, "BW-TEST-101");
echo "Result: " . ($res2 ? "✅ SUCCESS" : "❌ FAILED") . "\n\n";

echo "Check storage/logs for server responses if failed.\n";
