## ADDED Requirements

### Requirement: Symfony 8.0 проект
SHALL быть инициализирован Symfony 8.0 проект с `composer.json`, `public/index.php`, `src/Kernel.php`, `config/`.

#### Scenario: Symfony отвечает
- **WHEN** отправляется HTTP-запрос на корень приложения
- **THEN** Symfony возвращает ответ (welcome page или 404)

### Requirement: Doctrine ORM с SQLite
SHALL быть настроен Doctrine ORM с SQLite в качестве СУБД. Подключение SHALL использовать WAL mode.

#### Scenario: Подключение к БД
- **WHEN** Doctrine устанавливает соединение с SQLite
- **THEN** выполняется `PRAGMA journal_mode=WAL`

#### Scenario: Создание миграции
- **WHEN** выполняется `php bin/console doctrine:migrations:diff`
- **THEN** миграция создаётся корректно

### Requirement: Symfony Messenger
SHALL быть настроен Symfony Messenger с doctrine transport для async очереди.

#### Scenario: Async transport
- **WHEN** в `messenger.yaml` определён transport `async`
- **THEN** transport использует `doctrine://default?queue_name=async`

### Requirement: Dev-инструменты
SHALL быть установлены dev-зависимости: PHPStan (level 6+), PHP-CS-Fixer (PSR-12), PHPUnit.

#### Scenario: PHPStan проходит
- **WHEN** выполняется `vendor/bin/phpstan analyse src/`
- **THEN** 0 ошибок

#### Scenario: PHP-CS-Fixer проходит
- **WHEN** выполняется `vendor/bin/php-cs-fixer fix --dry-run`
- **THEN** 0 нарушений

#### Scenario: PHPUnit запускается
- **WHEN** выполняется `php bin/phpunit`
- **THEN** тесты проходят (пустой тест-suite или smoke test)