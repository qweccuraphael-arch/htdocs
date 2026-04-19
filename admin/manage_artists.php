<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/models/Artist.php';
requireAdmin();
$artistModel = new Artist(); $msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['artist_id']??0); $action = $_POST['action']??'';
    if ($action==='suspend') { $artistModel->update($id,['status'=>'suspended']); $msg='Artist suspended.'; }
    elseif ($action==='activate') { $artistModel->update($id,['status'=>'active']); $msg='Artist activated.'; }
}
$artists = $artistModel->getAll();
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Artists – BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head><body>
<div class="panel-layout">
<?php include '_sidebar.php'; ?>
<main class="panel-main">
<h1 class="panel-title">🎤 Manage Artists</h1>
<?php if($msg):?><div class="form-success" style="padding:10px;background:rgba(76,175,80,.1);border-radius:8px;margin-bottom:16px"><?=$msg?></div><?php endif;?>
<div class="table-wrap"><table class="data-table">
<thead><tr><th>Artist</th><th>Email</th><th>Phone</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($artists as $a):
  $stats = $artistModel->getStats($a['id']);
?>
<tr>
  <td><div style="display:flex;align-items:center;gap:10px">
    <?php if($a['photo']):?><img src="../storage/photos/<?=htmlspecialchars($a['photo'])?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover"><?php else:?><div style="width:36px;height:36px;border-radius:50%;background:var(--dark-3);display:flex;align-items:center;justify-content:center">🎤</div><?php endif;?>
    <div><div style="font-weight:500"><?=htmlspecialchars($a['name'])?></div><div style="font-size:12px;color:var(--text-muted)"><?=$stats['total_songs']?> songs · <?=formatNumber($stats['total_downloads']??0)?> downloads</div></div>
  </div></td>
  <td><?=htmlspecialchars($a['email'])?></td>
  <td><?=htmlspecialchars($a['phone']??'-')?></td>
  <td><span class="badge badge-<?=$a['status']?>"><?=ucfirst($a['status'])?></span></td>
  <td><?=date('M j, Y',strtotime($a['created_at']))?></td>
  <td>
    <?php if($a['status']==='active'):?>
    <form method="POST" style="display:inline"><input type="hidden" name="artist_id" value="<?=$a['id']?>"><input type="hidden" name="action" value="suspend"><button class="btn btn-danger btn-sm" data-confirm="Suspend this artist?">Suspend</button></form>
    <?php else:?>
    <form method="POST" style="display:inline"><input type="hidden" name="artist_id" value="<?=$a['id']?>"><input type="hidden" name="action" value="activate"><button class="btn btn-success btn-sm">Activate</button></form>
    <?php endif;?>
  </td>
</tr>
<?php endforeach;?>
</tbody></table></div>
</main></div>
<script src="../public/assets/js/main.js"></script>
</body></html>
