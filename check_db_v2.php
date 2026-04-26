<?php
require 'music-platform3/config/db.php';
try {
    $stmt = getDB()->query("DESCRIBE artists");
    while($row = $stmt->fetch()) {
        echo $row['Field'] . ", ";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
