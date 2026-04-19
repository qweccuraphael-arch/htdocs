<?php
// api/stats.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/models/Song.php';
require_once dirname(__DIR__) . '/app/models/Download.php';

$songModel     = new Song();
$downloadModel = new Download();

echo json_encode([
    'success'  => true,
    'trending' => $songModel->getTrending(10),
    'featured' => $songModel->getFeatured(6),
    'genres'   => $songModel->getGenres(),
    'stats'    => [
        'total_downloads' => $downloadModel->countTotal(),
        'today_downloads' => $downloadModel->countToday(),
    ],
]);
