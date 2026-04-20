<?php
// public/ad_download.php

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/ads.php';
require_once dirname(__DIR__) . '/app/models/Song.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';

$id   = (int)($_GET['id'] ?? 0);
$song = (new Song())->getById($id);

if (!$song) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Download: <?= htmlspecialchars($song['title']) ?> – BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= ADSENSE_PUBLISHER_ID ?>" crossorigin="anonymous"></script>
</head>
<body class="ad-page">

<header class="site-header">
  <div class="container header-inner">
    <a href="index.php" class="logo">🎵 BeatWave</a>
  </div>
</header>

<main class="ad-download-wrap container">

  <!-- TOP INTERSTITIAL AD -->
  <?php if (ADS_ENABLED): ?>
  <div class="ad-interstitial-slot">
    <?php include 'ads/interstitial.php'; ?>
  </div>
  <?php endif; ?>

  <div class="download-box">
    <div class="song-preview">
      <?php if ($song['cover_art']): ?>
        <img class="preview-cover" src="../storage/covers/<?= htmlspecialchars($song['cover_art']) ?>" alt="cover">
      <?php else: ?>
        <div class="preview-cover placeholder-cover">🎵</div>
      <?php endif; ?>
      <div class="preview-info">
        <h1 class="preview-title"><?= htmlspecialchars($song['title']) ?></h1>
        <p class="preview-artist"><?= htmlspecialchars($song['artist_name']) ?></p>
        <div class="preview-meta">
          <span><?= htmlspecialchars($song['genre']) ?></span>
          <?php if ($song['duration']): ?>
          <span>⏱ <?= gmdate('i:s', $song['duration']) ?></span>
          <?php endif; ?>
          <span>⬇ <?= formatNumber($song['download_count']) ?> downloads</span>
        </div>
      </div>
    </div>

    <div class="countdown-area" id="countdown-area">
      <p class="countdown-label">Your download starts in</p>
      <div class="countdown-circle">
        <svg viewBox="0 0 80 80">
          <circle class="bg-circle" cx="40" cy="40" r="34"/>
          <circle class="prog-circle" id="prog-circle" cx="40" cy="40" r="34"
                  stroke-dasharray="213.6" stroke-dashoffset="0"/>
        </svg>
        <span class="countdown-num" id="countdown-num"><?= AD_COUNTDOWN_SECONDS ?></span>
      </div>
      <p class="countdown-sub">Please wait while the ad loads…</p>
    </div>

    <a href="download.php?id=<?= $song['id'] ?>"
       class="btn-big-download disabled" id="dl-btn" aria-disabled="true">
      ⬇ Download Now
    </a>
    <p class="dl-note">Free download · No account needed</p>
  </div>

  <!-- BOTTOM AD -->
  <?php if (ADS_ENABLED): ?>
  <div class="ad-interstitial-slot">
    <?php include 'ads/banner.php'; ?>
  </div>
  <?php endif; ?>

</main>

<script>
(function(){
  const TOTAL  = <?= AD_COUNTDOWN_SECONDS ?>;
  const btn    = document.getElementById('dl-btn');
  const num    = document.getElementById('countdown-num');
  const circle = document.getElementById('prog-circle');
  const CIRC   = 213.6;
  let   left   = TOTAL;

  const tick = setInterval(() => {
    left--;
    num.textContent = left;
    circle.style.strokeDashoffset = ((TOTAL - left) / TOTAL) * CIRC;

    if (left <= 0) {
      clearInterval(tick);
      btn.classList.remove('disabled');
      btn.removeAttribute('aria-disabled');
      document.getElementById('countdown-area').innerHTML =
        '<p class="ready-msg">✅ Your download is ready!</p>';
    }
  }, 1000);

  btn.addEventListener('click', e => {
    if (btn.classList.contains('disabled')) {
      e.preventDefault();
      num.parentElement.parentElement.classList.add('shake');
      setTimeout(() => num.parentElement.parentElement.classList.remove('shake'), 500);
    }
  });
})();
</script>
</body>
</html>
