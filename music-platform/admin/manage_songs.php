<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/helpers/security.php';
require_once dirname(__DIR__) . '/app/controllers/SongController.php';
require_once dirname(__DIR__) . '/app/models/Song.php';
requireAdmin(); startSession();
$ctrl = new SongController(); $songModel = new Song(); $msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ''; $id = (int)($_POST['song_id'] ?? 0);
    if ($action === 'approve') { $r = $ctrl->approveSong($id); $r['success'] ? $msg='✅ Song approved. Artist notified via email & SMS.' : $err=$r['error']; }
    elseif ($action === 'reject') { $r = $ctrl->rejectSong($id, sanitize($_POST['reason']??'')); $r['success'] ? $msg='Song rejected. Artist notified.' : $err=$r['error']; }
    elseif ($action === 'delete') { $songModel->delete($id); $msg='Song deleted.'; }
    elseif ($action === 'feature') { getDB()->prepare("UPDATE songs SET is_featured=1 WHERE id=?")->execute([$id]); $msg='Song featured.'; }
}
$status = $_GET['status'] ?? ''; $songs = $songModel->getAllAdmin($status);
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Songs – BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head><body>
<div class="panel-layout">
<?php include '_sidebar.php'; ?>
<main class="panel-main">
<h1 class="panel-title">🎵 Manage Songs</h1>
<?php if($msg):?><div class="form-success" style="padding:10px;background:rgba(76,175,80,.1);border-radius:8px;margin-bottom:16px"><?=$msg?></div><?php endif;?>
<?php if($err):?><div class="form-error" style="padding:10px;background:rgba(244,67,54,.1);border-radius:8px;margin-bottom:16px"><?=$err?></div><?php endif;?>
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
  <a href="manage_songs.php" class="genre-pill <?=!$status?'active':''?>">All</a>
  <a href="?status=pending"  class="genre-pill <?=$status==='pending'?'active':''?>">⏳ Pending</a>
  <a href="?status=approved" class="genre-pill <?=$status==='approved'?'active':''?>">✅ Approved</a>
  <a href="?status=rejected" class="genre-pill <?=$status==='rejected'?'active':''?>">❌ Rejected</a>
</div>
<div class="table-wrap"><table class="data-table">
<thead><tr><th>Cover</th><th>Title</th><th>Artist</th><th>Genre</th><th>Status</th><th>Downloads</th><th>Date</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($songs as $s):?>
<tr>
  <td><?php if($s['cover_art']):?><img src="../storage/covers/<?=htmlspecialchars($s['cover_art'])?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover"><?php else:?><div style="width:40px;height:40px;background:var(--dark-3);border-radius:6px;display:flex;align-items:center;justify-content:center">🎵</div><?php endif;?></td>
  <td style="font-weight:500"><?=htmlspecialchars($s['title'])?></td>
  <td><?=htmlspecialchars($s['artist_name'])?></td>
  <td><?=htmlspecialchars($s['genre'])?></td>
  <td><span class="badge badge-<?=$s['status']?>"><?=ucfirst($s['status'])?></span></td>
  <td><?=number_format($s['download_count'])?></td>
  <td><?=date('M j, Y',strtotime($s['created_at']))?></td>
  <td><div style="display:flex;gap:5px;flex-wrap:wrap">
    <?php if($s['status']==='pending'):?>
    <form method="POST" style="display:inline"><input type="hidden" name="song_id" value="<?=$s['id']?>"><input type="hidden" name="action" value="approve"><button class="btn btn-success btn-sm">✅ Approve</button></form>
    <form method="POST" style="display:inline" onsubmit="return rej(this)"><input type="hidden" name="song_id" value="<?=$s['id']?>"><input type="hidden" name="action" value="reject"><input type="hidden" name="reason" class="rr" value=""><button class="btn btn-danger btn-sm">❌ Reject</button></form>
    <?php endif;?>
    <?php if($s['status']==='approved'&&!$s['is_featured']):?>
    <form method="POST" style="display:inline"><input type="hidden" name="song_id" value="<?=$s['id']?>"><input type="hidden" name="action" value="feature"><button class="btn btn-sm" style="border:1px solid var(--border)">⭐ Feature</button></form>
    <?php endif;?>
    <form method="POST" style="display:inline"><input type="hidden" name="song_id" value="<?=$s['id']?>"><input type="hidden" name="action" value="delete"><button class="btn btn-danger btn-sm" data-confirm="Delete permanently?">🗑</button></form>
  </div></td>
</tr>
<?php endforeach;?>
</tbody></table></div>
</main></div>
<script src="../public/assets/js/main.js"></script>
<script>function rej(f){const r=prompt('Rejection reason (optional):')||'';f.querySelector('.rr').value=r;return confirm('Reject this song?');}</script>
</body></html>
