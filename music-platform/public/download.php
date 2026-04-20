<?php
// public/download.php

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/controllers/DownloadController.php';
require_once dirname(__DIR__) . '/app/helpers/security.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: index.php');
    exit;
}

// Referer check — must come from ad_download.php
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (!str_contains($referer, 'ad_download.php') && !str_contains($referer, APP_URL)) {
    header('Location: ad_download.php?id=' . $id);
    exit;
}

(new DownloadController())->handleDownload($id);
