<?php
// app/helpers/functions.php

require_once dirname(__DIR__, 2) . '/config/app.php';

// ── Formatting ────────────────────────────────────────────────────────────────
function formatNumber(int $n): string {
    if ($n >= 1_000_000) return round($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return round($n / 1_000, 1) . 'K';
    return (string) $n;
}

function formatBytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 3) { $bytes /= 1024; $i++; }
    return round($bytes, 2) . ' ' . $units[$i];
}

function formatMoney(float $amount): string {
    return 'GHS ' . number_format($amount, 2);
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return intval($diff / 60) . 'm ago';
    if ($diff < 86400)  return intval($diff / 3600) . 'h ago';
    if ($diff < 604800) return intval($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function getClientIP(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            return explode(',', $_SERVER[$key])[0];
        }
    }
    return '0.0.0.0';
}

// ── Logging ───────────────────────────────────────────────────────────────────
function logActivity(string $type, string $message, array $context = []) {
    $entry = [
        'time'    => date('Y-m-d H:i:s'),
        'type'    => $type,
        'message' => $message,
        'context' => $context,
        'ip'      => getClientIP(),
    ];
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0777, true);
    }
    $file = LOG_PATH . date('Y-m-d') . '_activity.log';
    file_put_contents($file, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// ═════════════════════════════════════════════════════════════════════════════
//  EMAIL  (uses PHP's built-in mail() — swap for PHPMailer/SMTP easily)
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Send an email.
 *
 * @param string $to      Recipient address
 * @param string $subject Subject line
 * @param string $body    HTML body
 * @param string $plain   Plain-text fallback (auto-generated if empty)
 * @return bool
 */
function sendEmail(string $to, string $subject, string $body, string $plain = ''): bool {
    if (empty($plain)) {
        $plain = strip_tags($body);
    }

    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    $timeout = 10;

    // 1. DNS check - if it fails, we know we can't connect to SMTP_HOST
    if (gethostbyname($host) === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
        logActivity('email_error', "DNS resolution failed for $host. Falling back to mail().");
        return _mailFallback($to, $subject, $body, $plain);
    }

    // 2. Try SMTP
    $socket = @fsockopen(($port == 465 ? 'ssl://' : '') . $host, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        logActivity('email_error', "SMTP Socket Error: $errstr ($errno). Falling back to mail().");
        return _mailFallback($to, $subject, $body, $plain);
    }

    function get_response($socket) {
        $res = "";
        while ($str = fgets($socket, 515)) {
            $res .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $res;
    }

    try {
        get_response($socket);
        fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
        get_response($socket);

        if ($port == 587) {
            fwrite($socket, "STARTTLS\r\n");
            $tls_res = get_response($socket);
            if (substr($tls_res, 0, 3) != "220") {
                throw new Exception("STARTTLS failed: $tls_res");
            }
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("TLS encryption failed");
            }
            fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
            get_response($socket);
        }

        fwrite($socket, "AUTH LOGIN\r\n");
        get_response($socket);
        fwrite($socket, base64_encode($user) . "\r\n");
        get_response($socket);
        fwrite($socket, base64_encode($pass) . "\r\n");
        $login_res = get_response($socket);

        if (substr($login_res, 0, 3) != "235") {
            throw new Exception("SMTP Login Failed: $login_res");
        }

        fwrite($socket, "MAIL FROM: <$user>\r\n");
        get_response($socket);
        fwrite($socket, "RCPT TO: <$to>\r\n");
        get_response($socket);
        fwrite($socket, "DATA\r\n");
        get_response($socket);

        $boundary = md5(uniqid());
        $headers  = "From: " . MAIL_FROM_NAME . " <$user>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $headers .= "X-Mailer: BeatWave-SMTP\r\n";

        $message  = "$headers\r\n";
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n$plain\r\n\r\n";
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n$body\r\n\r\n";
        $message .= "--$boundary--\r\n.\r\n";

        fwrite($socket, $message);
        $send_res = get_response($socket);
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        $success = (substr($send_res, 0, 3) == "250");
        logActivity('email', $success ? 'Sent via SMTP' : 'SMTP Send Failed', ['to' => $to, 'res' => $send_res]);
        return $success;

    } catch (Exception $e) {
        logActivity('email_error', "SMTP Exception: " . $e->getMessage() . ". Trying mail().");
        if (is_resource($socket)) fclose($socket);
        return _mailFallback($to, $subject, $body, $plain);
    }
}

/**
 * Fallback to PHP's built-in mail() function
 */
function _mailFallback(string $to, string $subject, string $body, string $plain = ''): bool {
    $boundary = md5(uniqid());
    $headers  = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $message  = "--$boundary\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n" . ($plain ?: strip_tags($body)) . "\r\n\r\n";
    $message .= "--$boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n$body\r\n\r\n";
    $message .= "--$boundary--";

    $success = @mail($to, $subject, $message, $headers);
    logActivity('email', $success ? 'Sent via mail()' : 'mail() failed', ['to' => $to]);
    return $success;
}

// ── Pre-built email templates ─────────────────────────────────────────────────
function emailTemplate(string $title, string $content): string {
    $_year = date('Y');
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body{font-family:Arial,sans-serif;background:#0a0a0a;color:#f0f0f0;margin:0;padding:0}
  .wrap{max-width:600px;margin:40px auto;background:#111;border-radius:12px;overflow:hidden;border:1px solid #222}
  .head{background:linear-gradient(135deg,#d4af37,#f5c842);padding:30px;text-align:center}
  .head h1{margin:0;font-size:24px;color:#000;letter-spacing:2px}
  .body{padding:30px;line-height:1.7}
  .body h2{color:#d4af37;margin-top:0}
  .btn{display:inline-block;background:#d4af37;color:#000;padding:12px 30px;border-radius:6px;text-decoration:none;font-weight:bold;margin:20px 0}
  .foot{background:#0a0a0a;padding:20px;text-align:center;font-size:12px;color:#666}
  .highlight{color:#d4af37;font-weight:bold}
</style>
</head>
<body>
<div class="wrap">
  <div class="head"><h1>🎵 BeatWave</h1></div>
  <div class="body">
    <h2>{$title}</h2>
    {$content}
  </div>
  <div class="foot">
    &copy; {$_year} BeatWave &mdash; Ghana's Music Hub<br>
    <a href="mailto:support@yourdomain.com" style="color:#d4af37">support@yourdomain.com</a>
  </div>
</div>
</body>
</html>
HTML;
}

// Specific email senders
function sendWelcomeEmail(string $to, string $artistName): bool {
    $content = emailTemplate(
        "Welcome to Beatwave",
        "<p>Your account is ready.</p>"
    );
    return sendEmail($to, 'Welcome to Beatwave', $content);
}

function sendOTPEmail(string $to, string $artistName, string $otp): bool {
    $content = emailTemplate(
        "Verify Your Account - Beatwave",
        "<p>Hi <strong>{$artistName}</strong>,</p>
         <p>Use the code below to verify your account:</p>
         <div style='background:#1a1a1a;border:1px dashed #d4af37;padding:20px;text-align:center;font-size:32px;letter-spacing:10px;color:#d4af37;font-weight:bold;margin:20px 0'>
           {$otp}
         </div>"
    );
    return sendEmail($to, "🔐 Beatwave OTP: {$otp}", $content);
}

function sendWithdrawalEmail(string $to, string $artistName, float $amount, string $reference): bool {
    $content = emailTemplate(
        "Withdrawal Alert - Beatwave",
        "<p>You withdrew GHS " . number_format($amount, 2) . ". If not you, contact support immediately.</p>
         <p><strong>Reference:</strong> <code>{$reference}</code></p>"
    );
    return sendEmail($to, "Withdrawal Alert - Beatwave", $content);
}

function sendSongApprovedEmail(string $to, string $artistName, string $songTitle): bool {
    $content = emailTemplate(
        "Your Song is Live! 🎶",
        "<p>Hi <strong>{$artistName}</strong>,</p>
         <p>Your song <span class='highlight'>\"{$songTitle}\"</span> has been approved and is now live on BeatWave!</p>
         <p><a href='" . APP_URL . "/public/index.php' class='btn'>View on BeatWave</a></p>"
    );
    $content = str_replace('{$_year}', date('Y'), $content);
    return sendEmail($to, "✅ \"{$songTitle}\" is now live!", $content);
}

function sendEarningsEmail(string $to, string $artistName, float $amount, string $period): bool {
    $content = emailTemplate(
        "Earnings Summary for {$period}",
        "<p>Hi <strong>{$artistName}</strong>,</p>
         <p>Your earnings for <strong>{$period}</strong>:</p>
         <p style='font-size:32px;color:#d4af37;font-weight:bold;margin:20px 0'>" . formatMoney($amount) . "</p>
         <p><a href='" . APP_URL . "/artist/earnings.php' class='btn'>View Full Report</a></p>"
    );
    $content = str_replace('{$_year}', date('Y'), $content);
    return sendEmail($to, "💰 Your {$period} Earnings: " . formatMoney($amount), $content);
}

function sendNewSongNotificationEmail(string $to, string $songTitle, string $artistName): bool {
    $content = emailTemplate(
        "New Upload Pending Review",
        "<p>A new song requires your review:</p>
         <ul>
           <li><strong>Song:</strong> {$songTitle}</li>
           <li><strong>Artist:</strong> {$artistName}</li>
         </ul>
         <p><a href='" . APP_URL . "/admin/manage_songs.php' class='btn'>Review Now</a></p>"
    );
    $content = str_replace('{$_year}', date('Y'), $content);
    return sendEmail($to, "⬆️ New Song Upload: {$songTitle}", $content);
}

// ═════════════════════════════════════════════════════════════════════════════
//  SMS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Send an SMS via configured provider.
 *
 * @param string $phone  Recipient phone (international format: 233XXXXXXXXX)
 * @param string $message SMS body (max ~160 chars for 1 segment)
 * @return array ['success' => bool, 'response' => mixed]
 */
function sendSMS(string $phone, string $message): array {
    // Normalise Ghana numbers: 0XXXXXXXXX → 233XXXXXXXXX
    if (preg_match('/^0[0-9]{9}$/', $phone)) {
        $phone = '233' . substr($phone, 1);
    }

    $provider = SMS_PROVIDER;

    return match($provider) {
        'arkesel'  => _smsArkesel($phone, $message),
        'mnotify'  => _smsMnotify($phone, $message),
        'twilio'   => _smsTwilio($phone, $message),
        default    => ['success' => false, 'response' => 'Unknown SMS provider'],
    };
}

function _smsArkesel(string $phone, string $message): array {
    // Note: Some Arkesel accounts require Sender ID registration.
    // If you get 'Sender ID not allowed', try empty sender for generic ID or your verified name.
    $sender = SMS_SENDER_ID;
    
    $payload = [
        'sender'     => $sender,
        'message'    => $message,
        'recipients' => [$phone],
    ];

    $ch = curl_init(ARKESEL_SMS_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'api-key: ' . SMS_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        logActivity('sms_error', 'Arkesel cURL error', ['error' => $err, 'phone' => $phone]);
        return ['success' => false, 'response' => $err];
    }

    $data    = json_decode($response, true);
    $success = isset($data['status']) && strtolower($data['status']) === 'success';
    
    // Fallback attempt if sender ID is the issue
    if (!$success && isset($data['message']) && (str_contains($data['message'], 'Sender ID') || str_contains($data['message'], 'sender field'))) {
        $payload['sender'] = 'Beatwave'; // Force a default instead of empty
        $ch = curl_init(ARKESEL_SMS_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'api-key: ' . SMS_API_KEY,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        $success = isset($data['status']) && strtolower($data['status']) === 'success';
        curl_close($ch);
    }

    logActivity('sms', $success ? 'Sent via Arkesel' : 'Arkesel failed', ['phone' => $phone, 'resp' => $data]);
    return ['success' => $success, 'response' => $data];
}

function _smsMnotify(string $phone, string $message): array {
    $url = MNOTIFY_SMS_URL . '?' . http_build_query([
        'key'  => MNOTIFY_API_KEY,
        'to'   => $phone,
        'msg'  => $message,
        'sender_id' => SMS_SENDER_ID,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) return ['success' => false, 'response' => $err];

    $data    = json_decode($response, true);
    $success = isset($data['status']) && $data['status'] === '1000';
    logActivity('sms', $success ? 'Sent via mNotify' : 'mNotify failed', ['phone' => $phone]);
    return ['success' => $success, 'response' => $data];
}

function _smsTwilio(string $phone, string $message): array {
    $url     = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_SID . "/Messages.json";
    $payload = http_build_query(['To' => '+' . $phone, 'From' => TWILIO_FROM, 'Body' => $message]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => TWILIO_SID . ':' . TWILIO_TOKEN,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) return ['success' => false, 'response' => $err];

    $data    = json_decode($response, true);
    $success = isset($data['sid']);
    logActivity('sms', $success ? 'Sent via Twilio' : 'Twilio failed', ['phone' => $phone]);
    return ['success' => $success, 'response' => $data];
}

// ── Pre-built SMS templates ───────────────────────────────────────────────────
function smsSongApproved(string $phone, string $artistName, string $songTitle): array {
    $msg = "Hi {$artistName}! Your song \"{$songTitle}\" is now LIVE on BeatWave. Share & earn! - BeatWave";
    return sendSMS($phone, $msg);
}

function smsNewEarnings(string $phone, string $artistName, float $amount): array {
    $msg = "Hi {$artistName}! You've earned " . formatMoney($amount) . " on BeatWave. Login to view details. - BeatWave";
    return sendSMS($phone, $msg);
}

function smsWelcome(string $phone, string $artistName): array {
    $msg = "Welcome to BeatWave, {$artistName}! Upload your music and start earning. - BeatWave";
    return sendSMS($phone, $msg);
}

function smsSongRejected(string $phone, string $artistName, string $songTitle, string $reason = ''): array {
    $msg = "Hi {$artistName}, your song \"{$songTitle}\" was not approved." .
           ($reason ? " Reason: {$reason}." : '') . " Contact support. - BeatWave";
    return sendSMS($phone, $msg);
}
