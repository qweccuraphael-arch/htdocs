<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/helpers/security.php';
require_once dirname(__DIR__) . '/app/helpers/payments.php';
require_once dirname(__DIR__) . '/app/models/Artist.php';
require_once dirname(__DIR__) . '/app/models/Payout.php';
requireArtist();

$artistId = currentArtistId();
$artistModel = new Artist();
$payoutModel = new Payout();

$artistData = $artistModel->getById($artistId);
$paymentDetails = $artistModel->getPaymentDetails($artistId);
$availableBalance = $payoutModel->getAvailableBalance($artistId);
$payouts = $payoutModel->getByArtist($artistId);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_payment'])) {
        $paymentData = [
            'payment_method' => sanitize($_POST['payment_method'] ?? ''),
            'bank_name' => sanitize($_POST['bank_name'] ?? ''),
            'account_number' => sanitize($_POST['account_number'] ?? ''),
            'account_name' => sanitize($_POST['account_name'] ?? ''),
            'mobile_network' => sanitize($_POST['mobile_network'] ?? ''),
            'mobile_number' => sanitize($_POST['mobile_number'] ?? ''),
            'paypal_email' => sanitize($_POST['paypal_email'] ?? ''),
        ];

        if ($paymentData['payment_method'] === 'bank') {
            if ($paymentData['bank_name'] === '' || $paymentData['account_number'] === '' || $paymentData['account_name'] === '') {
                $error = 'Please fill in all bank details.';
            }
        } elseif ($paymentData['payment_method'] === 'mobile_money') {
            if ($paymentData['mobile_network'] === '' || $paymentData['mobile_number'] === '') {
                $error = 'Please fill in all mobile money details.';
            }
        } elseif ($paymentData['payment_method'] === 'paypal') {
            if ($paymentData['paypal_email'] === '' || !filter_var($paymentData['paypal_email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Please provide a valid PayPal email.';
            }
        } else {
            $error = 'Please select a payment method.';
        }

        if ($error === '') {
            $paymentData['payment_verified'] = 1; // Auto-verify for easier access
            if ($artistModel->updatePaymentDetails($artistId, $paymentData)) {
                // Clear recipient code on update to force re-creation
                $artistModel->update($artistId, ['paystack_recipient_code' => null]);
                $paymentDetails = $artistModel->getPaymentDetails($artistId);
                $success = 'Payment details saved successfully. You can now withdraw your earnings.';
            } else {
                $error = 'Failed to update payment details.';
            }
        }
    } elseif (isset($_POST['request_withdrawal'])) {
        $amount = (float) ($_POST['amount'] ?? 0);

        if ($amount <= 0) {
            $error = 'Please enter a valid amount.';
        } elseif ($amount > $availableBalance) {
            $error = 'Insufficient balance.';
        } elseif (!$paymentDetails || $paymentDetails['payment_method'] === 'none' || empty($paymentDetails['payment_method'])) {
            $error = 'Please set your payment details first.';
        } else {
            try {
                // Manual Flow: Record as pending
                $payoutModel->request($artistId, $amount, $paymentDetails['payment_method'], $paymentDetails);
                
                $paymentLabel = ucfirst(str_replace('_', ' ', $paymentDetails['payment_method']));
                $success = 'Withdrawal request for ' . formatMoney($amount) . ' has been submitted. Admin will process it soon and notify you via SMS/Email.';
                
                $availableBalance = $payoutModel->getAvailableBalance($artistId);
                $payouts = $payoutModel->getByArtist($artistId);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Artist Withdrawals - BeatWave</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<div class="panel-layout">
  <?php include '_sidebar.php'; ?>
  <main class="panel-main">
    <h1 class="panel-title">Artist Withdrawals</h1>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="balance-card">
      <h2>Available Balance</h2>
      <div class="balance-amount"><?= formatMoney($availableBalance) ?></div>
      <p style="margin-top:10px;font-size:14px;color:#666;">You can withdraw your earnings once you have set your payment details.</p>

      <?php if ($availableBalance > 0 && $paymentDetails && ($paymentDetails['payment_method'] ?? 'none') !== 'none'): ?>
      <form method="POST" style="margin-top:20px">
        <div class="form-group">
          <label>Withdraw Amount</label>
          <div style="display:flex;gap:10px">
            <input name="amount" type="number" step="0.01" min="1" max="<?= htmlspecialchars((string) $availableBalance) ?>" value="<?= htmlspecialchars((string) $availableBalance) ?>" class="form-control" style="flex:1">
            <button type="submit" name="request_withdrawal" class="btn btn-success" style="padding:12px 24px;font-size:16px">Withdraw Now</button>
          </div>
        </div>
      </form>
      <?php elseif ($availableBalance > 0): ?>
      <p style="margin-top:16px;color:#ffc107">Please set your payment details below to enable withdrawals.</p>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>Payment Details</h3>
      <form method="POST">
        <div class="form-group">
          <label>Payment Method</label>
          <select name="payment_method" class="form-control" required>
            <option value="">Select Payment Method</option>
            <option value="bank" <?= ($paymentDetails['payment_method'] ?? '') === 'bank' ? 'selected' : '' ?>>Bank Transfer</option>
            <option value="mobile_money" <?= ($paymentDetails['payment_method'] ?? '') === 'mobile_money' ? 'selected' : '' ?>>Mobile Money</option>
            <option value="paypal" <?= ($paymentDetails['payment_method'] ?? '') === 'paypal' ? 'selected' : '' ?>>PayPal</option>
          </select>
        </div>

        <div id="bank-details" style="display: <?= ($paymentDetails['payment_method'] ?? '') === 'bank' ? 'block' : 'none' ?>;">
          <div class="form-group">
            <label>Bank Name</label>
            <input name="bank_name" type="text" class="form-control" value="<?= htmlspecialchars($paymentDetails['bank_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Account Number</label>
            <input name="account_number" type="text" class="form-control" value="<?= htmlspecialchars($paymentDetails['account_number'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Account Name</label>
            <input name="account_name" type="text" class="form-control" value="<?= htmlspecialchars($paymentDetails['account_name'] ?? '') ?>">
          </div>
        </div>

        <div id="mobile-details" style="display: <?= ($paymentDetails['payment_method'] ?? '') === 'mobile_money' ? 'block' : 'none' ?>;">
          <div class="form-group">
            <label>Network</label>
            <select name="mobile_network" class="form-control">
              <option value="">Select Network</option>
              <option value="mtn" <?= ($paymentDetails['mobile_network'] ?? '') === 'mtn' ? 'selected' : '' ?>>MTN</option>
              <option value="vodafone" <?= ($paymentDetails['mobile_network'] ?? '') === 'vodafone' ? 'selected' : '' ?>>Vodafone</option>
              <option value="airteltigo" <?= ($paymentDetails['mobile_network'] ?? '') === 'airteltigo' ? 'selected' : '' ?>>AirtelTigo</option>
            </select>
          </div>
          <div class="form-group">
            <label>Mobile Number</label>
            <input name="mobile_number" type="tel" class="form-control" value="<?= htmlspecialchars($paymentDetails['mobile_number'] ?? '') ?>" placeholder="e.g. 0241234567">
          </div>
        </div>

        <div id="paypal-details" style="display: <?= ($paymentDetails['payment_method'] ?? '') === 'paypal' ? 'block' : 'none' ?>;">
          <div class="form-group">
            <label>PayPal Email</label>
            <input name="paypal_email" type="email" class="form-control" value="<?= htmlspecialchars($paymentDetails['paypal_email'] ?? '') ?>">
          </div>
        </div>

        <button type="submit" name="update_payment" class="btn btn-primary">Save Payment Details</button>
      </form>
    </div>

    <?php if (!empty($payouts)): ?>
    <div class="card">
      <h3>Withdrawal History</h3>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payouts as $payout): ?>
          <tr>
            <td><?= htmlspecialchars(date('M j, Y', strtotime($payout['requested_at'] ?? ''))) ?></td>
            <td><?= formatMoney((float) ($payout['amount'] ?? 0)) ?></td>
            <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $payout['payment_method'] ?? ''))) ?></td>
            <td><?= htmlspecialchars(ucfirst($payout['status'] ?? 'pending')) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const paymentMethodSelect = document.querySelector('select[name="payment_method"]');
  const bankDetails = document.getElementById('bank-details');
  const mobileDetails = document.getElementById('mobile-details');
  const paypalDetails = document.getElementById('paypal-details');

  function togglePaymentDetails() {
    const method = paymentMethodSelect.value;
    bankDetails.style.display = method === 'bank' ? 'block' : 'none';
    mobileDetails.style.display = method === 'mobile_money' ? 'block' : 'none';
    paypalDetails.style.display = method === 'paypal' ? 'block' : 'none';
  }

  paymentMethodSelect.addEventListener('change', togglePaymentDetails);
});
</script>
</body>
</html>
