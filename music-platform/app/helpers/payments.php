<?php
// app/helpers/payments.php

require_once dirname(__DIR__, 2) . '/config/app.php';

/**
 * Creates a Transfer Recipient on Paystack.
 */
function paystackCreateRecipient(array $artist): ?string {
    $url = "https://api.paystack.co/transferrecipient";
    
    $payload = [
        'type' => $artist['payment_method'] === 'bank' ? 'nuban' : 'mobile_money',
        'name' => $artist['name'],
        'currency' => 'GHS'
    ];

    if ($artist['payment_method'] === 'bank') {
        $payload['account_number'] = $artist['account_number'];
        $payload['bank_code'] = $artist['bank_name']; // Note: Frontend should provide bank codes
    } else {
        $payload['account_number'] = $artist['mobile_number'];
        
        // Map to Paystack Ghana MoMo codes
        $momoCodes = [
            'mtn' => 'MTN',
            'vodafone' => 'VOD',
            'airteltigo' => 'ATL'
        ];
        $payload['bank_code'] = $momoCodes[$artist['mobile_network']] ?? $artist['mobile_network'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (isset($data['status']) && $data['status'] === true) {
        logActivity('payment', 'Paystack recipient created', ['recipient_code' => $data['data']['recipient_code']]);
        return $data['data']['recipient_code'];
    }
    
    logActivity('payment', 'Paystack recipient failed', ['response' => $data, 'payload' => $payload]);
    return null;
}

/**
 * Initiates a Paystack Transfer.
 */
function paystackInitiateTransfer(float $amount, string $recipientCode): array {
    $url = "https://api.paystack.co/transfer";
    
    $payload = [
        'source' => 'balance',
        'amount' => $amount * 100, // Convert to pesewas
        'recipient' => $recipientCode,
        'reason' => 'Artist Earnings Withdrawal'
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    return [
        'success' => $data['status'] ?? false,
        'message' => $data['message'] ?? 'Connection error',
        'data' => $data['data'] ?? null
    ];
}

/**
 * Initiates a PayPal Payout.
 */
function paypalInitiatePayout(float $amount, string $email): array {
    // 1. Get Access Token
    $authUrl = (PAYPAL_MODE === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com') . '/v1/oauth2/token';
    
    $ch = curl_init($authUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Language: en_US']
    ]);
    $authResp = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!isset($authResp['access_token'])) {
        return ['success' => false, 'message' => 'PayPal Auth Failed'];
    }

    $token = $authResp['access_token'];

    // 2. Create Payout
    $payoutUrl = (PAYPAL_MODE === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com') . '/v1/payments/payouts';
    
    $payload = [
        'sender_batch_header' => [
            'sender_batch_id' => uniqid('payout_'),
            'email_subject' => 'You have a payout from BeatWave!',
            'email_message' => 'You have received a payout! Thanks for using BeatWave.'
        ],
        'items' => [[
            'recipient_type' => 'EMAIL',
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => 'USD' // Adjust currency as needed
            ],
            'note' => 'Artist Earnings',
            'receiver' => $email
        ]]
    ];

    $ch = curl_init($payoutUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]
    ]);
    $payoutResp = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $success = isset($payoutResp['batch_header']['batch_status']);
    return [
        'success' => $success,
        'message' => $success ? 'Payout initiated' : ($payoutResp['message'] ?? 'Payout failed'),
        'data' => $payoutResp
    ];
}
