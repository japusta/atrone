<?php

namespace Modules\Plots\Controllers;

use App\Core\Http\Request;
use App\Core\Template\PageView;
use Modules\Plots\Application\PlotService;

final class PageController
{
    private PlotService $plotService;

    public function __construct(PlotService $plotService)
    {
        $this->plotService = $plotService;
    }

    public function __invoke(Request $request): PageView
    {
        $offset = $request->getQueryParam('offset', 0);
        $offset = is_numeric($offset) ? (int) $offset : 0;
        $search = (string) $request->getQueryParam('search', '');

        $data = $this->plotService->getPaginatedList($search, $offset);
        $data['plots'] = $data['items'];

        return new PageView('index', 'home', 'plots', $data);
    }
}
