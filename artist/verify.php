<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/controllers/ArtistController.php';

startSession();

$email = $_GET['email'] ?? $_SESSION['verify_email'] ?? '';
if (!$email) {
    header('Location: login.php');
    exit;
}

$ctrl = new ArtistController();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'verify';
    
    if ($action === 'verify') {
        $otp = $_POST['otp'] ?? '';
        $r = $ctrl->verifyOTP($email, $otp);
        if ($r['success']) {
            $_SESSION['verify_msg'] = $r['message'];
            header('Location: login.php');
            exit;
        } else {
            $err = $r['error'];
        }
    } elseif ($action === 'resend') {
        $r = $ctrl->resendOTP($email);
        if ($r['success']) {
            $msg = $r['message'];
        } else {
            $err = $r['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verify Email – BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
<style>
  .otp-input {
    letter-spacing: 12px;
    font-size: 24px;
    text-align: center;
    font-weight: 800;
    color: var(--gold);
    text-transform: uppercase;
  }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card" style="max-width:400px">
    <div class="login-logo">🔐 Verify Email</div>
    <p style="text-align:center;font-size:14px;color:var(--text-muted);margin-bottom:24px">
      We sent a 6-digit code to <br><strong><?= htmlspecialchars($email) ?></strong>
    </p>

    <?php if($msg): ?><div class="form-success" style="padding:10px;background:rgba(76,175,80,.1);border-radius:8px;margin-bottom:16px"><?=$msg?></div><?php endif; ?>
    <?php if($err): ?><div class="form-error" style="padding:10px;background:rgba(244,67,54,.1);border-radius:8px;margin-bottom:16px;color:#f44336"><?=$err?></div><?php endif; ?>

    <form method="POST">
      <input type="hidden" name="action" value="verify">
      <div class="form-group">
        <label class="form-label">Enter 6-Digit OTP</label>
        <input name="otp" type="text" class="form-control otp-input" maxlength="6" pattern="\d{6}" required autofocus placeholder="000000">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Verify Account</button>
    </form>

    <div style="margin-top:24px;text-align:center;font-size:13px;border-top:1px solid var(--border);padding-top:16px">
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="resend">
        <button type="submit" style="background:none;border:none;color:var(--gold);cursor:pointer;font-weight:500">Didn't get code? Resend</button>
      </form>
      <br><br>
      <a href="login.php" style="color:var(--text-dim);text-decoration:none">← Back to Login</a>
    </div>
  </div>
</div>
</body>
</html>
