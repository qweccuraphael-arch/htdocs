<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/models/Song.php';
require_once dirname(__DIR__) . '/app/models/Download.php';
require_once dirname(__DIR__) . '/app/models/Earnings.php';
require_once dirname(__DIR__) . '/app/models/Artist.php';
requireAdmin();

$songModel     = new Song();
$downloadModel = new Download();
$earningsModel = new Earnings();
$artistModel   = new Artist();

$totalSongs     = count($songModel->getAllAdmin());
$totalArtists   = count($artistModel->getAll());
$totalDownloads = $downloadModel->countTotal();
$totalEarnings  = $earningsModel->platformTotal();
$pendingSongs   = count($songModel->getAllAdmin('pending'));
$todayDownloads = $downloadModel->countToday();
$recentSongs    = array_slice($songModel->getAllAdmin(), 0, 5);
$downloadChart  = $downloadModel->getStatsAdmin();
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard – BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head><body>
<div class="panel-layout">
  <?php include '_sidebar.php'; ?>
  <main class="panel-main">
    <h1 class="panel-title">📊 Dashboard</h1>

    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon">🎵</div><div class="stat-value"><?=number_format($totalSongs)?></div><div class="stat-label">Total Songs</div></div>
      <div class="stat-card"><div class="stat-icon">🎤</div><div class="stat-value"><?=number_format($totalArtists)?></div><div class="stat-label">Artists</div></div>
      <div class="stat-card"><div class="stat-icon">⬇</div><div class="stat-value"><?=formatNumber($totalDownloads)?></div><div class="stat-label">Total Downloads</div></div>
      <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value"><?=formatMoney($totalEarnings)?></div><div class="stat-label">Platform Earnings</div></div>
      <div class="stat-card"><div class="stat-icon">⏳</div><div class="stat-value" style="color:#ffc107"><?=number_format($pendingSongs)?></div><div class="stat-label">Pending Approval</div></div>
      <div class="stat-card"><div class="stat-icon">📅</div><div class="stat-value"><?=number_format($todayDownloads)?></div><div class="stat-label">Downloads Today</div></div>
    </div>

    <?php if($pendingSongs > 0):?>
    <div style="background:rgba(255,193,7,.1);border:1px solid rgba(255,193,7,.3);border-radius:10px;padding:14px 20px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between">
      <span>⚠️ <strong><?=$pendingSongs?> song<?=$pendingSongs>1?'s':''?></strong> waiting for approval</span>
      <a href="manage_songs.php?status=pending" class="btn btn-primary btn-sm">Review Now</a>
    </div>
    <?php endif;?>

    <div style="background:var(--dark-2);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:24px">
      <h3 style="font-family:var(--font-head);margin-bottom:16px">📈 Downloads (Last 30 Days)</h3>
      <canvas id="dlChart" height="80"></canvas>
    </div>

    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Song</th><th>Artist</th><th>Genre</th><th>Status</th><th>Downloads</th><th>Uploaded</th></tr></thead>
        <tbody>
          <?php foreach($recentSongs as $s):?>
          <tr>
            <td><a href="manage_songs.php" style="color:var(--gold)"><?=htmlspecialchars($s['title'])?></a></td>
            <td><?=htmlspecialchars($s['artist_name'])?></td>
            <td><?=htmlspecialchars($s['genre'])?></td>
            <td><span class="badge badge-<?=$s['status']?>"><?=ucfirst($s['status'])?></span></td>
            <td><?=formatNumber($s['download_count'])?></td>
            <td><?=timeAgo($s['created_at'])?></td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const labels = <?=json_encode(array_column($downloadChart,'day'))?>;
const data   = <?=json_encode(array_map('intval',array_column($downloadChart,'total')))?>;
new Chart(document.getElementById('dlChart'),{
  type:'bar',
  data:{labels,datasets:[{label:'Downloads',data,backgroundColor:'rgba(212,175,55,.5)',borderColor:'#d4af37',borderWidth:1,borderRadius:4}]},
  options:{plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#666'},grid:{color:'#222'}},y:{ticks:{color:'#666'},grid:{color:'#222'}}}}
});
</script>
</body></html>
