<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/models/Earnings.php';
require_once dirname(__DIR__) . '/app/controllers/EarningsController.php';
requireAdmin();

$earningsModel = new Earnings();
$ctrl = new EarningsController();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_reports') {
    $ctrl->sendMonthlyReports();
    $msg = 'Monthly earnings reports sent to all artists via email and SMS.';
}

$adminTotal = $earningsModel->adminTotal();
$adminToday = $earningsModel->adminTotal('today');

// Handle Admin Withdrawal
$adminErr = '';
$adminSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_withdraw'])) {
    $amount = (float) ($_POST['amount'] ?? 0);
    $method = $_POST['payment_method'] ?? 'mobile_money';
    $network = $_POST['mobile_network'] ?? '';
    $phone = $_POST['mobile_number'] ?? '';

    if ($amount <= 0 || $amount > $adminTotal) {
        $adminErr = 'Invalid withdrawal amount.';
    } elseif (empty($phone) || empty($network)) {
        $adminErr = 'Please provide mobile money details.';
    } else {
        require_once dirname(__DIR__) . '/app/helpers/payments.php';
        try {
            // Create recipient for admin
            $recipientData = [
                'name' => 'Admin Payout',
                'payment_method' => 'mobile_money',
                'mobile_number' => $phone,
                'mobile_network' => $network
            ];
            $recipientCode = paystackCreateRecipient($recipientData);
            
            if ($recipientCode) {
                $res = paystackInitiateTransfer($amount, $recipientCode);
                if ($res['success']) {
                    // Record admin payout in database (using a negative earning or a manual entry)
                    $earningsModel->record(0, 0, -$amount, 'manual', 'admin');
                    $adminSuccess = 'Admin payout of ' . formatMoney($amount) . ' initiated successfully!';
                    $adminTotal = $earningsModel->adminTotal();
                } else {
                    $adminErr = 'Transfer failed: ' . $res['message'];
                }
            } else {
                $adminErr = 'Could not register payout recipient with Paystack. Check your API Key.';
            }
        } catch (Exception $e) {
            $adminErr = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Earnings - BeatWave Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<div class="panel-layout">
<?php include '_sidebar.php'; ?>
<main class="panel-main">
<h1 class="panel-title">Earnings & Payouts</h1>
<?php if ($msg): ?><div class="form-success" style="padding:10px;background:rgba(76,175,80,.1);border-radius:8px;margin-bottom:16px"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="stats-grid" style="max-width:900px">
  <div class="stat-card" style="background:var(--gold);color:#000"><div class="stat-icon">💰</div><div class="stat-value"><?= formatMoney($adminTotal) ?></div><div class="stat-label" style="color:#000">Admin Balance</div></div>
  <div class="stat-card"><div class="stat-icon">📈</div><div class="stat-value"><?= formatMoney($adminToday) ?></div><div class="stat-label">Admin Today</div></div>
  <div class="stat-card"><div class="stat-icon">🎵</div><div class="stat-value"><?= formatMoney($artistGross) ?></div><div class="stat-label">Artist Total Gross</div></div>
</div>

<div style="display:grid;grid-template-columns: 1.5fr 1fr; gap:24px; margin-top:24px">
  <div>
    <h3 style="margin-bottom:16px">Artists Summary</h3>
    <div class="table-wrap"><table class="data-table">
    <thead><tr><th>Artist</th><th>Downloads</th><th>Total Earned</th></tr></thead>
    <tbody>
    <?php foreach ($summary as $row): ?>
    <tr>
      <td style="font-weight:500"><?= htmlspecialchars($row['name']) ?></td>
      <td><?= formatNumber((int) $row['total_downloads']) ?></td>
      <td style="color:var(--gold);font-weight:600"><?= formatMoney((float) $row['total_earnings']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div>
  </div>

  <div>
    <div class="card" style="background:var(--dark-2); border:1px solid var(--border)">
      <h3 style="margin-bottom:16px">Admin Withdrawal</h3>
      <?php if ($adminErr): ?><div class="alert alert-error" style="margin-bottom:12px;font-size:13px"><?= htmlspecialchars($adminErr) ?></div><?php endif; ?>
      <?php if ($adminSuccess): ?><div class="alert alert-success" style="margin-bottom:12px;font-size:13px"><?= htmlspecialchars($adminSuccess) ?></div><?php endif; ?>
      
      <form method="POST">
        <div class="form-group">
          <label>Withdraw Amount</label>
          <input name="amount" type="number" step="0.01" class="form-control" value="<?= $adminTotal ?>" max="<?= $adminTotal ?>" required>
        </div>
        <div class="form-group">
          <label>Mobile Network</label>
          <select name="mobile_network" class="form-control" required>
            <option value="mtn">MTN</option>
            <option value="vodafone">Vodafone</option>
            <option value="airteltigo">AirtelTigo</option>
          </select>
        </div>
        <div class="form-group">
          <label>Mobile Number</label>
          <input name="mobile_number" type="tel" class="form-control" placeholder="024XXXXXXX" required>
        </div>
        <button type="submit" name="admin_withdraw" class="btn btn-primary" style="width:100%; justify-content:center">Withdraw to MoMo</button>
      </form>
    </div>

    <div style="margin-top:24px">
      <form method="POST">
        <input type="hidden" name="action" value="send_reports">
        <button class="btn btn-secondary" style="width:100%; justify-content:center" data-confirm="Send monthly earnings reports?">📧 Send Monthly Reports</button>
      </form>
    </div>
  </div>
</div>
</main></div>
<script src="../public/assets/js/main.js"></script>
</body></html>
