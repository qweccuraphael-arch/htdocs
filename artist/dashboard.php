<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/models/Artist.php';
require_once dirname(__DIR__) . '/app/models/Earnings.php';
require_once dirname(__DIR__) . '/app/models/Download.php';
require_once dirname(__DIR__) . '/app/models/Song.php';
requireArtist();

$artistId      = currentArtistId();
$artistName    = currentArtistName();
$artistModel   = new Artist();
$artist        = $artistModel->getById($artistId);
$earningsModel = new Earnings();
$downloadModel = new Download();
$songModel     = new Song();

$stats        = $artistModel->getStats($artistId);
$earnToday    = $earningsModel->totalByArtist($artistId, 'today');
$earnMonth    = $earningsModel->totalByArtist($artistId, 'month');
$earnAll      = $earningsModel->totalByArtist($artistId, 'all');
$recentDls    = $downloadModel->getRecentByArtist($artistId, 5);
$mySongs      = $songModel->getByArtist($artistId);
$monthChart   = $earningsModel->getMonthlyChart($artistId);
$pendingCount = count(array_filter($mySongs, fn($s) => $s['status'] === 'pending'));
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard – BeatWave Artist</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head><body>
<div class="panel-layout">
  <?php include '_sidebar.php'; ?>
  <main class="panel-main">
    <h1 class="panel-title">👋 Welcome, <?=htmlspecialchars($artistName)?></h1>

    <?php if ($artist['kyc_status'] !== 'approved'): ?>
    <div style="background:rgba(212,175,55,.1);border:1px solid var(--gold);padding:20px;border-radius:16px;margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap">
      <div>
        <h3 style="font-family:var(--font-head);margin:0 0 4px;font-size:16px;color:var(--gold)">🏢 Business Verification Needed</h3>
        <p style="margin:0;font-size:13px;color:var(--text-muted)">
          <?php if($artist['kyc_status'] === 'none'): ?>
            Please upload your business documents to verify your account and enable all features.
          <?php elseif($artist['kyc_status'] === 'pending'): ?>
            Your verification documents are currently under review.
          <?php elseif($artist['kyc_status'] === 'rejected'): ?>
            Your verification was rejected. Please review and resubmit your documents.
          <?php endif; ?>
        </p>
      </div>
      <?php if($artist['kyc_status'] !== 'pending'): ?>
      <a href="kyc.php" class="btn btn-primary">Complete Verification</a>
      <?php else: ?>
      <span class="badge badge-pending">Under Review</span>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon">🎵</div><div class="stat-value"><?=number_format($stats['total_songs']??0)?></div><div class="stat-label">Songs Uploaded</div></div>
      <div class="stat-card"><div class="stat-icon">⬇</div><div class="stat-value"><?=formatNumber($stats['total_downloads']??0)?></div><div class="stat-label">Total Downloads</div></div>
      <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value"><?=formatMoney($earnAll)?></div><div class="stat-label">Total Earnings</div></div>
      <div class="stat-card"><div class="stat-icon">📅</div><div class="stat-value"><?=formatMoney($earnMonth)?></div><div class="stat-label">This Month</div></div>
      <div class="stat-card"><div class="stat-icon">☀️</div><div class="stat-value"><?=formatMoney($earnToday)?></div><div class="stat-label">Today</div></div>
      <?php if($pendingCount):?>
      <div class="stat-card"><div class="stat-icon">⏳</div><div class="stat-value" style="color:#ffc107"><?=$pendingCount?></div><div class="stat-label">Pending Review</div></div>
      <?php endif;?>
    </div>

    <!-- Earnings chart -->
    <?php if(!empty($monthChart)):?>
    <div style="background:var(--dark-2);border:1px solid var(--border);border-radius:16px;padding:24px;margin-bottom:28px">
      <h3 style="font-family:var(--font-head);margin-bottom:16px">💰 Monthly Earnings (GHS)</h3>
      <canvas id="earnChart" height="80"></canvas>
    </div>
    <?php endif;?>

    <!-- Recent downloads -->
    <?php if(!empty($recentDls)):?>
    <h2 style="font-family:var(--font-head);font-size:18px;margin-bottom:12px">⬇ Recent Downloads</h2>
    <div class="table-wrap" style="margin-bottom:28px">
      <table class="data-table">
        <thead><tr><th>Song</th><th>IP</th><th>Time</th></tr></thead>
        <tbody>
          <?php foreach($recentDls as $d):?>
          <tr>
            <td><?=htmlspecialchars($d['song_title'])?></td>
            <td style="color:var(--text-muted);font-size:12px"><?=htmlspecialchars($d['ip_address'])?></td>
            <td style="color:var(--text-dim);font-size:12px"><?=timeAgo($d['created_at'])?></td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
    </div>
    <?php endif;?>

    <!-- Quick links -->
    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <a href="upload.php" class="btn btn-primary">⬆ Upload New Song</a>
      <a href="my_songs.php" class="btn" style="border:1px solid var(--border)">🎵 My Songs</a>
      <a href="earnings.php" class="btn" style="border:1px solid var(--border)">💰 Full Earnings</a>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
<?php if(!empty($monthChart)):?>
new Chart(document.getElementById('earnChart'),{
  type:'bar',
  data:{
    labels:<?=json_encode(array_column($monthChart,'month'))?>,
    datasets:[{
      label:'Earnings (GHS)',
      data:<?=json_encode(array_map(fn($r)=>round((float)$r['total'],2),$monthChart))?>,
      backgroundColor:'rgba(212,175,55,.45)',
      borderColor:'#d4af37',
      borderWidth:1,
      borderRadius:5
    }]
  },
  options:{plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#666'},grid:{color:'#1e1e1e'}},y:{ticks:{color:'#666',callback:v=>'GHS '+v},grid:{color:'#1e1e1e'}}}}
});
<?php endif;?>
</script>
</body></html>
