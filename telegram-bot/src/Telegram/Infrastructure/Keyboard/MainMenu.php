<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Keyboard;

final readonly class MainMenu
{
    public const string CONNECT = 'connect';
    public const string BUY = 'buy';
    public const string STATUS = 'status';
    public const string SUPPORT = 'support';

    public const array BUTTONS = [
        self::CONNECT => 'Подключиться',
        self::BUY => 'Купить',
        self::STATUS => 'Текущий статус',
        self::SUPPORT => 'Поддержка',
    ];
}
