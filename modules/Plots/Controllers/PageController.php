<?php

namespace Modules\Plots\Controllers;

class PageController
{
    public static function index(array $query): void
    {
        $offset = $query['offset'] ?? 0;
        $search = $query['search'] ?? '';

        $plots = \Plot::plots_list([
            'mode' => 'page',
            'offset' => $offset,
            'search' => $search,
        ]);

        \HTML::assign('plots', $plots['items']);
        \HTML::assign('paginator', $plots['paginator']);
        \HTML::assign('search', $search);
        \HTML::assign('offset', $offset);
        \HTML::assign('section', 'plots.html');
        \HTML::assign('main_content', 'home.html');
    }
}