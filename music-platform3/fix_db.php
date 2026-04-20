<?php
require_once 'config/db.php';

try {
    $db = getDB();
    echo "--- Database Migration Check ---\n";

    // 1. Check/Add columns to artists
    $columnsToAdd = [
        'bank_name' => "VARCHAR(100) DEFAULT NULL",
        'bank_code' => "VARCHAR(10) DEFAULT NULL",
        'account_number' => "VARCHAR(30) DEFAULT NULL",
        'account_name' => "VARCHAR(120) DEFAULT NULL",
        'paystack_recipient_code' => "VARCHAR(50) DEFAULT NULL"
    ];

    $existingColumns = $db->query("DESCRIBE artists")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($columnsToAdd as $col => $definition) {
        if (!in_array($col, $existingColumns)) {
            $db->exec("ALTER TABLE artists ADD COLUMN $col $definition");
            echo "✅ Added column: $col\n";
        } else {
            echo "ℹ️ Column $col already exists.\n";
        }
    }

    // 2. Create Payouts table
    $db->exec("CREATE TABLE IF NOT EXISTS payouts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        artist_id INT UNSIGNED NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(5) DEFAULT 'GHS',
        status ENUM('pending', 'processing', 'success', 'failed') DEFAULT 'pending',
        reference VARCHAR(100) UNIQUE NOT NULL,
        paystack_transfer_code VARCHAR(100) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "✅ Payouts table checked/created.\n";

    // 3. Create Song Purchases table
    $db->exec("CREATE TABLE IF NOT EXISTS song_purchases (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        song_id INT UNSIGNED NOT NULL,
        customer_email VARCHAR(120) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        reference VARCHAR(100) UNIQUE NOT NULL,
        status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "✅ Song Purchases table checked/created.\n";

    echo "--- Done ---\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
