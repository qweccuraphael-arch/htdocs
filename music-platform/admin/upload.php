<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/security.php';
require_once dirname(__DIR__) . '/app/controllers/SongController.php';
require_once dirname(__DIR__) . '/app/models/Artist.php';
requireAdmin(); startSession();
$ctrl = new SongController(); $artists = (new Artist())->getAll();
$msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = $ctrl->uploadSong($_POST, $_FILES, (int)$_POST['artist_id']);
    if ($r['success']) {
        (new Song())->updateStatus($r['song_id'], 'approved'); // admin uploads auto-approve
        $msg = '✅ Song uploaded and published.';
    } else { $err = $r['error']; }
}
$genres = ['Afrobeats','Afropop','Highlife','Gospel','Hiplife','Hip-hop','R&B','Reggae','Dancehall','Trap','Pop','Other'];
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Upload Song – BeatWave Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head><body>
<div class="panel-layout">
<?php include '_sidebar.php'; ?>
<main class="panel-main">
<h1 class="panel-title">⬆ Upload Song</h1>
<?php if($msg):?><div class="form-success" style="padding:10px;background:rgba(76,175,80,.1);border-radius:8px;margin-bottom:16px"><?=$msg?></div><?php endif;?>
<?php if($err):?><div class="form-error" style="padding:10px;background:rgba(244,67,54,.1);border-radius:8px;margin-bottom:16px"><?=$err?></div><?php endif;?>
<div class="form-card">
<form method="POST" enctype="multipart/form-data">
  <div class="form-group"><label class="form-label">Artist *</label>
    <select name="artist_id" class="form-control" required>
      <option value="">Select artist…</option>
      <?php foreach($artists as $a):?><option value="<?=$a['id']?>"><?=htmlspecialchars($a['name'])?></option><?php endforeach;?>
    </select>
  </div>
  <div class="form-group"><label class="form-label">Song Title *</label><input name="title" type="text" class="form-control" required></div>
  <div class="form-group"><label class="form-label">Genre *</label>
    <select name="genre" class="form-control" required>
      <?php foreach($genres as $g):?><option value="<?=$g?>"><?=$g?></option><?php endforeach;?>
    </select>
  </div>
  <div class="form-group"><label class="form-label">Album</label><input name="album" type="text" class="form-control"></div>
  <div class="form-group"><label class="form-label">Year</label><input name="year" type="number" class="form-control" value="<?=date('Y')?>" min="1950" max="<?=date('Y')?>"></div>
  <div class="form-group"><label class="form-label">Audio File * (MP3/WAV/FLAC, max 50MB)</label><input name="audio" type="file" class="form-control" accept="audio/*" required></div>
  <div class="form-group"><label class="form-label">Cover Art (JPG/PNG/WEBP)</label><input name="cover" type="file" class="form-control" accept="image/*"></div>
  <button type="submit" class="btn btn-primary">⬆ Upload Song</button>
</form>
</div>
</main></div>
</body></html>
