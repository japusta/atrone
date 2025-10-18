<?php

/**
 * контроллер раздела Users, вызываемый при запросе
 * он читывает параметры запроса, обращается к User для получения
 * списка пользователей потом готовит переменные пагинации и поиска
 * и связывает их с шаблонными переменными и потом выбирает шаблон
 * секции пользователей для отображения
 */
function controller_users() {
    // определяемномер страницы  и поисковый запрос из строки запроса
    $offset = isset($_GET['offset']) ? flt_input($_GET['offset']) : 0;
    $search = $_GET['search'] ?? '';
    // получаем данные через модель User 
    $users = User::users_list(['mode' => 'page', 'offset' => $offset, 'search' => $search]);
    // передали переменные
    HTML::assign('users', $users['items']);
    HTML::assign('paginator', $users['paginator']);
    HTML::assign('search', $search);
    HTML::assign('offset', $offset);
    // задалитекущую секцию и основной шаблон
    HTML::assign('section', 'users.html');
    HTML::assign('main_content', 'home.html');
}
