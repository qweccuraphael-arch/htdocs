<?php
require_once 'config/db.php';
require_once 'config/app.php';
require_once 'app/helpers/paystack.php';

echo "=== BEATWAVE INTEGRATION TEST ===\n";

// 1. Check Database Tables
try {
    $db = getDB();
    echo "[1/3] Checking Database... ";
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $required = ['payouts', 'song_purchases'];
    $missing = array_diff($required, $tables);
    
    if (empty($missing)) {
        echo "✅ Tables exist (payouts, song_purchases).\n";
    } else {
        echo "❌ Missing tables: " . implode(', ', $missing) . "\n";
    }
} catch (Exception $e) {
    echo "❌ DB Error: " . $e->getMessage() . "\n";
}

// 2. Check Paystack Connection
echo "[2/3] Checking Paystack Connection... ";
$banks = paystack_get_banks();
if (!empty($banks)) {
    echo "✅ Success! " . count($banks) . " banks retrieved.\n";
} else {
    echo "❌ Failed! Check your Paystack Secret Key in config/app.php\n";
}

// 3. Test Payment Initialization (Logic check)
echo "[3/3] Generating Test Payment URL... ";
$test_url = paystack_initialize_payment("test@example.com", 1.00, "http://localhost/verify.php", ['test' => true]);
if ($test_url) {
    echo "✅ Success!\n";
    echo "     URL: $test_url\n";
    echo "\n>>> THE CODE IS WORKING. YOU CAN OPEN THE URL ABOVE TO PAY <<<\n";
} else {
    echo "❌ Failed to generate URL.\n";
}

echo "=================================\n";
