<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="panel-sidebar">
  <a href="index.php" class="panel-logo">🎵 BeatWave</a>
  <nav class="panel-nav">
    <a href="index.php"          class="<?=$current==='index.php'?'active':''?>">📊 Dashboard</a>
    <a href="manage_songs.php"   class="<?=$current==='manage_songs.php'?'active':''?>">🎵 Songs</a>
    <a href="manage_artists.php" class="<?=$current==='manage_artists.php'?'active':''?>">🎤 Artists</a>
    <a href="earnings.php"       class="<?=$current==='earnings.php'?'active':''?>">💰 Earnings</a>
    <a href="payouts.php"        class="<?=$current==='payouts.php'?'active':''?>">💳 Payouts</a>
    <a href="verify_payments.php" class="<?=$current==='verify_payments.php'?'active':''?>">✅ Verify Payments</a>
    <a href="analytics.php"      class="<?=$current==='analytics.php'?'active':''?>">📈 Analytics</a>
    <a href="upload.php"         class="<?=$current==='upload.php'?'active':''?>">⬆ Upload Song</a>
    <a href="../public/index.php" target="_blank">🌐 View Site</a>
    <a href="logout.php" style="margin-top:auto;color:#f44336">🚪 Logout</a>
  </nav>
</aside>
