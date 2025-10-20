<?php

namespace Modules\Users\Controllers;

use App\Core\Template\TemplateRenderer;
use Modules\Users\Application\UserService;

final class AjaxController
{
    private UserService $userService;
    private TemplateRenderer $renderer;

    public function __construct(UserService $userService, TemplateRenderer $renderer)
    {
        $this->userService = $userService;
        $this->renderer = $renderer;
    }

    public function __invoke(?string $action, array $payload): array
    {
        if ($action === 'edit_window') {
            $userId = isset($payload['user_id']) && is_numeric($payload['user_id']) ? (int) $payload['user_id'] : 0;
            $user = $this->userService->getEditData($userId);
            $html = $this->renderer->render('user_edit', ['user' => $user]);

            return ['html' => $html];
        }

        if ($action === 'edit_update') {
            $data = $this->userService->saveUser($payload);
            if (isset($data['error_msg'])) {
                return ['error_msg' => $data['error_msg']];
            }

            $html = $this->renderer->render('users_table', [
                'users' => $data['items'],
            ]);

            return [
                'html' => $html,
                'paginator' => $data['paginator'],
            ];
        }

        if ($action === 'delete') {
            $data = $this->userService->deleteUser($payload);
            $html = $this->renderer->render('users_table', [
                'users' => $data['items'],
            ]);

            return [
                'html' => $html,
                'paginator' => $data['paginator'],
            ];
        }

        return [];
    }
}