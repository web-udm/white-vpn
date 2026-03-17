## ADDED Requirements

### Requirement: PHP и фреймворк
Проект SHALL использовать PHP 8.5 и Symfony 8.0. В случае проблем совместимости — откат на PHP 8.4 / Symfony 7.x.

#### Scenario: Инициализация проекта
- **WHEN** создаётся новый проект
- **THEN** используется `symfony new` с PHP 8.5 и Symfony 8.0

### Requirement: Telegram Bot библиотека
Проект SHALL использовать SergiX44/Nutgram для взаимодействия с Telegram Bot API.

#### Scenario: Обработка webhook
- **WHEN** Telegram отправляет webhook запрос
- **THEN** Nutgram обрабатывает его через зарегистрированные хэндлеры

### Requirement: База данных
Проект SHALL использовать SQLite через Doctrine ORM с включённым WAL-режимом.

#### Scenario: Конкурентные запросы
- **WHEN** несколько webhook-запросов поступают одновременно
- **THEN** SQLite WAL mode обеспечивает корректную конкурентную запись

### Requirement: Очереди сообщений
Проект SHALL использовать Symfony Messenger с doctrine transport для асинхронных задач.

#### Scenario: Асинхронная рассылка
- **WHEN** админ инициирует рассылку
- **THEN** сообщения отправляются через Messenger worker, не блокируя webhook

### Requirement: Деплой и инфраструктура
Проект SHALL деплоиться через Docker Compose на Hetzner VPS. HTTPS SHALL обеспечиваться nginx + certbot.

#### Scenario: Развёртывание
- **WHEN** выполняется `docker compose up -d`
- **THEN** запускаются контейнеры php-fpm, nginx, messenger worker

### Requirement: VCS
Проект SHALL храниться на GitHub в публичном репозитории.

#### Scenario: Публикация кода
- **WHEN** разработчик пушит изменения
- **THEN** код доступен на GitHub как портфолио