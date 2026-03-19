## 1. Инициализация Symfony

- [ ] 1.1 Создать Symfony 8.0 проект (composer create-project или вручную composer.json)
- [ ] 1.2 Добавить зависимости: doctrine/orm, doctrine/doctrine-bundle, doctrine/doctrine-migrations-bundle, symfony/messenger
- [ ] 1.3 Добавить dev-зависимости: phpstan/phpstan, friendsofphp/php-cs-fixer, phpunit/phpunit
- [ ] 1.4 Создать `phpstan.neon` (level 6, paths: src/)
- [ ] 1.5 Создать `.php-cs-fixer.dist.php` (PSR-12)
- [ ] 1.6 Создать `phpunit.xml.dist`

## 2. Конфигурация Symfony

- [ ] 2.1 Настроить Doctrine для SQLite в `config/packages/doctrine.yaml` и `.env`
- [ ] 2.2 Создать Doctrine event listener для WAL mode
- [ ] 2.3 Настроить Messenger с doctrine transport в `config/packages/messenger.yaml`
- [ ] 2.4 Обновить `.gitignore` для Symfony (var/, vendor/, .env.local)

## 3. Docker

- [ ] 3.1 Создать `Dockerfile` (FrankenPHP Alpine, расширения: pdo_sqlite, intl, zip, opcache)
- [ ] 3.2 Создать `.dockerignore`
- [ ] 3.3 Создать `docker-compose.yaml` (dev: volume mount, app + worker)
- [ ] 3.4 Создать `docker-compose.prod.yaml` (prod: COPY, restart, opcache)

## 4. Проверка

- [ ] 4.1 `docker compose build` — образ собирается
- [ ] 4.2 `docker compose up -d` — app отвечает на :80
- [ ] 4.3 `vendor/bin/phpstan analyse src/` — 0 ошибок
- [ ] 4.4 `vendor/bin/php-cs-fixer fix --dry-run` — 0 нарушений
- [ ] 4.5 `php bin/phpunit` — тесты проходят