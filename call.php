<?php

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo '';
    exit;
}

require __DIR__.'/cfg/general.inc.php';
require __DIR__.'/src/Core/Autoloader.php';

App\Core\Autoloader::register();

$app = new App\Core\Application();

$location = $_POST['location'] ?? [];
$payload = $_POST['data'] ?? [];

if (!is_array($location)) {
    $location = [];
}

if (!is_array($payload)) {
    $payload = [];
}

$response = $app->handleAjaxRequest($location, $payload);
$response->send();
exit;
