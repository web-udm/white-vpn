# VPN Telegram Bot

## Стек
- PHP 8.5 + Symfony 8.0
- Telegram: SergiX44/Nutgram
- БД: SQLite + Doctrine ORM (WAL mode)
- Очереди: Symfony Messenger + doctrine transport
- Деплой: Docker + docker compose
- VCS: GitHub

## Архитектура

### Модульная структура
```
src/
├── User/
├── Subscription/
├── Panel/              # 3x-ui интеграция
├── Telegram/           # Чистый адаптер, нет Domain
└── Notification/
```

### Слои внутри модуля
- `Domain/` — Entity, Repository (интерфейсы), Event. **0 зависимостей от фреймворка.**
- `Application/Command/` — команды записи + хэндлеры
- `Application/Query/` — запросы чтения + хэндлеры
- `Infrastructure/` — Doctrine реализации, HTTP-клиенты, адаптеры

### Правила
- **CQRS**: все операции через Command Bus или Query Bus (включая синхронные query)
- **Hexagonal**: порты (интерфейсы) в Domain, адаптеры в Infrastructure
- **Telegram — адаптер**: без Domain слоя, вызывает Application других модулей через bus
- **Межмодульное взаимодействие**: только через Domain Events или Command/Query Bus. Прямые вызовы запрещены.

## Стандарты кода
- PSR-12 (PHP-CS-Fixer)
- `declare(strict_types=1)` в каждом файле
- PHPStan level 6+
- Именование:
  - Команды: `{Action}{Entity}Command` (например `ApproveApplicationCommand`)
  - Запросы: `Get{Entity}{Detail}Query` (например `GetSubscriptionStatusQuery`)
  - Хэндлеры: `{CommandOrQuery}Handler`

## TDD
- **Сначала тест, потом реализация.** Red → Green → Refactor.
- Domain/Application — unit-тесты (без фреймворка, без БД)
- Infrastructure — integration-тесты (реальная SQLite, mock HTTP)

## После завершения каждой фичи
Перед тем как сообщить о завершении, ОБЯЗАТЕЛЬНО запустить:
1. `php bin/phpunit` — все тесты должны проходить
2. `vendor/bin/phpstan analyse src/` — 0 ошибок
Если что-то падает — исправить до завершения.

## OpenSpec
Детальные спецификации: `openspec/specs/`