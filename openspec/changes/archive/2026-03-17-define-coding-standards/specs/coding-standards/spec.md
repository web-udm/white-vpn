## ADDED Requirements

### Requirement: Стиль кода PHP
Код SHALL следовать PSR-12 (Extended Coding Style). Форматирование SHALL проверяться PHP-CS-Fixer.

#### Scenario: Проверка стиля
- **WHEN** запускается `php-cs-fixer fix --dry-run`
- **THEN** нет нарушений стиля в файлах `src/`

### Requirement: Строгая типизация
Все PHP-файлы SHALL содержать `declare(strict_types=1)`. Все методы SHALL иметь типизированные параметры и return type.

#### Scenario: Новый файл
- **WHEN** создаётся новый PHP-файл
- **THEN** первая строка после `<?php` — `declare(strict_types=1);`

### Requirement: Именование
- Классы SHALL использовать PascalCase
- Методы и переменные SHALL использовать camelCase
- Константы SHALL использовать UPPER_SNAKE_CASE
- Команды SHALL именоваться `{Action}{Entity}Command` (например, `ApproveApplicationCommand`)
- Запросы SHALL именоваться `Get{Entity}{Detail}Query` (например, `GetSubscriptionStatusQuery`)
- Хэндлеры SHALL именоваться `{CommandOrQuery}Handler`

#### Scenario: Именование CQRS
- **WHEN** создаётся команда для одобрения заявки
- **THEN** файлы называются `ApproveApplicationCommand.php` и `ApproveApplicationCommandHandler.php`

### Requirement: Статический анализ
Проект SHALL использовать PHPStan на уровне не ниже level 6.

#### Scenario: Запуск анализа
- **WHEN** запускается `phpstan analyse src/`
- **THEN** нет ошибок

### Requirement: Тестирование
Модульные тесты SHALL покрывать Domain и Application слои. Интеграционные тесты SHALL покрывать Infrastructure адаптеры.

#### Scenario: Тест Domain-логики
- **WHEN** тестируется Entity или Domain Service
- **THEN** тест не требует фреймворка, БД или внешних сервисов

#### Scenario: Тест адаптера
- **WHEN** тестируется Doctrine Repository или HTTP-клиент
- **THEN** тест использует реальную SQLite БД или mock HTTP-сервер