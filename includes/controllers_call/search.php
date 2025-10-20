<?php

/**
 * обработка поисковых запросов для асинхронного обновления списков
 * каждый тип поиска (участки или пользователи) возвращает HTML‑таблицу и пагинатор на основе
 * переданной строки поиска и параметров постраничной навигации
 *  если действие не распознано возвращается пустой массив
 *
 * @param string $act идентификатор списка, по которому выполняется поиск (например plots)
 * @param array  $d   массив POST‑параметров, содержащий строку поиска и смещение
 * @return array      массив с HTML‑таблицей и кодом пагинатора
 */

use Modules\Users\Application\UserService;

function controller_search($act, $d)
{
    if ($act == 'plots')
        return Plot::plots_fetch($d);
    // добавлена поддержка поиска по списку пользователей
    if ($act == 'users') {
        $service = new UserService();
        $result = $service->getPaginatedList($d['search'] ?? '', isset($d['offset']) && is_numeric($d['offset']) ? (int) $d['offset'] : 0);
        HTML::assign('users', $result['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $result['paginator']];
    }
    return [];
}
