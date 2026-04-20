<?php
require_once 'config/db.php';

$new_password = 'Nse158@25';
$hashed = password_hash($new_password, PASSWORD_BCRYPT);

$db = getDB();
$stmt = $db->prepare("UPDATE admins SET password = ? WHERE username = ?");
$result = $stmt->execute([$hashed, 'admin']);

if ($result) {
    echo "✓ Password changed successfully to: Nse158@25\n";
    echo "Hashed password: " . $hashed . "\n";
} else {
    echo "✗ Error updating password";
}
?>
