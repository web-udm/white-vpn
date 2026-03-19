## ADDED Requirements

### Requirement: Webhook endpoint
Приложение SHALL иметь POST endpoint `/webhook` который принимает обновления от Telegram Bot API и передаёт их в Nutgram для обработки.

#### Scenario: Telegram отправляет обновление
- **WHEN** Telegram отправляет POST-запрос на `/webhook` с JSON-телом
- **THEN** Nutgram обрабатывает обновление и возвращает HTTP 200

#### Scenario: Невалидный запрос
- **WHEN** на `/webhook` приходит GET-запрос или пустое тело
- **THEN** endpoint возвращает HTTP 200 (не раскрывать ошибки)

### Requirement: Ответ на сообщение
Бот SHALL отвечать "Привет!" на любое входящее текстовое сообщение.

#### Scenario: Пользователь пишет боту
- **WHEN** пользователь отправляет любое текстовое сообщение боту
- **THEN** бот отвечает "Привет!"

### Requirement: Регистрация webhook
SHALL существовать консольная команда для регистрации webhook URL в Telegram Bot API.

#### Scenario: Установка webhook
- **WHEN** выполняется `php bin/console app:telegram:set-webhook --url=https://example.com/webhook`
- **THEN** Telegram Bot API получает setWebhook запрос с указанным URL

### Requirement: BOT_TOKEN конфигурация
BOT_TOKEN SHALL храниться в `.env` и передаваться в Nutgram через конфигурацию Symfony.

#### Scenario: Токен не задан
- **WHEN** BOT_TOKEN пустой или отсутствует
- **THEN** приложение выбрасывает понятную ошибку при попытке использовать Nutgram
