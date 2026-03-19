## Context

Docker-окружение и Symfony работают. Cloudflare Tunnel настроен для dev (публичный HTTPS URL → localhost:81). Нужно подключить Telegram через Nutgram и проверить что бот отвечает.

## Goals / Non-Goals

**Goals:**
- Nutgram установлен и интегрирован в Symfony
- Webhook endpoint принимает обновления от Telegram
- Бот отвечает "Привет!" на любое входящее сообщение
- Есть способ зарегистрировать webhook URL

**Non-Goals:**
- Сложная логика обработки команд (/start, кнопки, и т.д.)
- Middleware авторизации (admin/user)
- Модули User, Subscription — только Telegram адаптер

## Decisions

### 1. Nutgram Symfony Bundle

**Выбор:** `nutgram/nutgram` — есть встроенная поддержка Symfony через `Nutgram\Laravel\` или ручная интеграция. Проверить наличие `nutgram/symfony-bundle`.

**Rationale:** Nutgram — современная библиотека с middleware, типизированными объектами, поддержкой webhook и polling.

### 2. Структура модуля Telegram

```
src/Telegram/
├── Handler/
│   └── StartHandler.php       # Отвечает "Привет!" на сообщения
└── Webhook/
    └── WebhookController.php  # POST /webhook endpoint
```

Telegram модуль — чистый адаптер (по CLAUDE.md), без Domain слоя.

### 3. Webhook endpoint

`POST /webhook` — принимает JSON от Telegram, передаёт в Nutgram для обработки.

Секрет в URL не нужен на этом этапе (добавим позже для безопасности).

### 4. Регистрация webhook

Консольная команда: `php bin/console app:telegram:set-webhook --url=<URL>`. Использует Telegram Bot API `setWebhook`.

Для dev: URL из Cloudflare Tunnel. Для prod: URL с реальным доменом.

## Risks / Trade-offs

- **[Cloudflare Tunnel URL меняется]** → При каждом перезапуске `cloudflared` новый URL. Нужно перерегистрировать webhook. Для dev это ок.
- **[Nutgram + Symfony 8.0]** → Совместимость не гарантирована. Fallback: ручная интеграция без bundle.
