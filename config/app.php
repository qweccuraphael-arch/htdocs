<?php
// config/app.php

define('APP_NAME',    'BeatWave');
define('APP_URL',     'http://localhost/music-platform3');
define('APP_VERSION', '1.0.0');

// Storage
define('STORAGE_PATH', dirname(__DIR__) . '/storage/music/');
define('LOG_PATH',     dirname(__DIR__) . '/storage/logs/');

// Session
define('SESSION_LIFETIME', 3600); // 1 hour

// Pagination
define('SONGS_PER_PAGE', 20);

// Download earnings (per download in GHS)
define('DOWNLOAD_REVENUE_TOTAL', 2.00);
define('ARTIST_EARNINGS_PER_DOWNLOAD', 1.50);
define('EARNINGS_PER_DOWNLOAD', ARTIST_EARNINGS_PER_DOWNLOAD);
define('ADMIN_EARNINGS_PER_DOWNLOAD', DOWNLOAD_REVENUE_TOTAL - ARTIST_EARNINGS_PER_DOWNLOAD);

// ── EMAIL (SMTP via PHPMailer or mail()) ──────────────────────────────────────
define('MAIL_FROM',       'noreply@yourdomain.com');
define('MAIL_FROM_NAME',  APP_NAME);
define('ADMIN_ALERT_EMAIL', 'admin@yourdomain.com');
define('SMTP_HOST',       'smtp.gmail.com');
define('SMTP_PORT',       587);
define('SMTP_USER',       'your@gmail.com');
define('SMTP_PASS',       'your_app_password');
define('SMTP_ENCRYPTION', 'tls');

// ── SMS (Arkesel — popular in Ghana) ─────────────────────────────────────────
define('SMS_PROVIDER',    'arkesel');   // 'arkesel' | 'mnotify' | 'twilio'
define('SMS_API_KEY',     'UkdzQXl1QW5sTHRteUtkYUpZblA');
define('SMS_SENDER_ID',   'BeatWave'); // max 11 chars
define('ADMIN_ALERT_PHONE', '233249740636');

// ── Arkesel endpoint ──────────────────────────────────────────────────────────
define('ARKESEL_SMS_URL', 'https://sms.arkesel.com/api/v2/sms/send');

// ── mNotify (alternative) ─────────────────────────────────────────────────────
define('MNOTIFY_API_KEY', 'YOUR_MNOTIFY_KEY');
define('MNOTIFY_SMS_URL', 'https://apps.mnotify.net/smsapi');

// ── Twilio (international fallback) ──────────────────────────────────────────
define('TWILIO_SID',   'YOUR_TWILIO_SID');
define('TWILIO_TOKEN', 'YOUR_TWILIO_TOKEN');
define('TWILIO_FROM',  '+1XXXXXXXXXX');

// ── Paystack (Payments & Payouts) ───────────────────────────────────────────
define('PAYSTACK_SECRET_KEY', 'sk_test_60669c0bce81d5dc27a92feec095d2f6f5c9625d');
define('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co/transaction/initialize');
define('PAYSTACK_TRANSFER_URL', 'https://api.paystack.co/transfer');

// Timezone
date_default_timezone_set('Africa/Accra');
