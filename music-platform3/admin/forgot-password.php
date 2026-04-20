<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/security.php';

startSession();
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyToken($csrf_token)) {
        $error = 'Invalid security token.';
    } elseif (!rateLimitCheck('admin_forgot_' . getClientIP(), 20, 14400)) { // Increased for recovery: 20/4hrs
        $error = 'Too many requests. Wait 1 hour.';
    } else {
        $identifier = sanitize($_POST['identifier'] ?? '');
        if (adminSendResetEmail($identifier)) {
            $message = 'Reset link sent to your email! Check spam if not seen.';
        } else {
            $error = 'No account found or send failed. Check email/username.';
        }
    }
}
$csrf = setCSRF();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password – BeatWave Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">🔐 Forgot Password</div>
    
    <?php if ($message): ?>
      <p class="form-success" style="background:rgba(76,175,80,.1);border-left:4px solid #4caf50;padding:12px;border-radius:8px;margin-bottom:20px;">
        <?= htmlspecialchars($message) ?>
      </p>
      <div style="text-align:center">
        <a href="login.php" class="btn btn-primary">Back to Login</a>
      </div>
    <?php else: ?>
      <?php if ($error): ?>
        <p class="form-error" style="background:rgba(244,67,54,.1);border-left:4px solid #f44336;padding:12px;border-radius:8px;">
          <?= htmlspecialchars($error) ?>
        </p>
      <?php endif; ?>
      
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <div class="form-group">
          <label class="form-label">Username or Email</label>
          <input type="text" name="identifier" class="form-control" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
      </form>
      
      <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--text-muted);border-top:1px solid var(--border);padding-top:16px;">
        <a href="login.php">← Back to Sign In</a>
      </p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>

