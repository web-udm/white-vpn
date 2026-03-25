<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('doctrine.middleware')]
final readonly class SqliteWalMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new SqliteWalDriver($driver);
    }
}
