## Context

Проект пустой — есть только CLAUDE.md, OpenSpec спецификации и figma.png. Нужно создать Docker-окружение и инициализировать Symfony-проект, чтобы можно было начать разработку.

Стек зафиксирован: PHP 8.5, Symfony 8.0, FrankenPHP, SQLite + Doctrine, Symfony Messenger.

## Goals / Non-Goals

**Goals:**
- Рабочее Docker-окружение для dev и prod
- Инициализированный Symfony-проект со всеми зависимостями
- SQLite настроен через Doctrine, WAL mode включён
- Messenger настроен с doctrine transport
- Dev-инструменты: PHPStan, PHP-CS-Fixer, PHPUnit

**Non-Goals:**
- Бизнес-логика, хэндлеры, сущности
- Nutgram конфигурация (webhook endpoint) — отдельный change
- CI/CD pipeline
- Модульная структура `src/` — отдельный change

## Decisions

### 1. Базовый образ: FrankenPHP

**Выбор:** `dunglas/frankenphp:latest-php8.5-alpine` (или ближайший доступный тег).

FrankenPHP = Caddy + PHP в одном бинарнике. Встроенный веб-сервер, не нужен nginx.

**Расширения PHP:** `pdo_sqlite`, `intl`, `zip`, `opcache`.

SQLite не требует установки отдельного сервера — расширение `pdo_sqlite` подключается в Dockerfile через `install-php-extensions`.

### 2. Два compose-файла

**Dev (`docker-compose.yaml`):**
- Volume mount для hot-reload кода
- APP_ENV=dev
- Xdebug (опционально)

**Prod (`docker-compose.prod.yaml`):**
- Код копируется в образ (COPY)
- APP_ENV=prod
- opcache preloading
- restart: unless-stopped

### 3. Сервисы

```
app:     FrankenPHP :80 — обрабатывает HTTP-запросы
worker:  Тот же образ, CMD: php bin/console messenger:consume async --time-limit=3600
```

Worker автоматически перезапускается через `restart: unless-stopped` (prod) или `restart: on-failure` (dev).

### 4. SQLite

SQLite БД хранится в `var/data/app.db`. В Docker — named volume для персистентности. WAL mode включается через Doctrine event listener при подключении к БД.

### 5. Структура файлов

```
/
├── Dockerfile
├── docker-compose.yaml          # dev
├── docker-compose.prod.yaml     # prod
├── .dockerignore
├── composer.json
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml
│   │   ├── messenger.yaml
│   │   └── framework.yaml
│   └── services.yaml
├── public/
│   └── index.php
├── src/
│   └── Kernel.php
├── .env
├── phpstan.neon
├── .php-cs-fixer.dist.php
└── phpunit.xml.dist
```

## Risks / Trade-offs

- **[FrankenPHP + PHP 8.5]** → Может не быть готового образа. Fallback: собрать из исходников или использовать PHP 8.4
- **[FrankenPHP memory leaks]** → Worker mode не используем (только стандартный режим). Messenger worker — отдельный процесс, не в worker mode
- **[SQLite volume в Docker]** → При `docker compose down -v` данные потеряются. Документировать в README