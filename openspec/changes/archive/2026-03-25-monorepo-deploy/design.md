## Context

Бот запущен локально через Docker Compose (FrankenPHP + worker). 3x-ui панель работает на `89.167.91.252` в Docker. Деплой ручной. Нужно автоматизировать деплой и собрать всю инфраструктуру в одну репу.

Домен `whitevpn.tech` привязан к Cloudflare. Сервер один — `89.167.91.252`.

## Goals / Non-Goals

**Goals:**
- Автоматический деплой бота при пуше в `master` (prod) и `dev` (dev)
- Единая репа для бота, gateway и конфигурации 3x-ui
- Два окружения бота на одном сервере
- Caddy как reverse proxy с auto SSL

**Non-Goals:**
- Zero-downtime деплой (допустим простой 5-10 сек)
- Мониторинг/Grafana (потом)
- Автоматическое масштабирование
- Управление 3x-ui через CI (только конфиг в репе, панель управляется руками)

## Decisions

### 1. Структура репозитория

```
whitevpn/
  telegram-bot/                  ← весь текущий код бота
    docker-compose.prod.yaml
    docker-compose.dev.yaml
    docker/franken/Dockerfile
    src/
    ...
  gateway/                       ← Caddy reverse proxy
    docker-compose.yaml
    Caddyfile
    Dockerfile
  3x-ui/                         ← конфигурация панели
    docker-compose.yaml
  .github/workflows/
    deploy-prod.yaml
    deploy-dev.yaml
```

**Почему**: каждая папка самодостаточная — свой compose внутри. Запуск независимый: `cd gateway && docker compose up -d`.

### 2. Docker image через GHCR

Сборка image в CI (GitHub Container Registry), на сервере только `docker pull` + `up`.

```
CI: docker build → push ghcr.io/web-udm/whitevpn-bot:prod
CI: docker build → push ghcr.io/web-udm/whitevpn-bot:dev
Server: docker pull + docker compose up -d
```

**Почему**: не тратим ресурсы сервера на сборку, быстрее деплой, воспроизводимые образы.

### 3. Caddy как reverse proxy

```
whitevpn.tech        → bot-prod:8080
dev.whitevpn.tech    → bot-dev:8081
panel.whitevpn.tech  → localhost:2053
sub.whitevpn.tech    → localhost:2096
```

**Почему**: минимум конфигурации, auto SSL через Let's Encrypt, хорошо работает с Cloudflare (SSL mode: Full).

### 4. Секреты через GitHub Secrets

CI генерирует `.env` файлы на сервере из GitHub Secrets при каждом деплое.

**Почему**: единый источник правды для секретов, не нужно лезть на сервер для обновления.

### 5. CI flow

```
push → build image → push ghcr.io → ssh to server →
  docker pull → generate .env → docker compose up -d →
  docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction →
  docker compose exec app php bin/console app:telegram:set-webhook --url=<domain>/webhook
```

### 6. XUI_BASE_URL = localhost

Бот и 3x-ui на одной машине — обращение через `http://localhost:2053`.

## Risks / Trade-offs

- **[Один IP для VPN и бота]** → Если IP заблокируют из-за VPN трафика, бот тоже ляжет. Митигация: при необходимости выносим бота на отдельный сервер, архитектура это позволяет.
- **[Даунтайм при деплое]** → 5-10 секунд простоя при `docker compose up`. Митигация: допустимо для текущего масштаба.
- **[Перемещение файлов ломает пути]** → Dockerfile context, volumes, CI, autoload. Митигация: один коммит с перемещением + фикс путей, тесты валидируют.
- **[3x-ui уже запущен]** → Нужно аккуратно перевести под compose из репы, не потерять данные. Митигация: бэкап перед миграцией.