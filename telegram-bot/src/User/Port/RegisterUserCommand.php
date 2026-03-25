<?php

declare(strict_types=1);

namespace App\User\Port;

final readonly class RegisterUserCommand
{
    public function __construct(public int $telegramID)
    {
    }
}
