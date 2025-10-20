<?php

namespace Modules\Users\Domain;

class UserRepository
{
    public function findAuthInfo(?int $userId, ?string $phone): array
    {
        if ($userId) {
            $where = "user_id='" . $userId . "'";
        } elseif ($phone) {
            $where = "phone='" . $phone . "'";
        } else {
            return ['id' => 0, 'access' => 0];
        }

        $q = \DB::query("SELECT user_id, access FROM users WHERE " . $where . " LIMIT 1;") or die(\DB::error());
        if ($row = \DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'access' => (int) $row['access'],
            ];
        }

        return ['id' => 0, 'access' => 0];
    }

    public function findOwnersByPlot(string $number): array
    {
        $items = [];
        $q = \DB::query("SELECT user_id, plot_id, first_name, email, phone FROM users WHERE plot_id LIKE '%" . $number . "%' ORDER BY user_id;") or die(\DB::error());
        while ($row = \DB::fetch_row($q)) {
            $plotIds = explode(',', $row['plot_id']);
            foreach ($plotIds as $plotId) {
                if ($plotId == $number) {
                    $items[] = [
                        'id' => (int) $row['user_id'],
                        'first_name' => $row['first_name'],
                        'email' => $row['email'],
                        'phone' => $row['phone'],
                    ];
                    break;
                }
            }
        }

        return $items;
    }

    public function search(string $search, int $offset, int $limit): array
    {
        $conditions = [];
        $paramsSearch = trim($search);
        $phoneSearch = preg_replace('~\D+~', '', $paramsSearch);
        if ($paramsSearch !== '') {
            $safeSearch = flt_input($paramsSearch);
            $conditions[] = "first_name LIKE '%" . $safeSearch . "%'";
            $conditions[] = "last_name LIKE '%" . $safeSearch . "%'";
            $conditions[] = "email LIKE '%" . $safeSearch . "%'";
            if ($phoneSearch !== '') {
                $conditions[] = "phone LIKE '%" . flt_input($phoneSearch) . "%'";
            }
        }

        $whereSql = '';
        if ($conditions) {
            $whereSql = 'WHERE (' . implode(' OR ', $conditions) . ')';
        }

        $items = [];
        $q = \DB::query(
            "SELECT user_id, plot_id, first_name, last_name, phone, email, last_login"
            . " FROM users " . $whereSql . " ORDER BY user_id DESC LIMIT " . $offset . ", " . $limit . ";"
        ) or die(\DB::error());

        while ($row = \DB::fetch_row($q)) {
            $items[] = [
                'id' => (int) $row['user_id'],
                'plot_id' => $row['plot_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'last_login' => $row['last_login'],
            ];
        }

        $q2 = \DB::query("SELECT count(*) as total FROM users " . $whereSql . ";");
        $countRow = \DB::fetch_row($q2);
        $total = $countRow ? (int) $countRow['total'] : 0;

        return [$items, $total];
    }

    public function findForEdit(int $userId): ?array
    {
        $q = \DB::query("SELECT user_id, plot_id, first_name, last_name, phone, email FROM users WHERE user_id='" . $userId . "' LIMIT 1;") or die(\DB::error());
        if ($row = \DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'plot_id' => $row['plot_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
            ];
        }

        return null;
    }

    public function insert(array $data): void
    {
        \DB::query("INSERT INTO users (first_name, last_name, phone, email, plot_id, access, phone_code) VALUES ('"
            . $data['first_name'] . "', '" . $data['last_name'] . "', '" . $data['phone'] . "', '" . $data['email'] . "', '"
            . $data['plot_id'] . "', '" . $data['access'] . "', '" . $data['phone_code'] . "')") or die(\DB::error());
    }

    public function update(int $userId, array $data): void
    {
        $set = [];
        $set[] = "first_name='" . $data['first_name'] . "'";
        $set[] = "last_name='" . $data['last_name'] . "'";
        $set[] = "phone='" . $data['phone'] . "'";
        $set[] = "email='" . $data['email'] . "'";
        $set[] = "plot_id='" . $data['plot_id'] . "'";
        $set[] = "access='" . $data['access'] . "'";
        $set[] = "phone_code='" . $data['phone_code'] . "'";
        $setSql = implode(', ', $set);

        \DB::query("UPDATE users SET " . $setSql . " WHERE user_id='" . $userId . "' LIMIT 1;") or die(\DB::error());
    }

    public function delete(int $userId): void
    {
        \DB::query("DELETE FROM users WHERE user_id='" . $userId . "' LIMIT 1;") or die(\DB::error());
    }

    public function findByPhone(string $phone): ?array
    {
        $q = \DB::query("SELECT user_id, access, first_name, phone_code, phone_attempts_code, last_login FROM users WHERE phone='" . $phone . "' LIMIT 1;") or die(\DB::error());
        if ($row = \DB::fetch_row($q)) {
            return $row;
        }

        return null;
    }
}