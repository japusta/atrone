<?php

namespace App\Core\Database;

interface DatabaseConnection
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array;

    /**
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $params = []): ?array;

    public function execute(string $sql, array $params = []): void;

    public function lastInsertId(): string;
}