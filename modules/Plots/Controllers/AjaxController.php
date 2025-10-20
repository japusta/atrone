<?php

namespace Modules\Plots\Controllers;

class AjaxController
{
    public static function handle(?string $action, $payload)
    {
        if ($action === 'edit_window') {
            return \Plot::plot_edit_window($payload);
        }

        if ($action === 'edit_update') {
            return \Plot::plot_edit_update($payload);
        }

        return '';
    }
}