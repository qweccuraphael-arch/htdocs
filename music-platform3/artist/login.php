<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/controllers/ArtistController.php';
startSession();
if (isArtistLoggedIn()) { header('Location: dashboard.php'); exit; }
$ctrl = new ArtistController(); $msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = $_POST['tab'] ?? 'login';
    if ($tab === 'login') {
        $r = $ctrl->login($_POST['email']??'', $_POST['password']??'');
        $r['success'] ? header('Location: dashboard.php') && exit : $err = $r['error'];
    } else {
        $r = $ctrl->register($_POST, $_FILES);
        $r['success'] ? $msg='Account created! Please login.' : $err=$r['error'];
    }
}
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Artist Portal – BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head><body>
<div class="login-wrap">
  <div class="login-card" style="max-width:480px">
    <div class="login-logo">🎤 Artist Portal</div>
    <?php if($msg):?><div class="form-success" style="padding:10px;background:rgba(76,175,80,.1);border-radius:8px;margin-bottom:16px"><?=$msg?></div><?php endif;?>
    <?php if($err):?><div class="form-error" style="padding:10px;background:rgba(244,67,54,.1);border-radius:8px;margin-bottom:16px"><?=$err?></div><?php endif;?>

    <div style="display:flex;border-bottom:1px solid var(--border);margin-bottom:24px">
      <button onclick="switchTab('login')" id="tab-login" style="flex:1;padding:10px;background:none;border:none;color:var(--gold);font-family:var(--font-head);font-weight:700;border-bottom:2px solid var(--gold)">Login</button>
      <button onclick="switchTab('register')" id="tab-register" style="flex:1;padding:10px;background:none;border:none;color:var(--text-muted);font-family:var(--font-head);font-weight:700;border-bottom:2px solid transparent">Register</button>
    </div>

    <!-- LOGIN -->
    <form method="POST" id="form-login">
      <input type="hidden" name="tab" value="login">
      <div class="form-group"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Sign In</button>
    </form>
    <div style="margin-top:20px;text-align:center;font-size:12px;border-top:1px solid #eee;padding-top:16px">
      <a href="forgot-password.php" style="color:var(--gold);text-decoration:none;font-weight:500;">Forgot Password?</a>
    </div>

    <!-- REGISTER -->
    <form method="POST" id="form-register" style="display:none" enctype="multipart/form-data">
      <input type="hidden" name="tab" value="register">
      <div class="form-group"><label class="form-label">Full Name *</label><input name="name" type="text" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Email *</label><input name="email" type="email" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Phone (Ghana: 024XXXXXXX)</label><input name="phone" type="tel" class="form-control" placeholder="0249740636"></div>
      <div class="form-group"><label class="form-label">Password * (6+ chars)</label><input name="password" type="password" class="form-control" required minlength="6"></div>
      <div class="form-group"><label class="form-label">Bio</label><textarea name="bio" class="form-control" rows="2"></textarea></div>
      <div class="form-group"><label class="form-label">Profile Photo</label><input name="photo" type="file" class="form-control" accept="image/*"></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Create Account</button>
    </form>

    <p style="text-align:center;margin-top:16px;font-size:13px;color:var(--text-muted)"><a href="../public/index.php" style="color:var(--gold)">← Back to BeatWave</a></p>
  </div>
</div>
<script>
function switchTab(t){
  document.getElementById('form-login').style.display=t==='login'?'block':'none';
  document.getElementById('form-register').style.display=t==='register'?'block':'none';
  document.getElementById('tab-login').style.cssText='flex:1;padding:10px;background:none;border:none;font-family:var(--font-head);font-weight:700;border-bottom:2px solid '+(t==='login'?'var(--gold)':'transparent')+';color:'+(t==='login'?'var(--gold)':'var(--text-muted)');
  document.getElementById('tab-register').style.cssText='flex:1;padding:10px;background:none;border:none;font-family:var(--font-head);font-weight:700;border-bottom:2px solid '+(t==='register'?'var(--gold)':'transparent')+';color:'+(t==='register'?'var(--gold)':'var(--text-muted)');
}
</script>
</body></html>
