<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/models/Earnings.php';
require_once dirname(__DIR__) . '/app/models/Payout.php';
require_once dirname(__DIR__) . '/app/models/Artist.php';
require_once dirname(__DIR__) . '/app/helpers/paystack.php';
requireArtist();

$artistId      = currentArtistId();
$earningsModel = new Earnings();
$payoutModel   = new Payout();
$artistModel   = new Artist();

$artist = $artistModel->getById($artistId);
$period = $_GET['period'] ?? 'all';

$msg = '';
$err = '';

// Handle Payout Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_payout') {
    $amount = (float)($_POST['amount'] ?? 0);
    $totalEarned = $earningsModel->totalByArtist($artistId, 'all');
    
    // Calculate already paid
    $payouts = $payoutModel->getByArtist($artistId);
    $totalPaid = 0;
    foreach($payouts as $p) {
        if ($p['status'] !== 'failed') $totalPaid += $p['amount'];
    }
    
    $balance = $totalEarned - $totalPaid;

    if ($amount < 10) {
        $err = '❌ Minimum payout is GHS 10.00';
    } elseif ($amount > $balance) {
        $err = '❌ Insufficient balance.';
    } elseif (!$artist['paystack_recipient_code']) {
        $err = '❌ Please set up your Payment Settings first.';
    } else {
        $reference = 'BW-' . strtoupper(uniqid());
        $payoutId = $payoutModel->create([
            'artist_id' => $artistId,
            'amount' => $amount,
            'reference' => $reference
        ]);

        if ($payoutId) {
            $resp = paystack_initiate_transfer($amount, $artist['paystack_recipient_code'], $reference);
            if ($resp['status']) {
                $payoutModel->updateStatus($reference, 'processing', $resp['data']['transfer_code']);
                $msg = '✅ Payout initiated! It will be processed shortly.';
                
                // Send Notifications
                if (!empty($artist['phone'])) {
                    sendSMS($artist['phone'], "You withdrew GHS " . number_format($amount, 2) . ". If not you, contact support immediately. - Beatwave");
                }
                sendWithdrawalEmail($artist['email'], $artist['name'], $amount, $reference);
            } else {
                $payoutModel->updateStatus($reference, 'failed');
                $err = '❌ Paystack Error: ' . ($resp['message'] ?? 'Unknown error');
            }
        }
    }
}

$totals  = [
    'today' => $earningsModel->totalByArtist($artistId, 'today'),
    'week'  => $earningsModel->totalByArtist($artistId, 'week'),
    'month' => $earningsModel->totalByArtist($artistId, 'month'),
    'all'   => $earningsModel->totalByArtist($artistId, 'all'),
];

$payouts = $payoutModel->getByArtist($artistId);
$totalPaidOut = 0;
foreach($payouts as $p) {
    if ($p['status'] !== 'failed') $totalPaidOut += $p['amount'];
}
$currentBalance = $totals['all'] - $totalPaidOut;

