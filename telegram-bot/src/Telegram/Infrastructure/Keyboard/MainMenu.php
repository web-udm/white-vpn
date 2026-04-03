<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Keyboard;

final readonly class MainMenu
{
    public const string CONNECT = 'connect';
    public const string GET_VPN = 'get_vpn';
    public const string STATUS = 'status';
    public const string SUPPORT = 'support';

    public const string MENU_BUTTON_TEXT = '📋 Меню';

    public const array BUTTONS = [
        self::CONNECT => '🔗 Получить подписку',
        self::GET_VPN => '🛡 Получить VPN',
        self::STATUS => '📊 Текущий статус',
        self::SUPPORT => '🆘 Поддержка',
    ];
}
