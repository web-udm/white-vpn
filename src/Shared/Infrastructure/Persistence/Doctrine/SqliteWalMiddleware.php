<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

final class SqliteWalMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new SqliteWalDriver($driver);
    }
}
