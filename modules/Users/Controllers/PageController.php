<?php

namespace Modules\Users\Controllers;

use Modules\Users\Application\UserService;

class PageController
{
    private UserService $service;

    public function __construct(?UserService $service = null)
    {
        $this->service = $service ?? new UserService();
    }

    public function __invoke(array $query): void
    {
        $offset = isset($query['offset']) && is_numeric($query['offset']) ? (int) $query['offset'] : 0;
        $search = isset($query['search']) ? (string) $query['search'] : '';

        $users = $this->service->getPaginatedList($search, $offset);

        \HTML::assign('users', $users['items']);
        \HTML::assign('paginator', $users['paginator']);
        \HTML::assign('search', $users['search']);
        \HTML::assign('offset', $users['offset']);
        \HTML::assign('section', 'users.html');
        \HTML::assign('main_content', 'home.html');
    }
}