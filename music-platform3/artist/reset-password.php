<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/security.php';

startSession();
if (isArtistLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$token = sanitize($_GET['token'] ?? '');
$error = $message = '';

if (empty($token)) {
    $error = 'No token provided.';
} else {
    $artist = artistValidateResetToken($token);
    if (!$artist) {
        $error = 'Invalid/expired token. Request new link.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf = $_POST['csrf_token'] ?? '';
        $pass = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        
        if (!verifyToken($csrf)) {
            $error = 'Invalid token.';
        } elseif (strlen($pass) < 8) {
            $error = 'Password must be 8+ chars.';
        } elseif ($pass !== $confirm) {
            $error = 'Passwords mismatch.';
        } elseif (artistResetPassword($artist['id'], $pass)) {
            $message = 'Password updated! <a href="login.php">Sign in now</a>.';
        } else {
            $error = 'Update failed.';
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
<title>Reset Password – BeatWave Artist</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">🔐 Reset Password</div>
    
    <?php if ($message): ?>
      <div class="form-success" style="background:rgba(76,175,80,.1);border-left:4px solid #4caf50;padding:12px;border-radius:8px;">
        <?= $message ?>
      </div>
    <?php elseif ($error): ?>
      <div class="form-error" style="background:rgba(244,67,54,.1);border-left:4px solid #f44336;padding:12px;border-radius:8px;">
        <?= htmlspecialchars($error) ?>
      </div>
      <p style="text-align:center;margin-top:20px;"><a href="forgot-password.php" class="btn btn-primary">New Reset Link</a></p>
    <?php else: ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <div class="form-group">
          <label class="form-label">New Password</label>
          <input type="password" name="password" class="form-control" required minlength="8" autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm" class="form-control" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary w-100">Update Password</button>
      </form>
      <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--text-muted);">
        <a href="login.php">← Back to Login</a>
      </p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>

