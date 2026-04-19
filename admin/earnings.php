<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/models/Earnings.php';
require_once dirname(__DIR__) . '/app/controllers/EarningsController.php';
requireAdmin();
$earningsModel = new Earnings();
$ctrl          = new EarningsController();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'send_reports') {
    $ctrl->sendMonthlyReports();
    $msg = '✅ Monthly earnings reports sent to all artists via email & SMS.';
}

$summary  = $earningsModel->getAllArtistsSummary();
$platform = $earningsModel->platformTotal();
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Earnings – BeatWave Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head><body>
<div class="panel-layout">
<?php include '_sidebar.php'; ?>
<main class="panel-main">
<h1 class="panel-title">💰 Earnings</h1>
<?php if($msg):?><div class="form-success" style="padding:10px;background:rgba(76,175,80,.1);border-radius:8px;margin-bottom:16px"><?=$msg?></div><?php endif;?>

<div class="stats-grid" style="max-width:400px">
  <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value"><?=formatMoney($platform)?></div><div class="stat-label">Platform Total</div></div>
  <div class="stat-card"><div class="stat-icon">🎤</div><div class="stat-value"><?=count($summary)?></div><div class="stat-label">Artists</div></div>
</div>

<div style="margin-bottom:20px">
  <form method="POST" style="display:inline">
    <input type="hidden" name="action" value="send_reports">
    <button class="btn btn-primary" data-confirm="Send monthly earnings reports to ALL artists via email and SMS?">📧 Send Monthly Reports</button>
  </form>
  <span style="font-size:13px;color:var(--text-muted);margin-left:12px">Sends email + SMS to all artists with earnings this month</span>
</div>

<div class="table-wrap"><table class="data-table">
<thead><tr><th>Artist</th><th>Email</th><th>Songs</th><th>Downloads</th><th>Total Earned</th></tr></thead>
<tbody>
<?php foreach($summary as $row):?>
<tr>
  <td style="font-weight:500"><?=htmlspecialchars($row['name'])?></td>
  <td style="color:var(--text-muted)"><?=htmlspecialchars($row['email'])?></td>
  <td><?=$row['song_count']?></td>
  <td><?=formatNumber($row['total_downloads'])?></td>
  <td style="color:var(--gold);font-weight:600"><?=formatMoney($row['total_earnings'])?></td>
</tr>
<?php endforeach;?>
</tbody></table></div>
</main></div>
<script src="../public/assets/js/main.js"></script>
</body></html>
