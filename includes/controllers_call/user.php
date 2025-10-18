<?php

/**
 * AJAX‑контроллер управления пользователями.обрабатывает действия,
 * отправленные асинхронно (call.php) для Users, происходит передача соответствующему методу класса User и возвращает
 * массив для преобразования в JSON
 *
 * Поддерживаемые действия:
 *  edit_window: вернуть HTML для модального окна создания иредактирования пользователя
 * edit_update: сохранить изменения пользователя  и обновить список
 * delete: удалить пользователя из бд и обновить список
 *
 * @param string $act запрошенное поддействие например edit_window
 * @param array  $d   данные переданные клиентом
 * @return array      массив ответа для JSON
 */
function controller_user($act, $d) {
    if ($act == 'edit_window') return User::user_edit_window($d);
    if ($act == 'edit_update') return User::user_edit_update($d);
    if ($act == 'delete') return User::user_delete($d);
    return [];
}
