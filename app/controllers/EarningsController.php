<?php
// app/controllers/EarningsController.php

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__) . '/models/Earnings.php';
require_once dirname(__DIR__) . '/models/Artist.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

class EarningsController {
    private Earnings $earningsModel;
    private Artist   $artistModel;

    public function __construct() {
        $this->earningsModel = new Earnings();
        $this->artistModel   = new Artist();
    }

    /**
     * Send monthly earnings report to all artists (run via cron)
     */
    public function sendMonthlyReports() {
        $artists = $this->artistModel->getAll();
        $period  = date('F Y', strtotime('last month'));
        $month   = 'last_month';

        foreach ($artists as $artist) {
            $amount = $this->earningsModel->totalByArtist($artist['id'], 'month');
            if ($amount > 0) {
                sendEarningsEmail($artist['email'], $artist['name'], $amount, $period);
                if (!empty($artist['phone'])) {
                    smsNewEarnings($artist['phone'], $artist['name'], $amount);
                }
                logActivity('earnings_report', "Sent monthly report to {$artist['name']}", [
                    'artist_id' => $artist['id'],
                    'amount'    => $amount,
                ]);
            }
        }
    }

    public function getArtistSummary(int $artistId): array {
        return [
            'today'  => $this->earningsModel->totalByArtist($artistId, 'today'),
            'week'   => $this->earningsModel->totalByArtist($artistId, 'week'),
            'month'  => $this->earningsModel->totalByArtist($artistId, 'month'),
            'all'    => $this->earningsModel->totalByArtist($artistId, 'all'),
            'chart'  => $this->earningsModel->getMonthlyChart($artistId),
            'recent' => $this->earningsModel->getByArtist($artistId),
        ];
    }
}
