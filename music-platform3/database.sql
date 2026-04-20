-- ============================================================
--  BeatWave – Complete Database Schema
--  Run this once in your MySQL / MariaDB database
-- ============================================================

CREATE DATABASE IF NOT EXISTS music_platform
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE music_platform;

-- ── Admins ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(60)  NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,          -- bcrypt
  email      VARCHAR(120) NOT NULL UNIQUE,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin: username=admin  password=Admin@2025
-- (change immediately after first login!)
INSERT INTO admins (username, password, email)
VALUES ('admin',
        '$2y$12$92TN.Q5QpxQjqV8wRkVvEe6iF0YRuqN/zJkpJqKa5HgNcIPd02dbu',
        'admin@yourdomain.com');

-- ── Admin Password Reset Tokens ──────────────────────────────
CREATE TABLE IF NOT EXISTS admin_reset_tokens (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id   INT UNSIGNED NOT NULL,
  token      VARCHAR(64)  NOT NULL UNIQUE,
  expires_at DATETIME     NOT NULL,
  used_at    DATETIME     NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
  INDEX idx_token (token),
  INDEX idx_expires (expires_at),
  INDEX idx_used (used_at)
) ENGINE=InnoDB;

-- ── Artists ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS artists (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(120) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  phone      VARCHAR(20)  DEFAULT NULL,
  bio        TEXT         DEFAULT NULL,
  photo      VARCHAR(255) DEFAULT NULL,
  status     ENUM('active','suspended') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_status (status)
) ENGINE=InnoDB;

-- ── Artist Password Reset Tokens ─────────────────────────────
CREATE TABLE IF NOT EXISTS artist_reset_tokens (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  artist_id  INT UNSIGNED NOT NULL,
  token      VARCHAR(64)  NOT NULL UNIQUE,
  expires_at DATETIME     NOT NULL,
  used_at    DATETIME     NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE,
  INDEX idx_token (token),
  INDEX idx_expires (expires_at),
  INDEX idx_used (used_at)
) ENGINE=InnoDB;

-- ── Songs ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS songs (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  artist_id      INT UNSIGNED NOT NULL,
  title          VARCHAR(200) NOT NULL,
  genre          VARCHAR(80)  NOT NULL,
  album          VARCHAR(150) DEFAULT NULL,
  year           YEAR         DEFAULT NULL,
  file_path      VARCHAR(255) NOT NULL,      -- filename in storage/music/
  cover_art      VARCHAR(255) DEFAULT NULL,  -- filename in storage/covers/
  duration       INT UNSIGNED DEFAULT 0,     -- seconds
  file_size      BIGINT UNSIGNED DEFAULT 0,  -- bytes
  download_count INT UNSIGNED NOT NULL DEFAULT 0,
  is_featured    TINYINT(1)   NOT NULL DEFAULT 0,
  status         ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE,
  INDEX idx_artist  (artist_id),
  INDEX idx_status  (status),
  INDEX idx_genre   (genre),
  INDEX idx_featured(is_featured),
  INDEX idx_downloads(download_count DESC)
) ENGINE=InnoDB;

-- ── Downloads ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS downloads (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  song_id    INT UNSIGNED NOT NULL,
  artist_id  INT UNSIGNED NOT NULL,
  ip_address VARCHAR(45)  NOT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (song_id)   REFERENCES songs(id)   ON DELETE CASCADE,
  FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE,
  INDEX idx_song      (song_id),
  INDEX idx_artist    (artist_id),
  INDEX idx_created   (created_at),
  INDEX idx_date_only ((DATE(created_at)))
) ENGINE=InnoDB;

-- ── Earnings ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS earnings (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  artist_id  INT UNSIGNED   NOT NULL,
  song_id    INT UNSIGNED   NOT NULL,
  amount     DECIMAL(10,4)  NOT NULL DEFAULT 0.0000,
  beneficiary_type ENUM('artist','admin') NOT NULL DEFAULT 'artist',
  type       ENUM('download','bonus','manual') NOT NULL DEFAULT 'download',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE,
  FOREIGN KEY (song_id)   REFERENCES songs(id)   ON DELETE CASCADE,
  INDEX idx_artist  (artist_id),
  INDEX idx_song    (song_id),
  INDEX idx_created (created_at),
  INDEX idx_month   (artist_id, created_at),
  INDEX idx_beneficiary (beneficiary_type, created_at)
) ENGINE=InnoDB;

-- ── Useful views ─────────────────────────────────────────────

-- Artist performance overview
CREATE OR REPLACE VIEW v_artist_stats AS
SELECT
  a.id,
  a.name,
  a.email,
  a.phone,
  a.status,
  COUNT(DISTINCT s.id)              AS total_songs,
  COALESCE(SUM(s.download_count),0) AS total_downloads,
  COALESCE(SUM(e.amount),0)         AS total_earnings
FROM artists a
LEFT JOIN songs s   ON s.artist_id = a.id AND s.status = 'approved'
LEFT JOIN earnings e ON e.artist_id = a.id
GROUP BY a.id;

-- Top songs
CREATE OR REPLACE VIEW v_top_songs AS
SELECT
  s.id, s.title, s.genre, s.download_count, s.cover_art, s.status,
  a.name AS artist_name
FROM songs s
JOIN artists a ON s.artist_id = a.id
WHERE s.status = 'approved'
ORDER BY s.download_count DESC;
