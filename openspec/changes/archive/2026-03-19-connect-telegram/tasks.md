## 1. Установка Nutgram

- [x] 1.1 `composer require nutgram/nutgram` (проверить совместимость с Symfony 8.0)
- [x] 1.2 Настроить Nutgram в Symfony: конфигурация, DI-сервис
- [x] 1.3 Добавить `BOT_TOKEN` в `.env`

## 2. Webhook endpoint

- [x] 2.1 Создать `src/Telegram/Webhook/WebhookController.php` — POST /webhook
- [x] 2.2 Зарегистрировать роут в конфигурации

## 3. Хэндлер

- [x] 3.1 Создать `src/Telegram/Handler/StartHandler.php` — отвечает "Привет!" на любое сообщение
- [x] 3.2 Зарегистрировать хэндлер в Nutgram

## 4. Команда для webhook

- [x] 4.1 Создать консольную команду `app:telegram:set-webhook`
- [x] 4.2 Команда вызывает Telegram Bot API `setWebhook` с переданным URL

## 5. Проверка

- [x] 5.1 Зарегистрировать webhook на Cloudflare Tunnel URL
- [x] 5.2 Отправить сообщение боту — получить "Привет!" в ответ
- [x] 5.3 `vendor/bin/phpstan analyse src/` — 0 ошибок
