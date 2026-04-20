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

$token = sanitize($_GET['token'] ?? '');
$admin = null;
$error = '';
$message = '';
$showForm = true;

if (empty($token)) {
    $error = 'No reset token provided.';
    $showForm = false;
} else {
    $admin = adminValidateResetToken($token);
    if (!$admin) {
        $error = 'Invalid or expired token. Please request a new reset link.';
        $showForm = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin) {
    generateCsrfToken();
    
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        
        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (adminResetPassword($admin['id'], $password)) {
            $message = 'Password reset successful! You can now <a href="login.php">sign in</a> with your new password.';
            $showForm = false;
            logActivity('reset_complete', 'Admin reset completed', ['admin_id' => $admin['id']]);
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password – BeatWave Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">🔐 Reset Password</div>
    
    <?php if ($message): ?>
      <div style="padding:20px;background:rgba(76,175,80,.1);border-radius:8px;border-left:4px solid #4caf50;text-align:center;">
        <h3 style="color:#4caf50;margin-top:0;">Success!</h3>
        <?= $message ?>
      </div>
    <?php elseif ($error): ?>
      <div class="form-error" style="padding:16px;background:rgba(244,67,54,.1);border-radius:8px;border-left:4px solid #f44336;margin-bottom:20px;">
        <?= htmlspecialchars($error) ?>
      </div>
      <div style="text-align:center;">
        <a href="forgot-password.php" class="btn btn-primary" style="background:var(--gold);color:#000;">Request New Link</a>
      </div>
    <?php elseif ($showForm): ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="form-group">
          <label class="form-label">New Password (8+ chars)</label>
          <input name="password" type="password" class="form-control" required minlength="8" autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input name="confirm" type="password" class="form-control" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">
          Reset Password
        </button>
      </form>
      
      <div style="margin-top:20px;text-align:center;font-size:12px;color:var(--text-muted);">
        <a href="login.php">← Back to Sign In</a>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>

