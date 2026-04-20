<?php
// api/get_songs.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/models/Song.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';

$page   = max(1, (int)($_GET['page']   ?? 1));
$limit  = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$search = $_GET['q']     ?? '';
$genre  = $_GET['genre'] ?? '';

$model  = new Song();
$songs  = $model->getAll($page, $limit, $search, $genre);
$total  = $model->countAll($search, $genre);

echo json_encode([
    'success' => true,
    'data'    => $songs,
    'meta'    => [
        'page'        => $page,
        'limit'       => $limit,
        'total'       => $total,
        'total_pages' => ceil($total / $limit),
    ],
]);
