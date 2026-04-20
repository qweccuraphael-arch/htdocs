<?php
// public/index.php

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/ads.php';
require_once dirname(__DIR__) . '/app/models/Song.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';

$songModel = new Song();
$page      = max(1, (int)($_GET['page'] ?? 1));
$search    = htmlspecialchars($_GET['q'] ?? '');
$genre     = htmlspecialchars($_GET['genre'] ?? '');

$songs    = $songModel->getAll($page, SONGS_PER_PAGE, $search, $genre);
$total    = $songModel->countAll($search, $genre);
$pages    = ceil($total / SONGS_PER_PAGE);
$genres   = $songModel->getGenres();
$trending = $songModel->getTrending(5);
$featured = $songModel->getFeatured(6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BeatWave – Ghana's Music Hub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= ADSENSE_PUBLISHER_ID ?>" crossorigin="anonymous"></script>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
  <div class="container header-inner">
    <a href="index.php" class="logo">🎵 BeatWave</a>
    <form class="search-bar" method="GET" action="index.php">
      <input type="text" name="q" placeholder="Search songs, artists…" value="<?= $search ?>">
      <button type="submit">Search</button>
    </form>
    <nav class="nav-links">
      <a href="index.php">Home</a>
      <a href="../artist/login.php">Artist Login</a>
    </nav>
  </div>
</header>

<!-- BANNER AD -->
<?php if (ADS_ENABLED): ?>
<div class="ad-banner-wrap container">
  <?php include 'ads/banner.php'; ?>
</div>
<?php endif; ?>

<main class="container main-layout">
  <div class="content-area">

    <!-- GENRE FILTER -->
    <div class="genre-filter">
      <a href="index.php" class="genre-pill <?= !$genre ? 'active' : '' ?>">All</a>
      <?php foreach ($genres as $g): ?>
        <a href="?genre=<?= urlencode($g) ?>" class="genre-pill <?= $genre === $g ? 'active' : '' ?>">
          <?= htmlspecialchars($g) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- FEATURED SONGS -->
    <?php if ($featured && !$search && !$genre && $page === 1): ?>
    <section class="section-block">
      <h2 class="section-title">✨ Featured</h2>
      <div class="featured-grid">
        <?php foreach ($featured as $song): ?>
        <div class="song-card featured-card">
          <div class="cover-wrap">
            <?php if ($song['cover_art']): ?>
              <img src="../storage/covers/<?= htmlspecialchars($song['cover_art']) ?>" alt="cover" loading="lazy">
            <?php else: ?>
              <div class="cover-placeholder">🎵</div>
            <?php endif; ?>
            <div class="play-overlay">
              <a href="song.php?id=<?= $song['id'] ?>" class="btn-play">▶</a>
            </div>
          </div>
          <div class="song-info">
            <a href="song.php?id=<?= $song['id'] ?>" class="song-title"><?= htmlspecialchars($song['title']) ?></a>
            <span class="song-artist"><?= htmlspecialchars($song['artist_name']) ?></span>
            <div class="song-meta">
              <span class="badge-genre"><?= htmlspecialchars($song['genre']) ?></span>
              <span class="dl-count">⬇ <?= formatNumber($song['download_count']) ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- SONGS LIST -->
    <section class="section-block">
      <h2 class="section-title">
        <?= $search ? "Results for \"$search\"" : ($genre ? htmlspecialchars($genre) : '🎶 All Songs') ?>
        <span class="count-badge"><?= number_format($total) ?></span>
      </h2>

      <?php if (empty($songs)): ?>
        <div class="empty-state">
          <p>No songs found. Try a different search or genre.</p>
        </div>
      <?php else: ?>
      <div class="songs-list">
        <?php foreach ($songs as $i => $song): ?>
          <?php if ($i > 0 && $i % 5 === 0 && ADS_ENABLED): ?>
          <div class="inline-ad"><?php include 'ads/banner.php'; ?></div>
          <?php endif; ?>

          <div class="song-row">
            <span class="row-num"><?= (($page-1)*SONGS_PER_PAGE) + $i + 1 ?></span>
            <div class="row-cover">
              <?php if ($song['cover_art']): ?>
                <img src="../storage/covers/<?= htmlspecialchars($song['cover_art']) ?>" alt="" loading="lazy">
              <?php else: ?>
                <div class="cover-mini">🎵</div>
              <?php endif; ?>
            </div>
            <div class="row-info">
              <a href="song.php?id=<?= $song['id'] ?>" class="row-title"><?= htmlspecialchars($song['title']) ?></a>
              <span class="row-artist"><?= htmlspecialchars($song['artist_name']) ?></span>
            </div>
            <span class="row-genre"><?= htmlspecialchars($song['genre']) ?></span>
            <span class="row-downloads">⬇ <?= formatNumber($song['download_count']) ?></span>
            <div class="row-actions">
              <a href="song.php?id=<?= $song['id'] ?>" class="btn-listen" style="margin-right:8px; color:var(--primary); text-decoration:none; font-weight:500;">▶ Listen</a>
              <a href="ad_download.php?id=<?= $song['id'] ?>" class="btn-download">Download</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- PAGINATION -->
      <?php if ($pages > 1): ?>
      <div class="pagination">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&genre=<?= urlencode($genre) ?>"
             class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </section>

  </div><!-- /content-area -->

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <?php if (ADS_ENABLED): ?>
    <div class="sidebar-widget"><?php include 'ads/sidebar.php'; ?></div>
    <?php endif; ?>

    <div class="sidebar-widget">
      <h3 class="widget-title">🔥 Trending</h3>
      <?php foreach ($trending as $i => $s): ?>
      <div class="trending-row">
        <span class="trend-num"><?= $i+1 ?></span>
        <div class="trend-info">
          <a href="song.php?id=<?= $s['id'] ?>"><?= htmlspecialchars($s['title']) ?></a>
          <small><?= htmlspecialchars($s['artist_name']) ?></small>
        </div>
        <span class="trend-count">⬇<?= formatNumber($s['download_count']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </aside>
</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <span>&copy; <?= date('Y') ?> BeatWave — Ghana's Music Hub</span>
    <span>Built by <a href="tel:0249740636">Raphael</a></span>
  </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
