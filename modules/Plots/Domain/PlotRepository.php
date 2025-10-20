<?php

namespace Modules\Plots\Domain;

use App\Core\Database\DatabaseConnection;

final class PlotRepository
{
    private DatabaseConnection $database;

    public function __construct(DatabaseConnection $database)
    {
        $this->database = $database;
    }

    public function findById(int $id): ?array
    {
        $row = $this->database->fetchOne(
            'SELECT plot_id, status, billing, number, size, price, base_fixed, electricity_t1, electricity_t2, updated FROM plots WHERE plot_id = :id LIMIT 1',
            ['id' => $id]
        );

        if ($row === null) {
            return null;
        }

        return $this->hydrate($row);
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
            $conditions[] = 'number LIKE :number';
            $params['number'] = '%'.$search.'%';
        }

        $where = $conditions ? ('WHERE '.implode(' AND ', $conditions)) : '';

        $limit = max(0, $limit);
        $offset = max(0, $offset);

        $items = $this->database->fetchAll(
            sprintf(
                'SELECT plot_id, status, billing, number, size, price, base_fixed, electricity_t1, electricity_t2, updated FROM plots %s ORDER BY number+0 LIMIT %d, %d',
                $where,
                $offset,
                $limit
            ),
            $params
        );

        $items = array_map([$this, 'hydrate'], $items);

        $countRow = $this->database->fetchOne(
            sprintf('SELECT COUNT(*) AS total FROM plots %s', $where),
            $params
        );

        $total = $countRow ? (int) $countRow['total'] : 0;

        return [$items, $total];
    }

    public function update(int $id, array $data): void
    {
        $data['plot_id'] = $id;
        $this->database->execute(
            'UPDATE plots SET status = :status, billing = :billing, number = :number, size = :size, price = :price, updated = :updated WHERE plot_id = :plot_id LIMIT 1',
            $data
        );
    }

    public function insert(array $data): void
    {
        $this->database->execute(
            'INSERT INTO plots (status, billing, number, size, price, updated) VALUES (:status, :billing, :number, :size, :price, :updated)',
            $data
        );
    }

    private function hydrate(array $row): array
    {
        return [
            'id' => (int) $row['plot_id'],
            'status' => (int) $row['status'],
            'billing' => (int) $row['billing'],
            'number' => $row['number'],
            'size' => $row['size'],
            'price_raw' => (int) $row['price'],
            'price' => $row['price'],
            'base_fixed' => (bool) $row['base_fixed'],
            'electricity_t1' => (float) $row['electricity_t1'],
            'electricity_t2' => (float) $row['electricity_t2'],
            'updated' => (int) $row['updated'],
        ];
    }
}
