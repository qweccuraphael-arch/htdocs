<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/helpers/security.php';
require_once dirname(__DIR__) . '/app/models/Payout.php';
require_once dirname(__DIR__) . '/app/models/Artist.php';
requireAdmin();

$payoutModel = new Payout();
$artistModel = new Artist();
$payouts = $payoutModel->getAll();
$pendingTotal = $payoutModel->getPendingTotal();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $payoutId = (int) ($_POST['payout_id'] ?? 0);
    $action = $_POST['action'];
    $notes = sanitize($_POST['admin_notes'] ?? '');
    $targetPayout = null;

    foreach ($payouts as $payout) {
        if ((int) $payout['id'] === $payoutId) {
            $targetPayout = $payout;
            break;
        }
    }

    if ($action === 'approve') {
        if ($payoutModel->updateStatus($payoutId, 'approved', $notes)) {
            $success = 'Withdrawal request approved.';
        } else {
            $error = 'Failed to approve payout.';
        }
    } elseif ($action === 'reject') {
        if ($payoutModel->updateStatus($payoutId, 'rejected', $notes)) {
            $success = 'Withdrawal request rejected.';
        } else {
            $error = 'Failed to reject payout.';
        }
    } elseif ($action === 'mark_paid') {
        if ($payoutModel->updateStatus($payoutId, 'paid', $notes)) {
            $success = 'Withdrawal marked as paid.';
        } else {
            $error = 'Failed to mark payout as paid.';
        }
    }

    if ($targetPayout && $error === '') {
        $artist = $artistModel->getById($targetPayout['artist_id']);
        if ($action === 'approve') {
            sendPayoutNotificationEmail($targetPayout['artist_email'], $targetPayout['artist_name'], (float) $targetPayout['amount'], 'approved', $targetPayout['payment_method'], $notes);
            if (!empty($artist['phone'])) {
                sendPayoutNotificationSMS($artist['phone'], $artist['name'], (float) $targetPayout['amount'], 'approved', $targetPayout['payment_method']);
            }
        } elseif ($action === 'reject') {
            sendPayoutNotificationEmail($targetPayout['artist_email'], $targetPayout['artist_name'], (float) $targetPayout['amount'], 'rejected', $targetPayout['payment_method'], $notes);
            if (!empty($artist['phone'])) {
                sendPayoutNotificationSMS($artist['phone'], $artist['name'], (float) $targetPayout['amount'], 'rejected', $targetPayout['payment_method']);
            }
        } elseif ($action === 'mark_paid') {
            sendPayoutNotificationEmail($targetPayout['artist_email'], $targetPayout['artist_name'], (float) $targetPayout['amount'], 'paid', $targetPayout['payment_method'], $notes);
            if (!empty($artist['phone'])) {
                sendPayoutNotificationSMS($artist['phone'], $artist['name'], (float) $targetPayout['amount'], 'paid', $targetPayout['payment_method']);
            }
        }
    }

    if ($error === '') {
        $payouts = $payoutModel->getAll();
        $pendingTotal = $payoutModel->getPendingTotal();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payout Management - BeatWave Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<div class="panel-layout">
  <?php include '_sidebar.php'; ?>
  <main class="panel-main">
    <h1 class="panel-title">Payout Management</h1>

    <?php if ($error): ?><p class="form-error" style="margin-bottom:16px;padding:10px;background:rgba(244,67,54,.1);border-radius:8px"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p style="margin-bottom:16px;padding:10px;background:rgba(76,175,80,.1);border-radius:8px;color:#4caf50"><?= htmlspecialchars($success) ?></p><?php endif; ?>

    <div class="stats-grid" style="margin-bottom:24px">
      <div class="stat-card"><div class="stat-icon">P</div><div class="stat-value"><?= formatMoney($pendingTotal) ?></div><div class="stat-label">Pending Amount</div></div>
      <div class="stat-card"><div class="stat-icon">R</div><div class="stat-value"><?= count(array_filter($payouts, fn($p) => $p['status'] === 'pending')) ?></div><div class="stat-label">Pending Requests</div></div>
      <div class="stat-card"><div class="stat-icon">A</div><div class="stat-value"><?= count(array_filter($payouts, fn($p) => $p['status'] === 'approved')) ?></div><div class="stat-label">Approved Awaiting Payment</div></div>
    </div>

    <div style="background:var(--dark-2);border:1px solid var(--border);border-radius:16px;padding:24px">
      <h3 style="margin-bottom:16px">Withdrawal Requests</h3>

      <?php if (empty($payouts)): ?>
      <p style="color:var(--text-dim)">No payout requests yet.</p>
      <?php else: ?>
      <div style="overflow-x:auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Artist</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Details</th>
              <th>Status</th>
              <th>Requested</th>
              <th>Notes</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payouts as $payout): ?>
            <?php $details = json_decode($payout['payment_details'], true) ?: []; ?>
            <tr>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($payout['artist_name']) ?></div>
                <div style="font-size:12px;color:var(--text-dim)"><?= htmlspecialchars($payout['artist_email']) ?></div>
              </td>
              <td><?= formatMoney((float) $payout['amount']) ?></td>
              <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $payout['payment_method']))) ?></td>
              <td style="font-size:12px;color:var(--text-muted)">
                <?php if ($payout['payment_method'] === 'bank'): ?>
                <?= htmlspecialchars($details['bank_name'] ?? '') ?><br><?= htmlspecialchars($details['account_number'] ?? '') ?>
                <?php elseif ($payout['payment_method'] === 'mobile_money'): ?>
                <?= htmlspecialchars($details['mobile_network'] ?? '') ?><br><?= htmlspecialchars($details['mobile_number'] ?? '') ?>
                <?php elseif ($payout['payment_method'] === 'paypal'): ?>
                <?= htmlspecialchars($details['paypal_email'] ?? '') ?>
                <?php endif; ?>
              </td>
              <td><span class="status status-<?= htmlspecialchars($payout['status']) ?>"><?= htmlspecialchars(ucfirst($payout['status'])) ?></span></td>
              <td><?= htmlspecialchars(date('M j, Y', strtotime($payout['requested_at']))) ?></td>
              <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($payout['admin_notes'] ?? '') ?></td>
              <td style="min-width:220px">
                <?php if ($payout['status'] === 'pending'): ?>
                <form method="POST" style="display:grid;gap:8px">
                  <input type="hidden" name="payout_id" value="<?= (int) $payout['id'] ?>">
                  <input type="text" name="admin_notes" placeholder="Notes for artist" style="padding:8px;border:1px solid var(--border);border-radius:6px;background:var(--dark-1);color:var(--text)">
                  <div style="display:flex;gap:8px">
                    <button type="submit" name="action" value="approve" class="btn btn-sm" style="background:#4caf50">Approve</button>
                    <button type="submit" name="action" value="reject" class="btn btn-sm" style="background:#f44336">Reject</button>
                  </div>
                </form>
                <?php elseif ($payout['status'] === 'approved'): ?>
                <form method="POST" style="display:grid;gap:8px">
                  <input type="hidden" name="payout_id" value="<?= (int) $payout['id'] ?>">
                  <input type="text" name="admin_notes" placeholder="Payment reference or note" style="padding:8px;border:1px solid var(--border);border-radius:6px;background:var(--dark-1);color:var(--text)">
                  <button type="submit" name="action" value="mark_paid" class="btn btn-sm" style="background:#2196f3">Mark Paid</button>
                </form>
                <?php else: ?>
                <span style="font-size:12px;color:var(--text-dim)">No actions available</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<style>
.status {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}
.status-pending { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
.status-approved { background: rgba(76, 175, 80, 0.1); color: #4caf50; }
.status-paid { background: rgba(33, 150, 243, 0.1); color: #2196f3; }
.status-rejected { background: rgba(244, 67, 54, 0.1); color: #f44336; }

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  color: white;
}
</style>
</body>
</html>
