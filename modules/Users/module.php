<?php

use Modules\Users\Controllers\PageController;
use Modules\Users\Controllers\AjaxController;

return [
    'pages' => [
        'users' => PageController::class,
    ],
    'api' => [
        'user' => AjaxController::class,
    ],
];
