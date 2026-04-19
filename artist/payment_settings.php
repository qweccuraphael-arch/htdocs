<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/helpers/paystack.php';
require_once dirname(__DIR__) . '/app/models/Artist.php';
requireArtist();

$artistId = currentArtistId();
$artistModel = new Artist();
$artist = $artistModel->getById($artistId);
$banks = paystack_get_banks();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bank_code = $_POST['bank_code'] ?? '';
    $account_number = $_POST['account_number'] ?? '';
    $account_name = $_POST['account_name'] ?? '';
    
    // Find bank name from code
    $bank_name = '';
    foreach ($banks as $b) {
        if ($b['code'] === $bank_code) {
            $bank_name = $b['name'];
            break;
        }
    }

    if ($bank_code && $account_number && $account_name) {
        // Create or update Paystack recipient
        $recipient_code = paystack_create_recipient($account_name, $account_number, $bank_code);
        
        if ($recipient_code) {
            $data = [
                'bank_name' => $bank_name,
                'bank_code' => $bank_code,
                'account_number' => $account_number,
                'account_name' => $account_name,
                'paystack_recipient_code' => $recipient_code
            ];
            
            if ($artistModel->updateBankDetails($artistId, $data)) {
                $msg = '✅ Payment details updated successfully!';
                $artist = $artistModel->getById($artistId); // Refresh data
            } else {
                $err = '❌ Failed to save to database.';
            }
        } else {
            $err = '❌ Could not verify details with Paystack. Please check your account number.';
        }
    } else {
        $err = '❌ All fields are required.';
    }
}

?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payment Settings – BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head><body>
<div class="panel-layout">
  <?php include '_sidebar.php'; ?>
  <main class="panel-main">
    <h1 class="panel-title">💳 Payment Settings</h1>
    <p style="color:var(--text-dim);margin-bottom:24px">Set up where you want to receive your earnings (Bank or Mobile Money).</p>

    <?php if($msg):?><div class="form-success" style="margin-bottom:16px;padding:12px;background:rgba(76,175,80,.1);border-radius:8px"><?=$msg?></div><?php endif;?>
    <?php if($err):?><div class="form-error" style="margin-bottom:16px;padding:12px;background:rgba(244,67,54,.1);border-radius:8px;color:#f44336"><?=$err?></div><?php endif;?>

    <div class="auth-card" style="margin:0;max-width:500px">
      <form method="POST">
        <div class="form-group">
          <label>Select Bank / Provider</label>
          <select name="bank_code" required class="form-control" style="background:var(--dark-2);color:#fff;border:1px solid var(--border);width:100%;padding:10px;border-radius:8px">
            <option value="">-- Select --</option>
            <?php foreach($banks as $b):?>
              <option value="<?=$b['code']?>" <?=$artist['bank_code']===$b['code']?'selected':''?>><?=$b['name']?></option>
            <?php endforeach;?>
          </select>
        </div>

        <div class="form-group">
          <label>Account / Momo Number</label>
          <input type="text" name="account_number" value="<?=htmlspecialchars($artist['account_number']??'')?>" required placeholder="e.g. 0540000000" class="form-control">
        </div>

        <div class="form-group">
          <label>Account Name</label>
          <input type="text" name="account_name" value="<?=htmlspecialchars($artist['account_name']??'')?>" required placeholder="As it appears on your account" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%">Save Payment Details</button>
      </form>
    </div>

    <?php if($artist['paystack_recipient_code']): ?>
    <div style="margin-top:24px;padding:16px;border:1px solid var(--gold);border-radius:12px;background:rgba(212,175,55,.05);max-width:500px">
      <h3 style="color:var(--gold);margin-top:0">Verified Payout Method</h3>
      <p style="font-size:14px;margin:4px 0"><strong>Bank:</strong> <?=$artist['bank_name']?></p>
      <p style="font-size:14px;margin:4px 0"><strong>Account:</strong> <?=$artist['account_number']?></p>
      <p style="font-size:14px;margin:4px 0"><strong>Name:</strong> <?=$artist['account_name']?></p>
      <div style="margin-top:8px;font-size:11px;color:var(--text-dim)">Recipient Code: <?=$artist['paystack_recipient_code']?></div>
    </div>
    <?php endif; ?>
  </main>
</div>
</body></html>
