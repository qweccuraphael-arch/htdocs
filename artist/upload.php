<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/helpers/security.php';
require_once dirname(__DIR__) . '/app/controllers/SongController.php';
require_once dirname(__DIR__) . '/app/models/Artist.php';
requireArtist();

$artistId = currentArtistId();
$ctrl     = new SongController();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = $ctrl->uploadSong($_POST, $_FILES, $artistId);
    if ($r['success']) {
        // Notify admin of new pending song
        $artist = (new Artist())->getById($artistId);
        $title  = sanitize($_POST['title'] ?? 'Unknown');
        sendNewSongNotificationEmail('admin@yourdomain.com', $title, $artist['name'] ?? '');
        $msg = '✅ Song uploaded successfully! It will be reviewed and published shortly.';
    } else {
        $err = $r['error'];
    }
}

$genres = ['Afrobeats','Afropop','Highlife','Gospel','Hiplife','Hip-hop','R&B','Reggae','Dancehall','Trap','Pop','Other'];
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Upload Song – BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head><body>
<div class="panel-layout">
  <?php include '_sidebar.php'; ?>
  <main class="panel-main">
    <h1 class="panel-title">⬆ Upload New Song</h1>

    <?php if($msg):?>
    <div class="form-success" style="padding:14px;background:rgba(76,175,80,.1);border-radius:10px;margin-bottom:20px;font-size:15px"><?=$msg?></div>
    <?php endif;?>
    <?php if($err):?>
    <div class="form-error" style="padding:14px;background:rgba(244,67,54,.1);border-radius:10px;margin-bottom:20px"><?=$err?></div>
    <?php endif;?>

    <div class="form-card">
      <form method="POST" enctype="multipart/form-data" id="upload-form">

        <div class="form-group">
          <label class="form-label">Song Title *</label>
          <input name="title" type="text" class="form-control" required placeholder="e.g. My Afrobeats Hit">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Genre *</label>
            <select name="genre" class="form-control" required>
              <option value="">Select genre…</option>
              <?php foreach($genres as $g):?><option value="<?=$g?>"><?=$g?></option><?php endforeach;?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Year</label>
            <input name="year" type="number" class="form-control" value="<?=date('Y')?>" min="1950" max="<?=date('Y')?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Album / EP (optional)</label>
          <input name="album" type="text" class="form-control" placeholder="e.g. Volume 1">
        </div>

        <div class="form-group">
          <label class="form-label">Audio File * <span style="color:var(--text-dim)">(MP3, WAV, FLAC, AAC — max 50MB)</span></label>
          <input name="audio" type="file" class="form-control" accept="audio/*" required onchange="previewAudio(this)">
          <audio id="audio-preview" controls style="display:none;width:100%;margin-top:10px;border-radius:8px"></audio>
        </div>

        <div class="form-group">
          <label class="form-label">Cover Art <span style="color:var(--text-dim)">(JPG/PNG/WEBP)</span></label>
          <input name="cover" type="file" class="form-control" accept="image/*" onchange="previewCover(this)">
          <img id="cover-preview" style="display:none;width:120px;height:120px;border-radius:10px;object-fit:cover;margin-top:10px;border:1px solid var(--border)">
        </div>

        <div style="background:rgba(212,175,55,.06);border:1px solid rgba(212,175,55,.2);border-radius:10px;padding:14px;margin-bottom:20px;font-size:13px;color:var(--text-muted)">
          💡 Your song will be reviewed by our team before going live. You'll receive an <strong>email and SMS</strong> notification once approved.
        </div>

        <button type="submit" class="btn btn-primary" id="upload-btn" style="padding:12px 28px">
          ⬆ Upload Song
        </button>
      </form>
    </div>
  </main>
</div>
<script>
function previewAudio(input) {
  const audio = document.getElementById('audio-preview');
  if (input.files && input.files[0]) {
    audio.src = URL.createObjectURL(input.files[0]);
    audio.style.display = 'block';
  }
}
function previewCover(input) {
  const img = document.getElementById('cover-preview');
  if (input.files && input.files[0]) {
    img.src = URL.createObjectURL(input.files[0]);
    img.style.display = 'block';
  }
}
document.getElementById('upload-form').addEventListener('submit', () => {
  document.getElementById('upload-btn').textContent = '⏳ Uploading…';
  document.getElementById('upload-btn').disabled = true;
});
</script>
</body></html>
