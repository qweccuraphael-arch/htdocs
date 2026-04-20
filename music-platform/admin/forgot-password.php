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
    generateCsrfToken();  // Regenerate for safety
    
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } elseif (!rateLimitCheck('admin_forgot_submit_' . getClientIP(), 5, 3600)) {
        $error = 'Too many requests. Please wait 1 hour.';
    } else {
        $identifier = sanitize(trim($_POST['identifier'] ?? ''));
        if (empty($identifier)) {
            $error = 'Please enter your email or username.';
        } elseif (adminSendResetEmail($identifier)) {
    $message = 'Reset link sent! Check your email (including spam folder). Link expires in 1 hour.';
        } else {
            $error = 'Service temporarily unavailable. Please try again later.';
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
<title>Forgot Password – BeatWave Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
<style>
  .link-btn { background: var(--gold) !important; color: #000 !important; border: none; }
  .back-link { color: var(--gold); text-decoration: none; font-weight: 500; }
  .back-link:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">🔐 Forgot Password?</div>
    
    <?php if ($message): ?>
      <div class="form-success" style="padding:16px;background:rgba(76,175,80,.1);border-radius:8px;margin-bottom:20px;border-left:4px solid #4caf50;">
        <?= htmlspecialchars($message) ?>
      </div>
      <div style="text-align:center;padding:20px 0;">
        <a href="login.php" class="btn link-btn">← Back to Login</a>
      </div>
    <?php else: ?>
      <?php if ($error): ?>
        <p class="form-error" style="margin-bottom:16px;padding:10px;background:rgba(244,67,54,.1);border-radius:8px;border-left:4px solid #f44336;">
          <?= htmlspecialchars($error) ?>
        </p>
      <?php endif; ?>
      
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="form-group">
          <label class="form-label">Email or Username</label>
          <input name="identifier" type="text" class="form-control" 
                 placeholder="admin@yourdomain.com or admin" required 
                 value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" autofocus>
          <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">
            We'll send a secure reset link
          </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Send Reset Link</button>
      </form>
      
      <div style="margin-top:24px;text-align:center;font-size:12px;color:var(--text-muted);border-top:1px solid var(--border);padding-top:20px;">
        <a href="login.php" class="back-link">← Back to Sign In</a>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>

