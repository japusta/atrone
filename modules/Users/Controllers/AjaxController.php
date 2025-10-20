<?php

namespace Modules\Users\Controllers;

class AjaxController
{
    public static function handle(?string $action, $payload)
    {
        if ($action === 'edit_window') {
            return \User::user_edit_window($payload);
        }

        if ($action === 'edit_update') {
            return \User::user_edit_update($payload);
        }

        if ($action === 'delete') {
            return \User::user_delete($payload);
        }

        return [];
    }
}