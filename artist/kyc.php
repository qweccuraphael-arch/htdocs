<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/controllers/ArtistController.php';

requireArtist(); // Ensure logged in
$artistId = currentArtistId();
$artistName = currentArtistName();

$ctrl = new ArtistController();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = $ctrl->submitKYC($artistId, $_POST, $_FILES);
    if ($r['success']) {
        $msg = $r['message'];
    } else {
        $err = $r['error'];
    }
}

// Get current status
require_once dirname(__DIR__) . '/app/models/Artist.php';
$artist = (new Artist())->getById($artistId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Business Verification (KYC) – BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
<style>
  .kyc-wrap { padding: 40px 20px; max-width: 800px; margin: 0 auto; }
  .kyc-section { background: var(--dark-2); border: 1px solid var(--border); border-radius: 16px; padding: 32px; margin-bottom: 24px; }
  .section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
  .section-icon { font-size: 24px; }
  .section-title { font-family: var(--font-head); font-size: 18px; font-weight: 700; margin: 0; }
  .dynamic-row { display: flex; gap: 12px; margin-bottom: 12px; align-items: flex-end; }
  .btn-remove { background: rgba(244,67,54,.1); color: #f44336; border: 1px solid rgba(244,67,54,.3); padding: 10px; border-radius: 8px; cursor: pointer; }
  .btn-add { background: rgba(212,175,55,.1); color: var(--gold); border: 1px dashed var(--gold); padding: 10px 20px; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600; margin-top: 8px; }
  .status-banner { padding: 16px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-weight: 600; }
  .status-none { background: rgba(212,175,55,.1); color: var(--gold); border: 1px solid var(--gold); }
  .status-pending { background: rgba(255,193,7,.1); color: #ffc107; border: 1px solid #ffc107; }
  .status-approved { background: rgba(76,175,80,.1); color: #4caf50; border: 1px solid #4caf50; }
  .status-rejected { background: rgba(244,67,54,.1); color: #f44336; border: 1px solid #f44336; }
</style>
</head>
<body>

<div class="kyc-wrap">
  <div style="margin-bottom:32px">
    <a href="dashboard.php" style="color:var(--text-dim);text-decoration:none;font-size:14px">← Back to Dashboard</a>
    <h1 class="panel-title" style="margin-top:12px">🏢 Business Verification</h1>
    <p style="color:var(--text-muted);font-size:14px">Please provide the required documents to verify your artist/business account.</p>
  </div>

  <?php if($msg): ?><div class="form-success" style="padding:16px;background:rgba(76,175,80,.1);border-radius:12px;margin-bottom:24px"><?=$msg?></div><?php endif; ?>
  <?php if($err): ?><div class="form-error" style="padding:16px;background:rgba(244,67,54,.1);border-radius:12px;margin-bottom:24px;color:#f44336"><?=$err?></div><?php endif; ?>

  <div class="status-banner status-<?= $artist['kyc_status'] ?>">
    <span class="status-icon">
      <?php if($artist['kyc_status'] === 'none'): ?>⚠️<?php elseif($artist['kyc_status'] === 'pending'): ?>⏳<?php elseif($artist['kyc_status'] === 'approved'): ?>✅<?php else: ?>❌<?php endif; ?>
    </span>
    <div>
      Status: <?= ucfirst($artist['kyc_status'] === 'none' ? 'Not Started' : $artist['kyc_status']) ?>
      <?php if($artist['kyc_status'] === 'pending'): ?><div style="font-size:12px;font-weight:400;opacity:0.8">Our team is reviewing your documents. Usually takes 24-48 hours.</div><?php endif; ?>
    </div>
  </div>

  <?php if ($artist['kyc_status'] !== 'approved' && $artist['kyc_status'] !== 'pending'): ?>
  <form method="POST" enctype="multipart/form-data">
    
    <!-- DOCUMENTS -->
    <div class="kyc-section">
      <div class="section-header">
        <span class="section-icon">📄</span>
        <h3 class="section-title">Documents</h3>
      </div>
      <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px">Please upload clear copies of all required documents.</p>
      
      <div class="form-group">
        <label class="form-label">Form 3 *</label>
        <input type="file" name="form_3" class="form-control" accept=".pdf,image/*" required>
      </div>

      <div class="form-group">
        <label class="form-label">Certificate of Incorporation *</label>
        <input type="file" name="incorporation_cert" class="form-control" accept=".pdf,image/*" required>
      </div>
    </div>

    <!-- TAX INFO -->
    <div class="kyc-section">
      <div class="section-header">
        <span class="section-icon">💼</span>
        <h3 class="section-title">Tax Information</h3>
      </div>
      <div class="form-group">
        <label class="form-label">Tax Identification Number (TIN) *</label>
        <input type="text" name="tin" class="form-control" placeholder="C0001234567" required value="<?= htmlspecialchars($artist['tin'] ?? '') ?>">
        <small style="color:var(--text-dim);font-size:11px">Unique identification number issued to taxpayers.</small>
      </div>
    </div>

    <!-- DIRECTORS -->
    <div class="kyc-section">
      <div class="section-header">
        <span class="section-icon">👥</span>
        <h3 class="section-title">Directors</h3>
      </div>
      <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px">Please identify at least 2 directors of your business.</p>
      
      <div id="directors-list">
        <div class="dynamic-row">
          <div style="flex:2"><label class="form-label">Full Name</label><input type="text" name="director_name[]" class="form-control" required></div>
          <div style="flex:1"><label class="form-label">Role</label><input type="text" name="director_role[]" class="form-control" placeholder="Director" required></div>
          <div style="width:42px"></div>
        </div>
      </div>
      <button type="button" class="btn-add" onclick="addDirector()">+ Add Director</button>
    </div>

    <!-- BENEFICIAL OWNERS -->
    <div class="kyc-section">
      <div class="section-header">
        <span class="section-icon">👑</span>
        <h3 class="section-title">Beneficial Owners</h3>
      </div>
      <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px">Identify people who, together, own at least 51% of this business.</p>
      
      <div id="owners-list">
        <div class="dynamic-row">
          <div style="flex:2"><label class="form-label">Full Name</label><input type="text" name="owner_name[]" class="form-control" required></div>
          <div style="flex:1"><label class="form-label">Ownership %</label><input type="number" name="owner_percent[]" class="form-control" placeholder="51" min="1" max="100" required></div>
          <div style="width:42px"></div>
        </div>
      </div>
      <button type="button" class="btn-add" onclick="addOwner()">+ Add Owner</button>
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;padding:16px;font-size:16px;justify-content:center">Submit Verification</button>
  </form>
  <?php else: ?>
    <div class="kyc-section" style="text-align:center">
      <h3 class="section-title">Verification In Progress</h3>
      <p style="color:var(--text-muted);margin-top:12px">
        Thank you for submitting your documents. You will be notified via email once our team has reviewed your application.
      </p>
      <a href="dashboard.php" class="btn btn-primary" style="margin-top:20px;display:inline-flex">Return to Dashboard</a>
    </div>
  <?php endif; ?>
</div>

<script>
function addDirector() {
  const container = document.getElementById('directors-list');
  const div = document.createElement('div');
  div.className = 'dynamic-row';
  div.innerHTML = `
    <div style="flex:2"><label class="form-label">Full Name</label><input type="text" name="director_name[]" class="form-control" required></div>
    <div style="flex:1"><label class="form-label">Role</label><input type="text" name="director_role[]" class="form-control" placeholder="Director" required></div>
    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
  `;
  container.appendChild(div);
}

function addOwner() {
  const container = document.getElementById('owners-list');
  const div = document.createElement('div');
  div.className = 'dynamic-row';
  div.innerHTML = `
    <div style="flex:2"><label class="form-label">Full Name</label><input type="text" name="owner_name[]" class="form-control" required></div>
    <div style="flex:1"><label class="form-label">Ownership %</label><input type="number" name="owner_percent[]" class="form-control" placeholder="51" min="1" max="100" required></div>
    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
  `;
  container.appendChild(div);
}
</script>

</body>
</html>
