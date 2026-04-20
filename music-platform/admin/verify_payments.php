<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/models/Artist.php';
requireAdmin();

$artistModel = new Artist();

$artists = $artistModel->getAll();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_payment'])) {
    $artistId = (int) ($_POST['artist_id'] ?? 0);
    $verified = isset($_POST['payment_verified']) ? 1 : 0;

    if ($artistModel->updatePaymentDetails($artistId, ['payment_verified' => $verified])) {
        $success = 'Payment verification updated successfully.';
        // Refresh data
        $artists = $artistModel->getAll();
    } else {
        $error = 'Failed to update payment verification.';
    }
}
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verify Payments – BeatWave Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head><body>
<div class="panel-layout">
  <?php include '_sidebar.php'; ?>
  <main class="panel-main">
    <h1 class="panel-title">✅ Verify Artist Payments</h1>

    <?php if($error):?>
    <p class="form-error" style="margin-bottom:16px;padding:10px;background:rgba(244,67,54,.1);border-radius:8px"><?=$error?></p>
    <?php endif;?>
    <?php if($success):?>
    <p style="margin-bottom:16px;padding:10px;background:rgba(76,175,80,.1);border-radius:8px;color:#4caf50"><?=$success?></p>
    <?php endif;?>

    <div style="background:var(--dark-2);border:1px solid var(--border);border-radius:16px;padding:24px">
      <h3 style="margin-bottom:16px">Artist Payment Details</h3>
      
      <?php if(empty($artists)):?>
      <p style="color:var(--text-dim)">No artists found.</p>
      <?php else:?>
      <div style="overflow-x:auto">
        <table class="table">
          <thead>
            <tr>
              <th>Artist</th>
              <th>Payment Method</th>
              <th>Details</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($artists as $artist):?>
            <?php $payment = $artistModel->getPaymentDetails($artist['id']); ?>
            <tr>
              <td>
                <div style="font-weight:600"><?=htmlspecialchars($artist['name'])?></div>
                <div style="font-size:12px;color:var(--text-dim)"><?=htmlspecialchars($artist['email'])?></div>
              </td>
              <td>
                <?php if($payment && $payment['payment_method'] !== 'none'):?>
                  <?=ucfirst(str_replace('_', ' ', $payment['payment_method']))?>
                <?php else:?>
                  <span style="color:var(--text-dim)">Not set</span>
                <?php endif;?>
              </td>
              <td>
                <?php if($payment && $payment['payment_method'] === 'bank'):?>
                  <div style="font-size:12px">
                    Bank: <?=htmlspecialchars($payment['bank_name'] ?? '')?><br>
                    Account: <?=htmlspecialchars($payment['account_number'] ?? '')?><br>
                    Name: <?=htmlspecialchars($payment['account_name'] ?? '')?>
                  </div>
                <?php elseif($payment && $payment['payment_method'] === 'mobile_money'):?>
                  <div style="font-size:12px">
                    Network: <?=htmlspecialchars($payment['mobile_network'] ?? '')?><br>
                    Number: <?=htmlspecialchars($payment['mobile_number'] ?? '')?>
                  </div>
                <?php elseif($payment && $payment['payment_method'] === 'paypal'):?>
                  <div style="font-size:12px">
                    Email: <?=htmlspecialchars($payment['paypal_email'] ?? '')?>
                  </div>
                <?php else:?>
                  <span style="color:var(--text-dim)">No details</span>
                <?php endif;?>
              </td>
              <td>
                <?php if($payment && $payment['payment_verified']):?>
                  <span style="color:#4caf50">✓ Verified</span>
                <?php elseif($payment && $payment['payment_method'] !== 'none'):?>
                  <span style="color:#ffc107">⏳ Pending</span>
                <?php else:?>
                  <span style="color:var(--text-dim)">Not set</span>
                <?php endif;?>
              </td>
              <td>
                <?php if($payment && $payment['payment_method'] !== 'none'):?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="artist_id" value="<?=$artist['id']?>">
                  <input type="hidden" name="verify_payment" value="1">
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" 
                           name="payment_verified" 
                           value="1" 
                           <?=($payment['payment_verified'] ?? 0) ? 'checked' : ''?>
                           onchange="this.form.submit()">
                    Verify
                  </label>
                </form>
                <?php endif;?>
              </td>
            </tr>
            <?php endforeach;?>
          </tbody>
        </table>
      </div>
      <?php endif;?>
    </div>
  </main>
</div>
</body></html>