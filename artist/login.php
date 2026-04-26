<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/controllers/ArtistController.php';
startSession();
if (isArtistLoggedIn()) { header('Location: dashboard.php'); exit; }
$ctrl = new ArtistController(); $msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    // Handle Google Login separately (it has its own security)
    if (isset($_POST['google_credential'])) {
        $r = $ctrl->loginWithGoogle($_POST['google_credential']);
        $r['success'] ? header('Location: dashboard.php') && exit : $err = $r['error'];
    } elseif (!verifyToken($csrf)) {
        $err = 'Invalid security token.';
    } else {
        $tab = $_POST['tab'] ?? 'login';
        if ($tab === 'login') {
            $r = $ctrl->login($_POST['email']??'', $_POST['password']??'');
            if ($r['success']) {
                header('Location: dashboard.php');
                exit;
            } elseif (isset($r['needs_verification'])) {
                $_SESSION['verify_email'] = $r['email'];
                header('Location: verify.php');
                exit;
            } else {
                $err = $r['error'];
            }
        } else {
            $r = $ctrl->register($_POST, $_FILES);
            if ($r['success']) {
                $_SESSION['verify_email'] = $r['email'];
                header('Location: verify.php');
                exit;
            } else {
                $err = $r['error'];
            }
        }
    }
}
$msg = $_SESSION['verify_msg'] ?? '';
unset($_SESSION['verify_msg']);
$csrf_token = setCSRF();
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Artist Portal – BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
<script src="https://accounts.google.com/gsi/client" async defer></script>
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
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
      <input type="hidden" name="tab" value="login">
      <div class="form-group"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Sign In</button>
      <div style="margin-top:20px;text-align:center;font-size:12px;border-top:1px solid var(--border);padding-top:16px">
        <a href="forgot-password.php" style="color:var(--gold);text-decoration:none;font-weight:500;">Forgot Password?</a>
      </div>
    </form>

    <!-- REGISTER -->
    <form method="POST" id="form-register" style="display:none" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
      <input type="hidden" name="tab" value="register">
      <div class="form-group"><label class="form-label">Full Name *</label><input name="name" type="text" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Email *</label><input name="email" type="email" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Phone (Ghana: 024XXXXXXX)</label><input name="phone" type="tel" class="form-control" placeholder="0249740636"></div>
      <div class="form-group"><label class="form-label">Password * (6+ chars)</label><input name="password" type="password" class="form-control" required minlength="6"></div>
      <div class="form-group"><label class="form-label">Bio</label><textarea name="bio" class="form-control" rows="2"></textarea></div>
      <div class="form-group"><label class="form-label">Profile Photo</label><input name="photo" type="file" class="form-control" accept="image/*"></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Create Account</button>
    </form>

    <div style="text-align:center;margin:24px 0 16px;font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:10px">
      <div style="flex:1;height:1px;background:var(--border)"></div>
      OR
      <div style="flex:1;height:1px;background:var(--border)"></div>
    </div>

    <!-- Google One Tap / Button -->
    <?php if (strpos(GOOGLE_CLIENT_ID, 'YOUR_GOOGLE_CLIENT_ID') === false): ?>
    <div id="g_id_onload"
         data-client_id="<?= GOOGLE_CLIENT_ID ?>"
         data-context="signin"
         data-ux_mode="popup"
         data-callback="handleGoogleResponse"
         data-auto_prompt="false">
    </div>
    <div class="g_id_signin"
         data-type="standard"
         data-shape="rectangular"
         data-theme="outline"
         data-text="continue_with"
         data-size="large"
         data-logo_alignment="left"
         style="display:flex;justify-content:center">
    </div>
    <?php else: ?>
      <div style="text-align:center;padding:15px;background:rgba(212,175,55,.05);border:1px dashed var(--gold);border-radius:12px;font-size:12px;color:var(--text-muted)">
        Google Sign-in is currently being configured. <br>Please use your Email/Password.
      </div>
    <?php endif; ?>

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

function handleGoogleResponse(response) {
  // Submit the credential to our PHP backend
  const form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = `<input type="hidden" name="google_credential" value="${response.credential}">`;
  document.body.appendChild(form);
  form.submit();
}
</script>
</body></html>