$records = $earningsModel->getByArtist($artistId, $period);
$chart   = $earningsModel->getMonthlyChart($artistId);
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Earnings – BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head><body>
<div class="panel-layout">
  <?php include '_sidebar.php'; ?>
  <main class="panel-main">
    <h1 class="panel-title">💰 My Earnings</h1>

    <?php if($msg):?><div class="form-success" style="padding:12px;background:rgba(76,175,80,.1);border-radius:8px;margin-bottom:16px"><?=$msg?></div><?php endif;?>
    <?php if($err):?><div class="form-error" style="padding:12px;background:rgba(244,67,54,.1);border-radius:8px;margin-bottom:16px;color:#f44336"><?=$err?></div><?php endif;?>

    <!-- Balance & Withdrawal -->
    <div class="earnings-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;margin-bottom:28px">
      <div style="background:linear-gradient(135deg,#d4af37,#f5c842);padding:24px;border-radius:16px;color:#000">
        <div style="font-size:14px;font-weight:500;opacity:.8">Current Balance</div>
        <div style="font-size:36px;font-weight:800;margin:8px 0"><?=formatMoney($currentBalance)?></div>
        <div style="font-size:12px;opacity:.7">Total Earned: <?=formatMoney($totals['all'])?> | Paid Out: <?=formatMoney($totalPaidOut)?></div>
      </div>

      <div style="background:var(--dark-2);border:1px solid var(--border);padding:24px;border-radius:16px">
        <h3 style="margin-top:0;font-size:16px">Request Payout</h3>
        <form method="POST">
          <input type="hidden" name="action" value="request_payout">
          <div style="display:flex;gap:8px">
            <input type="number" name="amount" step="0.01" min="10" max="<?=max(0,$currentBalance)?>" value="<?=max(0,$currentBalance)?>" required 
                   style="flex:1;background:#000;border:1px solid var(--border);color:#fff;padding:10px;border-radius:8px">
            <button type="submit" class="btn btn-primary" <?= ($currentBalance < 10 || !$artist['paystack_recipient_code']) ? 'disabled' : '' ?>>Withdraw</button>
          </div>
          <?php if(!$artist['paystack_recipient_code']): ?>
            <p style="font-size:12px;color:#f44336;margin-top:8px">⚠️ Please set up <a href="payment_settings.php" style="color:inherit;text-decoration:underline">payment settings</a> first.</p>
          <?php else: ?>
            <p style="font-size:12px;color:var(--text-dim);margin-top:8px">Min. GHS 10.00 • Sent to: <?=$artist['bank_name']?> (<?=$artist['account_number']?>)</p>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Summary cards -->
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon">☀️</div><div class="stat-value"><?=formatMoney($totals['today'])?></div><div class="stat-label">Today</div></div>
      <div class="stat-card"><div class="stat-icon">📅</div><div class="stat-value"><?=formatMoney($totals['week'])?></div><div class="stat-label">This Week</div></div>
      <div class="stat-card"><div class="stat-icon">🗓</div><div class="stat-value"><?=formatMoney($totals['month'])?></div><div class="stat-label">This Month</div></div>
      <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value"><?=formatMoney($totals['all'])?></div><div class="stat-label">All Time</div></div>
    </div>

    <div style="background:rgba(212,175,55,.06);border:1px solid rgba(212,175,55,.2);border-radius:10px;padding:12px 18px;margin-bottom:24px;font-size:13px;color:var(--text-muted)">
      💡 You earn <strong style="color:var(--gold)"><?=formatMoney(EARNINGS_PER_DOWNLOAD)?></strong> per download.
      Monthly reports are sent to your email &amp; phone automatically.
    </div>

    <!-- Chart -->
    <?php if(!empty($chart)):?>
    <div style="background:var(--dark-2);border:1px solid var(--border);border-radius:16px;padding:24px;margin-bottom:28px">
      <h3 style="font-family:var(--font-head);margin-bottom:16px">📈 Monthly Earnings</h3>
      <canvas id="earnChart" height="80"></canvas>
    </div>
    <?php endif;?>

    <!-- Filter + table -->
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
      <?php foreach(['today'=>'Today','week'=>'This Week','month'=>'This Month','all'=>'All Time'] as $k=>$label):?>
      <a href="?period=<?=$k?>" class="genre-pill <?=$period===$k?'active':''?>"><?=$label?></a>
      <?php endforeach;?>
    </div>

    <?php if(empty($records)):?>
    <div class="empty-state"><p>No earnings found for this period.</p></div>
    <?php else:?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Song</th><th>Amount</th><th>Type</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach($records as $r):?>
          <tr>
            <td style="font-weight:500"><?=htmlspecialchars($r['song_title'])?></td>
            <td style="color:var(--gold);font-weight:600"><?=formatMoney($r['amount'])?></td>
            <td><span class="badge badge-approved"><?=ucfirst($r['type'])?></span></td>
            <td style="color:var(--text-dim);font-size:12px"><?=timeAgo($r['created_at'])?></td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
    </div>
    <?php endif;?>

    <!-- Payout History -->
    <?php if(!empty($payouts)): ?>
    <div style="margin-top:40px">
      <h3 style="font-family:var(--font-head);margin-bottom:16px">💸 Payout History</h3>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Reference</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach($payouts as $p):?>
            <tr>
              <td style="font-family:monospace;font-size:12px"><?=htmlspecialchars($p['reference'])?></td>
              <td style="font-weight:600"><?=formatMoney($p['amount'])?></td>
              <td>
                <span class="badge badge-<?= $p['status']==='success'?'approved':($p['status']==='failed'?'rejected':'pending') ?>">
                  <?=ucfirst($p['status'])?>
                </span>
              </td>
              <td style="color:var(--text-dim);font-size:12px"><?=date('M j, Y H:i', strtotime($p['created_at']))?></td>
            </tr>
            <?php endforeach;?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
<?php if(!empty($chart)):?>
new Chart(document.getElementById('earnChart'),{
  type:'line',
  data:{
    labels:<?=json_encode(array_column($chart,'month'))?>,
    datasets:[{
      label:'Earnings (GHS)',
      data:<?=json_encode(array_map(fn($r)=>round((float)$r['total'],2),$chart))?>,
      borderColor:'#d4af37',
      backgroundColor:'rgba(212,175,55,.1)',
      fill:true,tension:.4,pointBackgroundColor:'#d4af37',pointRadius:4
    }]
  },
  options:{plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#666'},grid:{color:'#1e1e1e'}},y:{ticks:{color:'#666',callback:v=>'GHS '+v},grid:{color:'#1e1e1e'}}}}
});
<?php endif;?>
</script>
</body></html>
