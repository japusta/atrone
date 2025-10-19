<?php

class User
{

    // GENERAL

    /**
     * Получить упрощенную запись пользователя по идентификатору или телефону.
     * Возвращает только id и флаг доступа, так как эти поля нужны
     * для аутентификации. Используется логикой Session и Auth.
     *
     * @param array $d Ассоциативный массив, содержащий 'user_id' или 'phone'.
     * @return array    Содержит ключи 'id' и 'access'.
     */
    public static function user_info($d)
    {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        // where
        if ($user_id)
            $where = "user_id='" . $user_id . "'";
        else if ($phone)
            $where = "phone='" . $phone . "'";
        else
            return [];
        // info
        $q = DB::query("SELECT user_id, phone, access FROM users WHERE " . $where . " LIMIT 1;") or die(DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'access' => (int) $row['access']
            ];
        } else {
            return [
                'id' => 0,
                'access' => 0
            ];
        }
    }

    /**
     * Вернуть список пользователей, связанных с указанным номером участка.
     * Используется при отрисовке таблицы участков для перечисления владельцев.
     * Предполагается, что колонка users.plot_id хранит список номеров участков
     * через запятую. Возвращаются только пользователи, у которых совпадает
     * номер участка.
     *
     * @param string $number Номер участка для поиска.
     * @return array        Список пользователей, владеющих участком.
     */
    public static function users_list_plots($number)
    {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
                FROM users WHERE plot_id LIKE '%" . $number . "%' ORDER BY user_id;") or die(DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach ($plot_ids as $plot_id)
                if ($plot_id == $number)
                    $val = true;
            if ($val)
                $items[] = [
                    'id' => (int) $row['user_id'],
                    'first_name' => $row['first_name'],
                    'email' => $row['email'],
                    'phone_str' => phone_formatting($row['phone'])
                ];
        }
        // output
        return $items;
    }

    /* ==================== Новый функционал для раздела пользователей (добавлено) ==================== */

    /**
     * Получить постраничный список пользователей с возможностью поиска.
     * Строка поиска сопоставляется с именем, фамилией, телефоном и
     * электронной почтой.  Максимум 20 записей на страницу.  Пагинатор
     * формируется при помощи глобального помощника paginator().
     *
     * @param array $d Параметры: 'search' — строка поиска, 'offset' — смещение.
     * @return array    Содержит массив записей и HTML пагинатора.
     */
    // добавлено
    public static function users_list($d = [])
    {
        $search = isset($d['search']) && trim($d['search']) ? trim($d['search']) : '';
        $offset = isset($d['offset']) && is_numeric($d['offset']) ? $d['offset'] : 0;
        $limit = 20;
        $items = [];
        // формируем выражение WHERE, если задан поиск
        $where = [];
        if ($search) {
            $s = flt_input($search);
            $phone_search = preg_replace('~\D+~', '', $search);
            $conditions = [
                "first_name LIKE '%" . $s . "%'",
                "last_name LIKE '%" . $s . "%'",
                "email LIKE '%" . $s . "%'"
            ];
            if ($phone_search !== '' && preg_match('~^[\d\s\+\-\(\)]+$~u', $search)) {
                $conditions[] = "phone LIKE '%" . flt_input($phone_search) . "%'";
            }
            $where[] = '(' . implode(' OR ', $conditions) . ')';
        }
        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        // выполняем запрос
        $q = DB::query("SELECT user_id, plot_id, first_name, last_name, phone, email, last_login"
                . " FROM users " . $where_sql . " ORDER BY user_id DESC LIMIT " . $offset . ", " . $limit . ";") or die(DB::error());
        while ($row = DB::fetch_row($q)) {
            $items[] = [
                'id' => (int) $row['user_id'],
                'plot_id' => $row['plot_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone' => $row['phone'],
                'phone_str' => phone_formatting($row['phone']),
                'email' => $row['email'],
                'last_login' => $row['last_login'] ? date('Y/m/d', $row['last_login']) : ''
            ];
        }
        // создаём пагинатор
        $q2 = DB::query("SELECT count(*) FROM users " . $where_sql . ";");
        $count = ($row2 = DB::fetch_row($q2)) ? $row2['count(*)'] : 0;
        $url = 'users?';
        if ($search)
            $url .= '&search=' . urlencode($search);
        paginator($count, $offset, $limit, $url, $paginator);
        return ['items' => $items, 'paginator' => $paginator];
    }

    /**
     * Помощник для получения и назначения списка пользователей при
     * асинхронных запросах.  Возвращает заново сформированную HTML‑таблицу
     * и разметку пагинатора.
     *
     * @param array $d Параметры, переданные из контроллера вызова.
     * @return array    Содержит ключи 'html' и 'paginator'.
     */
    // добавлено
    public static function users_fetch($d = [])
    {
        $info = self::users_list($d);
        HTML::assign('users', $info['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
    }

    /**
     * Получить полную информацию для редактирования одного пользователя.
     * Если идентификатор отсутствует, возвращает структуру, подходящую
     * для создания нового пользователя.
     *
     * @param int $user_id Идентификатор пользователя.
     * @return array       Поля пользователя для редактирования.
     */
    // добавлено
    private static function user_info_edit($user_id)
    {
        if ($user_id) {
            $q = DB::query("SELECT user_id, plot_id, first_name, last_name, phone, email
                    FROM users WHERE user_id='" . $user_id . "' LIMIT 1;") or die(DB::error());
            if ($row = DB::fetch_row($q)) {
                return [
                    'id' => (int) $row['user_id'],
                    'plot_id' => $row['plot_id'],
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'phone' => $row['phone'],
                    'email' => $row['email']
                ];
            }
        }
        return [
            'id' => 0,
            'plot_id' => '',
            'first_name' => '',
            'last_name' => '',
            'phone' => '',
            'email' => ''
        ];
    }

    /**
     * Вернуть HTML модального окна для редактирования или создания
     * пользователя.  Делегирует в user_info_edit() для подготовки
     * структуры данных, которая предварительно заполнит форму.
     *
     * @param array $d Данные, содержащие 'user_id'.
     * @return array    Содержит ключ 'html' с содержимым модального окна.
     */
    // добавлено
    public static function user_edit_window($d = [])
    {
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user', self::user_info_edit($user_id));
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }

    /**
     * Сохраняет изменения в записи пользователя (создание или обновление).
     * Проверяет, что обязательные поля (имя, фамилия, телефон, email)
     * заполнены.  Телефон очищается от всех символов, кроме цифр, а
     * электронная почта приводится к нижнему регистру.  Поле plots
     * сохраняется как есть, что позволяет указать несколько номеров
     * участков через запятую.  После сохранения возвращается
     * обновлённый список пользователей.
     *
     * @param array $d Данные, содержащие поля пользователя и смещение для пагинации.
     * @return array    Массив ответа с обновлённым списком или сообщением об ошибке.
     */
    // добавлено
    public static function user_edit_update($d = [])
    {
        // параметры
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $first_name = isset($d['first_name']) ? trim($d['first_name']) : '';
        $last_name = isset($d['last_name']) ? trim($d['last_name']) : '';
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : '';
        $email = isset($d['email']) ? strtolower(trim($d['email'])) : '';
        $plots = isset($d['plots']) ? trim($d['plots']) : '';
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
        $search = isset($d['search']) ? trim($d['search']) : '';
        // проверяем обязательные поля (кроме plots)
        if (!$first_name || !$last_name || !$phone || !$email) {
            return ['error_msg' => 'Please fill in all required fields.'];
        }
        // обновляем существующую запись
        if ($user_id) {
            $set = [];
            $set[] = "first_name='" . $first_name . "'";
            $set[] = "last_name='" . $last_name . "'";
            $set[] = "phone='" . $phone . "'";
            $set[] = "email='" . $email . "'";
            $set[] = "plot_id='" . $plots . "'";
            $set_sql = implode(', ', $set);
            DB::query("UPDATE users SET " . $set_sql . " WHERE user_id='" . $user_id . "' LIMIT 1;") or die(DB::error());
        } else {
            // вставляем новую запись
            DB::query("INSERT INTO users (
                    first_name,
                    last_name,
                    phone,
                    email,
                    plot_id
                ) VALUES (
                    '" . $first_name . "',
                    '" . $last_name . "',
                    '" . $phone . "',
                    '" . $email . "',
                    '" . $plots . "'
                );") or die(DB::error());
        }
        // возвращаем обновлённую таблицу
        return self::users_fetch(['offset' => $offset, 'search' => $search]);
    }

    /**
     * Удаляет пользователя из системы.  Затрагивается только таблица users;
     * любые сессии или внешние связи остаются неизменными.  После удаления
     * возвращается обновлённый список пользователей.
     *
     * @param array $d Данные, содержащие идентификатор 'user_id' для удаления.
     * @return array    Обновлённая таблица и разметка пагинатора.
     */
    // добавлено
    public static function user_delete($d = [])
    {
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
        $search = isset($d['search']) ? trim($d['search']) : '';
        if ($user_id) {
            DB::query("DELETE FROM users WHERE user_id='" . $user_id . "' LIMIT 1;") or die(DB::error());
        }
        return self::users_fetch(['offset' => $offset]);
    }

}