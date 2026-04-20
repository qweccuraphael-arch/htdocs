<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/models/Download.php';
require_once dirname(__DIR__) . '/app/models/Song.php';
requireAdmin();
$downloadModel = new Download(); $songModel = new Song();
$chart   = $downloadModel->getStatsAdmin();
$topSongs = $songModel->getTrending(10);
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Analytics – BeatWave Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head><body>
<div class="panel-layout">
<?php include '_sidebar.php'; ?>
<main class="panel-main">
<h1 class="panel-title">📈 Analytics</h1>
<div style="background:var(--dark-2);border:1px solid var(--border);border-radius:16px;padding:24px;margin-bottom:28px">
  <h3 style="font-family:var(--font-head);margin-bottom:20px">Downloads — Last 30 Days</h3>
  <canvas id="chart1" height="80"></canvas>
</div>
<h2 style="font-family:var(--font-head);font-size:18px;margin-bottom:16px">🔥 Top 10 Songs</h2>
<div class="table-wrap"><table class="data-table">
<thead><tr><th>#</th><th>Song</th><th>Artist</th><th>Genre</th><th>Downloads</th></tr></thead>
<tbody>
<?php foreach($topSongs as $i=>$s):?>
<tr>
  <td style="color:var(--gold);font-weight:700"><?=$i+1?></td>
  <td style="font-weight:500"><?=htmlspecialchars($s['title'])?></td>
  <td><?=htmlspecialchars($s['artist_name'])?></td>
  <td><?=htmlspecialchars($s['genre'])?></td>
  <td><?=number_format($s['download_count'])?></td>
</tr>
<?php endforeach;?>
</tbody></table></div>
</main></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const labels=<?=json_encode(array_column($chart,'day'))?>;
const data=<?=json_encode(array_map('intval',array_column($chart,'total')))?>;
new Chart(document.getElementById('chart1'),{type:'line',data:{labels,datasets:[{label:'Downloads',data,borderColor:'#d4af37',backgroundColor:'rgba(212,175,55,.1)',fill:true,tension:.4,pointBackgroundColor:'#d4af37'}]},options:{plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#666'},grid:{color:'#1e1e1e'}},y:{ticks:{color:'#666'},grid:{color:'#1e1e1e'}}}}});
</script>
</body></html>
