<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

final class SqliteWalDriver extends AbstractDriverMiddleware
{
    public function connect(array $params): Connection
    {
        $connection = parent::connect($params);

        if (str_contains($params['driver'] ?? '', 'sqlite')) {
            $connection->exec('PRAGMA journal_mode=WAL');
        }

        return $connection;
    }
}
