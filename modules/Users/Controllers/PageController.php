<?php

namespace Modules\Users\Controllers;

use App\Core\Http\Request;
use App\Core\Template\PageView;
use Modules\Users\Application\UserService;

final class PageController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function __invoke(Request $request): PageView
    {
        $offset = $request->getQueryParam('offset', 0);
        $offset = is_numeric($offset) ? (int) $offset : 0;
        $search = (string) $request->getQueryParam('search', '');

        $data = $this->userService->getPaginatedList($search, $offset);
        $data['users'] = $data['items'];

        return new PageView('index', 'home', 'users', $data);
    }
}
