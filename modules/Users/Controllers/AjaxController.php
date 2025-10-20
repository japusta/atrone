<?php

namespace Modules\Users\Controllers;

use Modules\Users\Application\UserService;

class AjaxController
{
    private UserService $service;

    public function __construct(?UserService $service = null)
    {
        $this->service = $service ?? new UserService();
    }

    public function __invoke(?string $action, $payload)
    {
        if ($action === 'edit_window') {
            return $this->editWindow($payload);
        }

        if ($action === 'edit_update') {
            return $this->editUpdate($payload);
        }

        if ($action === 'delete') {
            return $this->delete($payload);
        }

        return [];
    }

    private function editWindow($payload): array
    {
        $userId = isset($payload['user_id']) && is_numeric($payload['user_id']) ? (int) $payload['user_id'] : 0;
        $user = $this->service->getEditData($userId);
        \HTML::assign('user', $user);

        return ['html' => \HTML::fetch('./partials/user_edit.html')];
    }

    private function editUpdate($payload): array
    {
        $result = $this->service->saveUser(is_array($payload) ? $payload : []);
        if (isset($result['error_msg'])) {
            return $result;
        }

        \HTML::assign('users', $result['items']);

        return [
            'html' => \HTML::fetch('./partials/users_table.html'),
            'paginator' => $result['paginator'],
        ];
    }

    private function delete($payload): array
    {
        $result = $this->service->deleteUser(is_array($payload) ? $payload : []);
        \HTML::assign('users', $result['items']);

        return [
            'html' => \HTML::fetch('./partials/users_table.html'),
            'paginator' => $result['paginator'],
        ];
    }
}