<?php

namespace Modules\Users\Domain;

use App\Core\Database\DatabaseConnection;

final class UserRepository
{
    private DatabaseConnection $database;

    public function __construct(DatabaseConnection $database)
    {
        $this->database = $database;
    }

    public function findAuthInfo(?int $userId, ?string $phone): array
    {
        if ($userId !== null && $userId > 0) {
            $row = $this->database->fetchOne(
                'SELECT user_id, access FROM users WHERE user_id = :id LIMIT 1',
                ['id' => $userId]
            );
        } elseif ($phone !== null && $phone !== '') {
            $row = $this->database->fetchOne(
                'SELECT user_id, access FROM users WHERE phone = :phone LIMIT 1',
                ['phone' => $phone]
            );
        } else {
            return ['id' => 0, 'access' => 0];
        }

        if ($row === null) {
            return ['id' => 0, 'access' => 0];
        }

        return [
            'id' => (int) $row['user_id'],
            'access' => (int) $row['access'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findOwnersByPlot(string $number): array
    {
        $rows = $this->database->fetchAll(
            "SELECT user_id, plot_id, first_name, email, phone FROM users WHERE FIND_IN_SET(:plot, REPLACE(plot_id, ' ', '')) > 0 ORDER BY user_id",
            ['plot' => $number]
        );

        return array_map(static function (array $row) {
            return [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
            ];
        }, $rows);
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    public function search(string $search, int $offset, int $limit): array
    {
        $conditions = [];
        $params = [];
        $search = trim($search);

        if ($search !== '') {
            $conditions[] = '(first_name LIKE :name OR last_name LIKE :name OR email LIKE :email)';
            $params['name'] = '%'.$search.'%';
            $params['email'] = '%'.$search.'%';

            $digits = preg_replace('~\D+~', '', $search) ?? '';
            if ($digits !== '') {
                $conditions[] = 'phone LIKE :phone';
                $params['phone'] = '%'.$digits.'%';
            }
        }

        $where = $conditions ? ('WHERE '.implode(' OR ', $conditions)) : '';

        $limit = max(0, $limit);
        $offset = max(0, $offset);

        $items = $this->database->fetchAll(
            sprintf(
                'SELECT user_id, plot_id, first_name, last_name, phone, email, last_login FROM users %s ORDER BY user_id DESC LIMIT %d, %d',
                $where,
                $offset,
                $limit
            ),
            $params
        );

        $countRow = $this->database->fetchOne(
            "SELECT COUNT(*) AS total FROM users $where",
            $params
        );

        $total = $countRow ? (int) $countRow['total'] : 0;

        $items = array_map(static function (array $row) {
            return [
                'id' => (int) $row['user_id'],
                'plot_id' => $row['plot_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'last_login' => (int) $row['last_login'],
            ];
        }, $items);

        return [$items, $total];
    }

    public function findForEdit(int $userId): ?array
    {
        $row = $this->database->fetchOne(
            'SELECT user_id, plot_id, first_name, last_name, phone, email FROM users WHERE user_id = :id LIMIT 1',
            ['id' => $userId]
        );

        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) $row['user_id'],
            'plot_id' => $row['plot_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'phone' => $row['phone'],
            'email' => $row['email'],
        ];
    }

    public function insert(array $data): void
    {
        $this->database->execute(
            'INSERT INTO users (first_name, last_name, phone, email, plot_id, access, phone_code) VALUES (:first_name, :last_name, :phone, :email, :plot_id, :access, :phone_code)',
            $data
        );
    }

    public function update(int $userId, array $data): void
    {
        $data['user_id'] = $userId;
        $this->database->execute(
            'UPDATE users SET first_name = :first_name, last_name = :last_name, phone = :phone, email = :email, plot_id = :plot_id, access = :access, phone_code = :phone_code WHERE user_id = :user_id LIMIT 1',
            $data
        );
    }

    public function delete(int $userId): void
    {
        $this->database->execute(
            'DELETE FROM users WHERE user_id = :user_id LIMIT 1',
            ['user_id' => $userId]
        );
    }

    public function findByPhone(string $phone): ?array
    {
        $row = $this->database->fetchOne(
            'SELECT user_id, access, first_name, phone_code, phone_attempts_code, last_login FROM users WHERE phone = :phone LIMIT 1',
            ['phone' => $phone]
        );

        return $row ?? null;
    }
}
