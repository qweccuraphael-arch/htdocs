<?php
// public/song.php

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
<title><?= htmlspecialchars($song['title']) ?> – BeatWave</title>
<meta property="og:title" content="<?= htmlspecialchars($song['title']) ?>">
<meta property="og:description" content="Download <?= htmlspecialchars($song['title']) ?> by <?= htmlspecialchars($song['artist_name']) ?> on BeatWave">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= ADSENSE_PUBLISHER_ID ?>" crossorigin="anonymous"></script>
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="index.php" class="logo">🎵 BeatWave</a>
    <nav class="nav-links">
      <a href="index.php">← Back to Songs</a>
    </nav>
  </div>
</header>

<main class="container song-page-wrap">

  <div class="song-hero">
    <?php if ($song['cover_art']): ?>
      <img class="hero-cover" src="../storage/covers/<?= htmlspecialchars($song['cover_art']) ?>" alt="cover">
    <?php else: ?>
      <div class="hero-cover hero-cover-placeholder">🎵</div>
    <?php endif; ?>

    <div class="hero-info">
      <span class="hero-genre"><?= htmlspecialchars($song['genre']) ?></span>
      <h1 class="hero-title"><?= htmlspecialchars($song['title']) ?></h1>
      <p class="hero-artist">by <strong><?= htmlspecialchars($song['artist_name']) ?></strong></p>
      <div class="hero-meta">
        <?php if ($song['album']): ?><span>💿 <?= htmlspecialchars($song['album']) ?></span><?php endif; ?>
        <?php if ($song['year']): ?><span>📅 <?= $song['year'] ?></span><?php endif; ?>
        <?php if ($song['duration']): ?><span>⏱ <?= gmdate('i:s', $song['duration']) ?></span><?php endif; ?>
        <span>⬇ <?= formatNumber($song['download_count']) ?> downloads</span>
        <?php if ($song['file_size']): ?><span>📦 <?= formatBytes($song['file_size']) ?></span><?php endif; ?>
      </div>

      <?php if ($song['artist_bio']): ?>
      <p class="artist-bio"><?= nl2br(htmlspecialchars($song['artist_bio'])) ?></p>
      <?php endif; ?>

      <div class="audio-player-wrap" style="margin: 20px 0;">
        <audio controls controlsList="nodownload" style="width: 100%; height: 56px; border-radius: 12px;">
          <source src="stream.php?id=<?= $song['id'] ?>" type="audio/mpeg">
          Your browser does not support the audio element.
        </audio>
      </div>

      <div class="hero-actions">
        <a href="ad_download.php?id=<?= $song['id'] ?>" class="btn-big-download">⬇ Free Download</a>
        <a href="buy_song.php?id=<?= $song['id'] ?>" class="btn-big-download" style="background:#4caf50; border-color:#4caf50;">💰 Buy Song (GHS 2.00)</a>
        <button class="btn-share" onclick="shareThis()">📤 Share</button>
      </div>
    </div>
  </div>

  <?php if (ADS_ENABLED): ?>
  <div class="ad-inline"><?php include 'ads/banner.php'; ?></div>
  <?php endif; ?>

</main>

<script>
function shareThis() {
  if (navigator.share) {
    navigator.share({
      title: '<?= addslashes($song['title']) ?> – BeatWave',
      text: 'Listen & download "<?= addslashes($song['title']) ?>" by <?= addslashes($song['artist_name']) ?> on BeatWave!',
      url: window.location.href
    });
  } else {
    navigator.clipboard.writeText(window.location.href);
    alert('Link copied!');
  }
}
</script>
</body>
</html>
