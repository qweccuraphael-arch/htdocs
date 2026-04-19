<?php
require_once 'config/db.php';
$pdo = getDB();
echo "--- ARTISTS TABLE ---\n";
$stmt = $pdo->query("DESCRIBE artists");
while($row = $stmt->fetch()) { echo $row['Field'] . " (" . $row['Type'] . ")\n"; }

echo "\n--- SONGS TABLE ---\n";
$stmt = $pdo->query("DESCRIBE songs");
while($row = $stmt->fetch()) { echo $row['Field'] . " (" . $row['Type'] . ")\n"; }

echo "\n--- EARNINGS TABLE ---\n";
$stmt = $pdo->query("DESCRIBE earnings");
while($row = $stmt->fetch()) { echo $row['Field'] . " (" . $row['Type'] . ")\n"; }
