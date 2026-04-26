<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/security.php';
startSession();
if (isAdminLoggedIn()) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = sanitize($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    
    // Allow login by username OR email
    $stmt = getDB()->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
    $stmt->execute([$u, $u]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($p, $admin['password'])) {
        adminLogin($admin['id'], $admin['username']);
        header('Location: index.php'); 
        exit;
    }
    $error = 'Invalid username/email or password.';
    logActivity('admin_login_failed', "Failed login attempt for: $u");
}
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login – BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head><body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">🎵 BeatWave Admin</div>
    <?php if($error):?><p class="form-error" style="margin-bottom:16px;padding:10px;background:rgba(244,67,54,.1);border-radius:8px"><?=$error?></p><?php endif;?>
    <form method="POST">
      <div class="form-group"><label class="form-label">Username</label><input name="username" type="text" class="form-control" required autofocus></div>
      <div class="form-group"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Sign In</button>
    </form>
    <div style="margin-top:24px;text-align:center;font-size:12px;border-top:1px solid #eee;padding-top:16px">
      <a href="forgot-password.php" style="color:var(--gold);text-decoration:none;font-weight:500;">Forgot Password?</a>
    </div>
  </div>
</div></body></html>
