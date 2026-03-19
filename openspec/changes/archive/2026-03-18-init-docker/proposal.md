## Why

Проект не имеет окружения для запуска. Нужно создать Docker-инфраструктуру, чтобы можно было локально разрабатывать и деплоить на VPS. Также необходимо инициализировать Symfony-проект с базовыми зависимостями.

## What Changes

- Создаётся `Dockerfile` на базе FrankenPHP (PHP 8.5 + встроенный веб-сервер)
- Создаётся `docker-compose.yaml` (dev) с двумя сервисами: app (FrankenPHP) и worker (Messenger consumer)
- Создаётся `docker-compose.prod.yaml` (prod) с production-настройками
- Инициализируется Symfony 8.0 проект с базовыми зависимостями
- Настраивается SQLite через Doctrine ORM
- Настраивается Symfony Messenger с doctrine transport
- Добавляются dev-инструменты: PHPStan, PHP-CS-Fixer, PHPUnit

## Capabilities

### New Capabilities
- `docker-environment`: Docker-окружение проекта — Dockerfile, compose файлы, конфигурация сервисов
- `symfony-init`: Инициализация Symfony-проекта — composer, конфигурация, базовые зависимости

### Modified Capabilities

## Impact

- Корень проекта: `Dockerfile`, `docker-compose.yaml`, `docker-compose.prod.yaml`, `.dockerignore`
- Symfony: `composer.json`, `config/`, `public/`, `src/`, `.env`
- Зависимости: `dunglas/frankenphp`, `doctrine/orm`, `symfony/messenger`, `nutgram/nutgram`, `phpstan`, `php-cs-fixer`, `phpunit`
