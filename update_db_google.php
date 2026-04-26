<?php
require 'music-platform3/config/db.php';
try {
    $db = getDB();
    $db->exec("ALTER TABLE artists ADD COLUMN google_id VARCHAR(255) DEFAULT NULL AFTER email");
    $db->exec("ALTER TABLE artists ADD UNIQUE INDEX idx_google_id (google_id)");
    echo "SUCCESS: Added google_id column and index.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
