<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/models/Song.php';
require_once dirname(__DIR__) . '/config/db.php';
requireArtist();

$artistId = currentArtistId();
$songModel = new Song();
$songs = $songModel->getByArtist($artistId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int) ($_POST['song_id'] ?? 0);
    $song = getDB()->prepare("SELECT * FROM songs WHERE id = ? AND artist_id = ?");
    $song->execute([$id, $artistId]);
    if ($song->fetch()) {
        $songModel->delete($id);
    }
    header('Location: my_songs.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Songs - BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<div class="panel-layout">
  <?php include '_sidebar.php'; ?>
  <main class="panel-main">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
      <h1 class="panel-title" style="margin-bottom:0">My Songs</h1>
      <a href="upload.php" class="btn btn-primary">Upload New</a>
    </div>

    <?php if (empty($songs)): ?>
    <div class="empty-state">
      <p style="font-size:40px;margin-bottom:12px">M</p>
      <p>You have not uploaded any songs yet.</p>
      <a href="upload.php" class="btn btn-primary" style="margin-top:16px;display:inline-flex">Upload Your First Song</a>
    </div>
    <?php else: ?>
    <div style="background:rgba(212,175,55,.06);border:1px solid rgba(212,175,55,.2);border-radius:10px;padding:12px 18px;margin-bottom:18px;font-size:13px;color:var(--text-muted)">
      Earnings shown here use your artist share of <strong style="color:var(--gold)"><?= formatMoney(ARTIST_EARNINGS_PER_DOWNLOAD) ?></strong> per approved download.
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Cover</th><th>Title</th><th>Genre</th><th>Status</th><th>Downloads</th><th>Artist Earnings</th><th>Uploaded</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($songs as $s): $earned = round((int) $s['download_count'] * ARTIST_EARNINGS_PER_DOWNLOAD, 2); ?>
          <tr>
            <td>
              <?php if ($s['cover_art']): ?>
              <img src="../storage/covers/<?= htmlspecialchars($s['cover_art']) ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover">
              <?php else: ?>
              <div style="width:40px;height:40px;background:var(--dark-3);border-radius:6px;display:flex;align-items:center;justify-content:center">S</div>
              <?php endif; ?>
            </td>
            <td style="font-weight:500">
              <?= htmlspecialchars($s['title']) ?>
              <?php if ($s['album']): ?><div style="font-size:11px;color:var(--text-dim)"><?= htmlspecialchars($s['album']) ?></div><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($s['genre']) ?></td>
            <td>
              <span class="badge badge-<?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars(ucfirst($s['status'])) ?></span>
              <?php if ($s['status'] === 'pending'): ?><div style="font-size:11px;color:var(--text-dim);margin-top:3px">Under review</div><?php endif; ?>
            </td>
            <td><?= number_format((int) $s['download_count']) ?></td>
            <td style="color:var(--gold);font-weight:600"><?= formatMoney($earned) ?></td>
            <td style="color:var(--text-dim);font-size:12px"><?= timeAgo($s['created_at']) ?></td>
            <td>
              <div style="display:flex;gap:5px">
                <?php if ($s['status'] === 'approved'): ?>
                <a href="../public/song.php?id=<?= (int) $s['id'] ?>" target="_blank" class="btn btn-sm" style="border:1px solid var(--border)">View</a>
                <?php endif; ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="song_id" value="<?= (int) $s['id'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <button class="btn btn-danger btn-sm" data-confirm="Delete this song permanently?">Delete</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </main>
</div>
<script src="../public/assets/js/main.js"></script>
</body>
</html>
