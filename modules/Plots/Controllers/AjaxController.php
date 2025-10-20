<?php

namespace Modules\Plots\Controllers;

use App\Core\Template\TemplateRenderer;
use Modules\Plots\Application\PlotService;

final class AjaxController
{
    private PlotService $plotService;
    private TemplateRenderer $renderer;

    public function __construct(PlotService $plotService, TemplateRenderer $renderer)
    {
        $this->plotService = $plotService;
        $this->renderer = $renderer;
    }

    public function __invoke(?string $action, array $payload): array
    {
        if ($action === 'edit_window') {
            $plotId = isset($payload['plot_id']) && is_numeric($payload['plot_id']) ? (int) $payload['plot_id'] : 0;
            $plot = $this->plotService->getEditData($plotId);
            $html = $this->renderer->render('plot_edit', ['plot' => $plot]);

            return ['html' => $html];
        }

        if ($action === 'edit_update') {
            $data = $this->plotService->savePlot($payload);
            $html = $this->renderer->render('plots_table', [
                'plots' => $data['items'],
            ]);

            return [
                'html' => $html,
                'paginator' => $data['paginator'],
            ];
        }

        return [];
    }
}