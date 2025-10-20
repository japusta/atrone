<?php

namespace Modules\Plots\Application;

use App\Core\Support\Clock;
use App\Core\Support\Paginator;
use App\Core\Support\Sanitizer;
use Modules\Plots\Domain\PlotRepository;
use Modules\Users\Application\UserService;

final class PlotService
{
    private const PAGE_SIZE = 20;

    private PlotRepository $repository;
    private UserService $userService;
    private Paginator $paginator;
    private Clock $clock;
    private Sanitizer $sanitizer;

    public function __construct(PlotRepository $repository, UserService $userService, Paginator $paginator, Clock $clock, Sanitizer $sanitizer)
    {
        $this->repository = $repository;
        $this->userService = $userService;
        $this->paginator = $paginator;
        $this->clock = $clock;
        $this->sanitizer = $sanitizer;
    }

    public function getPaginatedList(string $search, int $offset): array
    {
        $normalizedOffset = max(0, $offset);
        [$items, $total] = $this->repository->search($search, $normalizedOffset, self::PAGE_SIZE);

        $items = array_map(function (array $item) {
            $item['status_str'] = $this->statusToString($item['status']);
            $item['price'] = number_format((int) $item['price_raw'], 0, '', ' ');
            $item['users'] = $item['number'] !== '' ? $this->userService->getOwnersByPlot($item['number']) : [];
            return $item;
        }, $items);

        $searchQuery = trim($search);
        $path = 'plots?';
        if ($searchQuery !== '') {
            $path .= 'search='.urlencode($searchQuery).'&';
        }

        $paginator = $this->paginator->render($total, $normalizedOffset, self::PAGE_SIZE, $path);

        return [
            'items' => $items,
            'paginator' => $paginator,
            'offset' => $normalizedOffset,
            'search' => $searchQuery,
        ];
    }

    public function getEditData(int $plotId): array
    {
        $info = $plotId ? $this->repository->findById($plotId) : null;

        if ($info) {
            $info['status_id'] = $info['status'];
            $info['price'] = number_format((int) $info['price_raw'], 0, '', ' ');
            return $info;
        }

        return [
            'id' => 0,
            'status_id' => 0,
            'billing' => 0,
            'number' => '',
            'size' => '',
            'price' => '',
        ];
    }

    public function savePlot(array $payload): array
    {
        $plotId = isset($payload['plot_id']) && is_numeric($payload['plot_id']) ? (int) $payload['plot_id'] : 0;
        $status = isset($payload['status']) && in_array((int) $payload['status'], [0, 1, 2], true) ? (int) $payload['status'] : 0;
        $billing = isset($payload['billing']) && (int) $payload['billing'] === 1 ? 1 : 0;
        $number = $this->sanitizer->sanitize($payload['number'] ?? '');
        $size = preg_replace('~\D+~', '', $payload['size'] ?? '') ?? '';
        $priceDigits = preg_replace('~\D+~', '', $payload['price'] ?? '') ?? '';
        $price = $priceDigits !== '' ? (int) $priceDigits : 0;

        $data = [
            'status' => $status,
            'billing' => $billing,
            'number' => $number,
            'size' => $size,
            'price' => $price,
            'updated' => $this->clock->now(),
        ];

        if ($plotId > 0) {
            $this->repository->update($plotId, $data);
        } else {
            $this->repository->insert($data);
        }

        $offset = isset($payload['offset']) && is_numeric($payload['offset']) ? (int) $payload['offset'] : 0;
        $search = isset($payload['search']) ? (string) $payload['search'] : '';

        return $this->getPaginatedList($search, $offset);
    }

    private function statusToString(int $status): string
    {
        return match ($status) {
            1 => 'Reserved',
            2 => 'Sold',
            default => 'Free',
        };
    }
}
