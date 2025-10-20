<?php

namespace App\Core\Support;

final class Paginator
{
    public function render(int $total, int $offset, int $pageSize, string $path): string
    {
        if ($total <= $pageSize) {
            return '';
        }

        $html = '';
        $currentPage = (int) floor($offset / $pageSize) + 1;
        $totalPages = (int) ceil($total / $pageSize);

        $appendLink = static function (int $page) use (&$html, $pageSize, $path): void {
            $pageOffset = ($page - 1) * $pageSize;
            $html .= sprintf('<a href="/%soffset=%d">%d</a>', $path, $pageOffset, $page);
        };

        $start = max(1, $currentPage - 1);
        $end = min($totalPages, $currentPage + 2);

        if ($start > 1) {
            $appendLink(1);
            if ($start > 2) {
                $html .= '&nbsp;&nbsp;...&nbsp;&nbsp;';
            }
        }

        for ($page = $start; $page < $currentPage; $page++) {
            $appendLink($page);
        }

        $html .= sprintf('<a href="#" class="active">%d</a>', $currentPage);

        for ($page = $currentPage + 1; $page <= $end; $page++) {
            $appendLink($page);
        }

        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $html .= '&nbsp;&nbsp;...&nbsp;&nbsp;';
            }
            $appendLink($totalPages);
        }

        return $html;
    }
}