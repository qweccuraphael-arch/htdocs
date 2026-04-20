<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/models/Earnings.php';
require_once dirname(__DIR__) . '/app/models/Payout.php';
requireArtist();

$artistId = currentArtistId();
$earningsModel = new Earnings();
$payoutModel = new Payout();
$period = $_GET['period'] ?? 'all';

$totals = [
    'today' => $earningsModel->totalByArtist($artistId, 'today'),
    'week' => $earningsModel->totalByArtist($artistId, 'week'),
    'month' => $earningsModel->totalByArtist($artistId, 'month'),
    'all' => $earningsModel->totalByArtist($artistId, 'all'),
];
$records = $earningsModel->getByArtist($artistId, $period);
$chart = $earningsModel->getMonthlyChart($artistId);
$availableBalance = $payoutModel->getAvailableBalance($artistId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Earnings - BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<div class="panel-layout">
  <?php include '_sidebar.php'; ?>
  <main class="panel-main">
    <h1 class="panel-title">My Earnings</h1>

    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon">T</div><div class="stat-value"><?= formatMoney($totals['today']) ?></div><div class="stat-label">Today</div></div>
      <div class="stat-card"><div class="stat-icon">W</div><div class="stat-value"><?= formatMoney($totals['week']) ?></div><div class="stat-label">This Week</div></div>
      <div class="stat-card"><div class="stat-icon">M</div><div class="stat-value"><?= formatMoney($totals['month']) ?></div><div class="stat-label">This Month</div></div>
      <div class="stat-card"><div class="stat-icon">A</div><div class="stat-value"><?= formatMoney($totals['all']) ?></div><div class="stat-label">All Time</div></div>
      <div class="stat-card"><div class="stat-icon">B</div><div class="stat-value"><?= formatMoney($availableBalance) ?></div><div class="stat-label">Available to Withdraw</div></div>
    </div>

    <div style="background:rgba(212,175,55,.06);border:1px solid rgba(212,175,55,.2);border-radius:10px;padding:12px 18px;margin-bottom:24px;font-size:13px;color:var(--text-muted)">
      Artist share per download: <strong style="color:var(--gold)"><?= formatMoney(ARTIST_EARNINGS_PER_DOWNLOAD) ?></strong>.
      Withdrawals can be requested any time after admin confirms your payment details.
    </div>

    <?php if (!empty($chart)): ?>
    <div style="background:var(--dark-2);border:1px solid var(--border);border-radius:16px;padding:24px;margin-bottom:28px">
      <h3 style="font-family:var(--font-head);margin-bottom:16px">Monthly Earnings</h3>
      <canvas id="earnChart" height="80"></canvas>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
      <?php foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'all' => 'All Time'] as $k => $label): ?>
      <a href="?period=<?= $k ?>" class="genre-pill <?= $period === $k ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($records)): ?>
    <div class="empty-state"><p>No earnings found for this period.</p></div>
    <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Song</th><th>Amount</th><th>Type</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($records as $r): ?>
          <tr>
            <td style="font-weight:500"><?= htmlspecialchars($r['song_title']) ?></td>
            <td style="color:var(--gold);font-weight:600"><?= formatMoney((float) $r['amount']) ?></td>
            <td><span class="badge badge-approved"><?= htmlspecialchars(ucfirst($r['type'])) ?></span></td>
            <td style="color:var(--text-dim);font-size:12px"><?= timeAgo($r['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
<?php if (!empty($chart)): ?>
new Chart(document.getElementById('earnChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($chart, 'month')) ?>,
    datasets: [{
      label: 'Earnings (GHS)',
      data: <?= json_encode(array_map(fn($r) => round((float) $r['total'], 2), $chart)) ?>,
      borderColor: '#d4af37',
      backgroundColor: 'rgba(212,175,55,.1)',
      fill: true,
      tension: .4,
      pointBackgroundColor: '#d4af37',
      pointRadius: 4
    }]
  },
  options: {plugins: {legend: {display: false}}, scales: {x: {ticks: {color: '#666'}, grid: {color: '#1e1e1e'}}, y: {ticks: {color: '#666', callback: v => 'GHS ' + v}, grid: {color: '#1e1e1e'}}}}
});
<?php endif; ?>
</script>
</body>
</html>
