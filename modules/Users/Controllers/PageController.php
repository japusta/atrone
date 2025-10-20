<?php

namespace Modules\Users\Controllers;

class PageController
{
    public static function index(array $query): void
    {
        $offset = $query['offset'] ?? 0;
        $search = $query['search'] ?? '';

        $users = \User::users_list([
            'mode' => 'page',
            'offset' => $offset,
            'search' => $search,
        ]);

        \HTML::assign('users', $users['items']);
        \HTML::assign('paginator', $users['paginator']);
        \HTML::assign('search', $search);
        \HTML::assign('offset', $offset);
        \HTML::assign('section', 'users.html');
        \HTML::assign('main_content', 'home.html');
    }
}