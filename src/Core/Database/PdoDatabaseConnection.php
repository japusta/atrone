<?php

namespace App\Core\Database;

use PDO;
use PDOException;
use RuntimeException;

final class PdoDatabaseConnection implements DatabaseConnection
{
    private PDO $pdo;

    /**
     * @param array{dsn:string,user:string,password:string} $config
     */
    public function __construct(array $config)
    {
        try {
            $this->pdo = new PDO(
                $config['dsn'],
                $config['user'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('Unable to connect to the database: '.$exception->getMessage(), 0, $exception);
        }
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $result = $statement->fetch();

        return $result === false ? null : $result;
    }

    public function execute(string $sql, array $params = []): void
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}