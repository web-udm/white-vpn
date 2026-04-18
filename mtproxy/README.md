# MTProxy

MTProxy-сервер на базе [TelegramMessenger/MTProxy](https://github.com/TelegramMessenger/MTProxy).

## Переменные окружения

| Переменная | По умолчанию | Описание |
|---|---|---|
| `MTPROXY_PORT` | `8443` | Порт MTProxy (443 занят Caddy) |
| `AD_TAG` | — | Тег рекламного канала от @MTProxybot (опционально) |

## Запуск

```bash
cd mtproxy/
docker compose up -d
```

## Секреты

Файл `secrets.txt` содержит по одному hex-секрету на строку. Генерируется ботом автоматически.

Вручную:
```bash
# в директории telegram-bot/
php bin/console app:mtproxy:sync-secrets
```

## Синхронизация и рестарт (Ofelia)

MTProxy не поддерживает hot-reload секретов. Для применения изменений нужен рестарт.

Синхронизация автоматизирована через [Ofelia](https://github.com/mcuadros/ofelia) — Docker-native планировщик, поднимается вместе с compose.

Каждые 30 минут Ofelia:
1. Запускает `php bin/console app:mtproxy:sync-secrets` в контейнере `bot_franken` → обновляет `secrets.txt`
2. Перезапускает контейнер `mtproxy_app` → применяет новые секреты

Задержка отзыва доступа при истечении подписки — до 30 минут.

> Ofelia использует Docker socket (`/var/run/docker.sock`). Если это неприемлемо по требованиям безопасности — замените на host cron.

## Переменные окружения бота

Добавить в `.env.local` бота:

```env
MTPROXY_HOST=whitevpn.tech
MTPROXY_PORT=8443
MTPROXY_SECRETS_PATH=/path/to/whitevpn/mtproxy/secrets.txt
```

## Миграция существующих подписок

При первом деплое создать MTProxy-подключения для всех активных подписчиков:

```bash
php bin/console app:mtproxy:migrate-existing
```
