#!/usr/bin/env php
<?php
/**
 * BeatWave Cron Jobs
 *
 * Add to crontab:
 *   # Monthly earnings reports (1st of each month at 9 AM)
 *   0 9 1 * * php /path/to/music-platform/cron.php monthly_reports
 *
 *   # Daily cleanup of old rate-limit files
 *   0 3 * * * php /path/to/music-platform/cron.php cleanup_logs
 */

define('CRON_RUN', true);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/helpers/functions.php';
require_once __DIR__ . '/app/models/Earnings.php';
require_once __DIR__ . '/app/models/Artist.php';
require_once __DIR__ . '/app/controllers/EarningsController.php';

$task = $argv[1] ?? 'help';

switch ($task) {
    case 'monthly_reports':
        echo "[" . date('Y-m-d H:i:s') . "] Sending monthly earnings reports…\n";
        (new EarningsController())->sendMonthlyReports();
        echo "[" . date('Y-m-d H:i:s') . "] Done.\n";
        break;

    case 'cleanup_logs':
        echo "[" . date('Y-m-d H:i:s') . "] Cleaning up old log files…\n";
        $cutoff = time() - (7 * 86400); // older than 7 days
        foreach (glob(LOG_PATH . '*.log') as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                echo "  Deleted: " . basename($file) . "\n";
            }
        }
        foreach (glob(LOG_PATH . 'ratelimit_*.json') as $file) {
            if (filemtime($file) < time() - 3600) {
                unlink($file);
            }
        }
        echo "[" . date('Y-m-d H:i:s') . "] Cleanup done.\n";
        break;

    default:
        echo "Usage: php cron.php [task]\n";
        echo "Tasks:\n";
        echo "  monthly_reports  – Send earnings emails+SMS to all artists\n";
        echo "  cleanup_logs     – Remove old log files\n";
}
