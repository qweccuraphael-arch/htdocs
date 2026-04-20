<?php
require_once 'config/app.php';
require_once 'app/helpers/paystack.php';
require_once 'app/helpers/functions.php';

echo "--- Connection Test ---\n";

// 1. Test Paystack
echo "Testing Paystack... ";
$banks = paystack_get_banks();
if (!empty($banks)) {
    echo "✅ SUCCESS! Received " . count($banks) . " banks from Paystack.\n";
} else {
    echo "❌ FAILED! Check Paystack Secret Key.\n";
}

// 2. Test Arkesel SMS (Fetch Balance)
echo "Testing Arkesel SMS... ";
$ch = curl_init('https://sms.arkesel.com/api/v2/clients/balance-info');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['api-key: ' . SMS_API_KEY],
    CURLOPT_TIMEOUT        => 10,
]);
$resp = curl_exec($ch);
$data = json_decode($resp, true);
curl_close($ch);

if (isset($data['status']) && $data['status'] === 'success') {
    $balance = $data['data'][0]['balance'] ?? 'Unknown';
    echo "✅ SUCCESS! Your SMS Balance is: " . $balance . "\n";
} else {
    echo "❌ FAILED! Check Arkesel API Key. Response: " . $resp . "\n";
}

echo "-----------------------\n";
