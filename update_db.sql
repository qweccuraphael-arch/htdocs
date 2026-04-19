-- Add Paystack fields to artists
ALTER TABLE artists
ADD COLUMN bank_name VARCHAR(100) DEFAULT NULL,
ADD COLUMN bank_code VARCHAR(10) DEFAULT NULL,
ADD COLUMN account_number VARCHAR(30) DEFAULT NULL,
ADD COLUMN account_name VARCHAR(120) DEFAULT NULL,
ADD COLUMN paystack_recipient_code VARCHAR(50) DEFAULT NULL;

-- Create Payouts table
CREATE TABLE IF NOT EXISTS payouts (
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
) ENGINE=InnoDB;

-- Create Song Purchases table (for paid downloads)
CREATE TABLE IF NOT EXISTS song_purchases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    song_id INT UNSIGNED NOT NULL,
    customer_email VARCHAR(120) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reference VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
) ENGINE=InnoDB;
