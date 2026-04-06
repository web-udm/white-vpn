## ADDED Requirements

### Requirement: Dockerfile на базе FrankenPHP
Проект SHALL использовать FrankenPHP (Alpine) в качестве базового образа. Dockerfile SHALL устанавливать расширения: pdo_mysql, intl, zip, opcache.

#### Scenario: Сборка образа
- **WHEN** выполняется `docker build .`
- **THEN** образ собирается без ошибок и содержит PHP с расширениями pdo_mysql, intl, zip, opcache

### Requirement: Dev compose файл
SHALL существовать `docker-compose.yaml` для разработки. Файл SHALL содержать сервисы: app (FrankenPHP :80), worker (Messenger consumer) и mysql (MySQL 8). Код SHALL монтироваться через volume для hot-reload.

#### Scenario: Запуск dev-окружения
- **WHEN** выполняется `docker compose up -d`
- **THEN** контейнер app доступен на порту 80 и отдаёт Symfony welcome page

#### Scenario: Hot-reload в dev
- **WHEN** изменяется PHP-файл на хосте
- **THEN** изменения сразу видны в контейнере без пересборки

### Requirement: Prod compose файл
SHALL существовать `docker-compose.prod.yaml` для production. Код SHALL копироваться в образ (не volume). Контейнеры SHALL иметь `restart: unless-stopped`. Приложение SHALL подключаться к MySQL через external network `whitevpn_db`.

#### Scenario: Запуск prod-окружения
- **WHEN** выполняется `docker compose -f docker-compose.prod.yaml up -d`
- **THEN** контейнеры запускаются и автоматически рестартуют при падении

### Requirement: Worker контейнер
Worker SHALL запускаться как отдельный контейнер из того же образа. Worker SHALL выполнять `messenger:consume async` с `--time-limit=3600`.

#### Scenario: Перезапуск worker
- **WHEN** worker завершается по time-limit
- **THEN** Docker автоматически перезапускает контейнер

### Requirement: MySQL сервис
SHALL существовать отдельный `mysql/docker-compose.yaml` с одним MySQL 8 контейнером и named network `whitevpn_db`. Init-скрипт SHALL создавать базы `whitevpn_dev` и `whitevpn_prod` при первом запуске. Данные SHALL храниться в named Docker volume.

#### Scenario: Запуск MySQL
- **WHEN** выполняется `docker compose -f mysql/docker-compose.yaml up -d`
- **THEN** MySQL доступен на порту 3306 и сеть `whitevpn_db` создана

#### Scenario: Персистентность данных
- **WHEN** выполняется `docker compose down` и затем `docker compose up -d`
- **THEN** данные в MySQL сохранены (named volume не удаляется)

### Requirement: .dockerignore
SHALL существовать `.dockerignore` исключающий `.git`, `var/`, `vendor/`, `.idea/`, `node_modules/`.

#### Scenario: Контекст сборки
- **WHEN** собирается Docker-образ
- **THEN** файлы из .dockerignore не попадают в build context