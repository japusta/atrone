<?php

use Modules\Plots\Controllers\PageController;
use Modules\Plots\Controllers\AjaxController;

return [
    'pages' => [
        'plots' => PageController::class,
    ],
    'api' => [
        'plot' => AjaxController::class,
    ],
];
