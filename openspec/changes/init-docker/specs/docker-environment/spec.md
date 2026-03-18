## ADDED Requirements

### Requirement: Dockerfile на базе FrankenPHP
Проект SHALL использовать FrankenPHP (Alpine) в качестве базового образа. Dockerfile SHALL устанавливать расширения: pdo_sqlite, intl, zip, opcache.

#### Scenario: Сборка образа
- **WHEN** выполняется `docker build .`
- **THEN** образ собирается без ошибок и содержит PHP с расширениями pdo_sqlite, intl, zip, opcache

### Requirement: Dev compose файл
SHALL существовать `docker-compose.yaml` для разработки. Файл SHALL содержать сервисы: app (FrankenPHP :80) и worker (Messenger consumer). Код SHALL монтироваться через volume для hot-reload.

#### Scenario: Запуск dev-окружения
- **WHEN** выполняется `docker compose up -d`
- **THEN** контейнер app доступен на порту 80 и отдаёт Symfony welcome page

#### Scenario: Hot-reload в dev
- **WHEN** изменяется PHP-файл на хосте
- **THEN** изменения сразу видны в контейнере без пересборки

### Requirement: Prod compose файл
SHALL существовать `docker-compose.prod.yaml` для production. Код SHALL копироваться в образ (не volume). Контейнеры SHALL иметь `restart: unless-stopped`.

#### Scenario: Запуск prod-окружения
- **WHEN** выполняется `docker compose -f docker-compose.prod.yaml up -d`
- **THEN** контейнеры запускаются и автоматически рестартуют при падении

### Requirement: Worker контейнер
Worker SHALL запускаться как отдельный контейнер из того же образа. Worker SHALL выполнять `messenger:consume async` с `--time-limit=3600`.

#### Scenario: Перезапуск worker
- **WHEN** worker завершается по time-limit
- **THEN** Docker автоматически перезапускает контейнер

### Requirement: SQLite persistence
SQLite файл SHALL храниться в named Docker volume. Файл SHALL располагаться в `var/data/app.db`.

#### Scenario: Данные сохраняются между перезапусками
- **WHEN** выполняется `docker compose down` и затем `docker compose up -d`
- **THEN** данные в SQLite БД сохранены

### Requirement: .dockerignore
SHALL существовать `.dockerignore` исключающий `.git`, `var/`, `vendor/`, `.idea/`, `node_modules/`.

#### Scenario: Контекст сборки
- **WHEN** собирается Docker-образ
- **THEN** файлы из .dockerignore не попадают в build context