## Why

Бот не подключён к Telegram — нет возможности принимать и отправлять сообщения. Это первый шаг к MVP: подключить Nutgram, настроить webhook endpoint и проверить работоспособность ответом "Привет" на любое сообщение.

## What Changes

- Добавляется зависимость `nutgram/nutgram` (Symfony bundle)
- Создаётся webhook endpoint для приёма обновлений от Telegram
- Создаётся базовый хэндлер в модуле `Telegram/` — отвечает "Привет!" на любое сообщение
- Добавляется `BOT_TOKEN` в `.env`
- Добавляется консольная команда для регистрации webhook URL в Telegram

## Capabilities

### New Capabilities
- `telegram-webhook`: Приём обновлений от Telegram через webhook и базовая обработка сообщений

### Modified Capabilities

## Impact

- Новый модуль: `src/Telegram/`
- Зависимости: `nutgram/nutgram`
- Конфигурация: `.env` (BOT_TOKEN), `config/packages/nutgram.yaml`
- Endpoint: `POST /webhook`
