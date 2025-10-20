<?php

require __DIR__.'/cfg/general.inc.php';
require __DIR__.'/src/Core/Autoloader.php';

App\Core\Autoloader::register();

$app = new App\Core\Application();
$app->run();
