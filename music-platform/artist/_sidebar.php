<?php
require_once dirname(__DIR__) . '/app/helpers/auth.php';
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="panel-sidebar">
  <a href="dashboard.php" class="panel-logo">🎤 Artist Panel</a>
  <nav class="panel-nav">
    <a href="dashboard.php" class="<?=$current==='dashboard.php'?'active':''?>">📊 Dashboard</a>
    <a href="upload.php"    class="<?=$current==='upload.php'?'active':''?>">⬆ Upload Song</a>
    <a href="my_songs.php"  class="<?=$current==='my_songs.php'?'active':''?>">🎵 My Songs</a>
    <a href="earnings.php"  class="<?=$current==='earnings.php'?'active':''?>">💰 Earnings</a>
    <a href="payments.php"  class="<?=$current==='payments.php'?'active':''?>">💳 Payments</a>
    <a href="../public/index.php" target="_blank">🌐 View Site</a>
    <a href="logout.php" style="color:#f44336">🚪 Logout</a>
  </nav>
  <div style="padding:16px 20px;margin-top:auto;border-top:1px solid var(--border)">
    <div style="font-size:12px;color:var(--text-dim)">Logged in as</div>
    <div style="font-size:14px;font-weight:600;color:var(--gold)"><?=htmlspecialchars(currentArtistName())?></div>
  </div>
</aside>
