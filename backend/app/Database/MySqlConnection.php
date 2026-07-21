<?php

declare(strict_types=1);

namespace SmartStock\Database;

use PDO;
use SmartStock\Contracts\ConnectionInterface;

final class MySqlConnection implements ConnectionInterface
{
    private ?PDO $connection = null;

    public function __construct(private readonly array $config) {}

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = new PDO(
                sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $this->config['host'], $this->config['database']),
                $this->config['username'],
                $this->config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        }
        return $this->connection;
    }
}
