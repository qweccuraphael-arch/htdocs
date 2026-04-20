<?php
require_once 'config/app.php';
require_once 'app/controllers/ArtistController.php';

$ctrl = new ArtistController();
$testData = [
    'name' => 'Test Artist ' . time(),
    'email' => 'test' . time() . '@example.com',
    'password' => 'password123',
    'phone' => '0249740636',
    'bio' => 'Test bio'
];
$r = $ctrl->register($testData, []);
echo json_encode($r);
