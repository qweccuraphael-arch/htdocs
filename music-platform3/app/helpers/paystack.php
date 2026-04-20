<?php
// app/helpers/paystack.php

require_once dirname(__DIR__, 2) . '/config/app.php';

/**
 * Paystack Helper for GH (Ghana)
 */

function paystack_headers(): array {
    return [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
    ];
}

/**
 * Get list of banks/providers in Ghana
 */
function paystack_get_banks(): array {
    $ch = curl_init('https://api.paystack.co/bank?country=ghana');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => paystack_headers(),
    ]);
    $response = curl_exec($ch);
    $data     = json_decode($response, true);
    curl_close($ch);

    return $data['status'] ? $data['data'] : [];
}

/**
 * Create a Transfer Recipient
 * @param string $name Account name
 * @param string $account_number Bank account or Momo number
 * @param string $bank_code Bank code from paystack_get_banks()
 */
function paystack_create_recipient(string $name, string $account_number, string $bank_code): ?string {
    $payload = json_encode([
        'type'           => 'ghiprops', // Use 'ghiprops' for Ghana banks and Momo
        'name'           => $name,
        'account_number' => $account_number,
        'bank_code'      => $bank_code,
        'currency'       => 'GHS',
    ]);

    $ch = curl_init('https://api.paystack.co/transferrecipient');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => paystack_headers(),
    ]);
    $response = curl_exec($ch);
    $data     = json_decode($response, true);
    curl_close($ch);

    return ($data['status'] && isset($data['data']['recipient_code'])) ? $data['data']['recipient_code'] : null;
}

/**
 * Initiate a Payout (Transfer)
 */
function paystack_initiate_transfer(float $amount, string $recipient_code, string $reference): array {
    $payload = json_encode([
        'source'    => 'balance',
        'amount'    => (int)($amount * 100), // convert to pesewas
        'recipient' => $recipient_code,
        'reference' => $reference,
        'reason'    => 'BeatWave Artist Payout',
        'currency'  => 'GHS',
    ]);

    $ch = curl_init(PAYSTACK_TRANSFER_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => paystack_headers(),
    ]);
    $response = curl_exec($ch);
    $data     = json_decode($response, true);
    curl_close($ch);

    return $data;
}

/**
 * Initialize a Payment (User buying a song)
 */
function paystack_initialize_payment(string $email, float $amount, string $callback_url, array $metadata = []): ?string {
    $payload = json_encode([
        'email'    => $email,
        'amount'   => (int)($amount * 100),
        'callback_url' => $callback_url,
        'metadata' => $metadata,
        'currency' => 'GHS',
    ]);

    $ch = curl_init(PAYSTACK_PAYMENT_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => paystack_headers(),
    ]);
    $response = curl_exec($ch);
    $data     = json_decode($response, true);
    curl_close($ch);

    return ($data['status'] && isset($data['data']['authorization_url'])) ? $data['data']['authorization_url'] : null;
}

/**
 * Verify a Payment
 */
function paystack_verify_payment(string $reference): ?array {
    $ch = curl_init("https://api.paystack.co/transaction/verify/" . rawurlencode($reference));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => paystack_headers(),
    ]);
    $response = curl_exec($ch);
    $data     = json_decode($response, true);
    curl_close($ch);

    return ($data['status'] && $data['data']['status'] === 'success') ? $data['data'] : null;
}
