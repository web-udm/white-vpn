<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Keyboard;

final readonly class MainMenu
{
    public const string CONNECT = 'connect';
    public const string BUY = 'buy';
    public const string STATUS = 'status';
    public const string SUPPORT = 'support';
    public const string GET_CONNECTION = 'get_connection';
    public const string INSTRUCTIONS = 'instructions';

    public const string MENU_BUTTON_TEXT = '📋 Меню';

    public const array BUTTONS = [
        self::CONNECT => '📋 Подписка',
        self::BUY => 'Купить',
        self::STATUS => '📊 Текущий статус',
        self::SUPPORT => '🆘 Поддержка',
        self::GET_CONNECTION => '🔑 Мои подключения',
        self::INSTRUCTIONS => '❓ F.A.Q.',
    ];
}
