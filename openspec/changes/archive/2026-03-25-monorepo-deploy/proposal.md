## Why

Сейчас бот разрабатывается локально и деплоится вручную. 3x-ui панель стоит отдельно на `89.167.91.252` без версионного контроля конфигурации. Нужно объединить всё в одну репу с автоматическим деплоем через GitHub Actions, чтобы пуш в ветку автоматически раскатывал изменения на сервер.

## What Changes

- **BREAKING**: Переструктуризация репозитория — код бота перемещается из корня в `telegram-bot/`
- Переименование репозитория `vpn-tg-bot` → `whitevpn`
- Добавление `gateway/` — Caddy reverse proxy с auto SSL
- Добавление `3x-ui/` — docker-compose и конфигурация панели под версионный контроль
- Добавление GitHub Actions CI/CD: build image → push ghcr.io → ssh deploy → migrate → set webhook
- Два окружения бота: prod (ветка `master`) и dev (ветка `dev`) на одном сервере
- Два Telegram-бота: prod-бот и dev-бот
- Caddy проксирует: `whitevpn.tech` → bot prod, `dev.whitevpn.tech` → bot dev, `panel.whitevpn.tech` → 3x-ui, `sub.whitevpn.tech` → subscriptions

## Capabilities

### New Capabilities
- `repo-structure`: Монорепа с папками `telegram-bot/`, `gateway/`, `3x-ui/` и корректными путями
- `ci-cd`: GitHub Actions — сборка Docker image, push в ghcr.io, SSH деплой на сервер, миграции, webhook
- `gateway-proxy`: Caddy reverse proxy для роутинга доменов к сервисам
- `multi-env`: Dev/prod окружения бота на одном сервере с разными портами, БД, ботами

### Modified Capabilities

## Impact

- Все пути в Dockerfile, docker-compose, phpunit.xml.dist, phpstan.neon — обновляются из-за переноса в `telegram-bot/`
- Git remote URL меняется после переименования репы
- Секреты (BOT_TOKEN, XUI_*) переезжают в GitHub Secrets
- XUI_BASE_URL меняется на `http://localhost:2053`
- Сервер `89.167.91.252` получает Caddy + контейнеры бота рядом с 3x-ui
- DNS: новые записи в Cloudflare для `whitevpn.tech`, `dev.whitevpn.tech`, `panel.whitevpn.tech`, `sub.whitevpn.tech`