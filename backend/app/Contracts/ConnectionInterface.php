<?php

declare(strict_types=1);

namespace SmartStock\Contracts;

use PDO;

interface ConnectionInterface
{
    public function getConnection(): PDO;
}
