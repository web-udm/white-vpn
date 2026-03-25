## ADDED Requirements

### Requirement: Automatic prod deployment on push to master
При пуше в ветку `master` GitHub Actions SHALL собрать Docker image бота, отправить в ghcr.io и задеплоить на сервер.

#### Scenario: Push to master triggers prod deploy
- **WHEN** разработчик пушит в `master`
- **THEN** CI собирает image `ghcr.io/web-udm/whitevpn-bot:prod`, пушит в registry, деплоит на сервер

#### Scenario: Migrations run automatically
- **WHEN** деплой завершён
- **THEN** CI выполняет `doctrine:migrations:migrate --no-interaction` в контейнере

#### Scenario: Webhook set automatically
- **WHEN** деплой завершён
- **THEN** CI выполняет `app:telegram:set-webhook --url=https://whitevpn.tech/webhook`

### Requirement: Automatic dev deployment on push to dev
При пуше в ветку `dev` GitHub Actions SHALL собрать Docker image и задеплоить dev-окружение.

#### Scenario: Push to dev triggers dev deploy
- **WHEN** разработчик пушит в `dev`
- **THEN** CI собирает image `ghcr.io/web-udm/whitevpn-bot:dev`, пушит в registry, деплоит dev-окружение

#### Scenario: Dev webhook set
- **WHEN** dev деплой завершён
- **THEN** CI выполняет `app:telegram:set-webhook --url=https://dev.whitevpn.tech/webhook`

### Requirement: Secrets from GitHub Secrets
CI SHALL генерировать `.env` файлы на сервере из GitHub Secrets.

#### Scenario: Env file generated on deploy
- **WHEN** CI деплоит окружение
- **THEN** `.env` файл создаётся из значений GitHub Secrets (BOT_TOKEN, XUI_*, DB_PATH, etc.)

### Requirement: Docker image built in CI
Docker image бота SHALL собираться в GitHub Actions, а не на сервере.

#### Scenario: Server only pulls image
- **WHEN** CI выполняет деплой
- **THEN** на сервере выполняется `docker pull` + `docker compose up -d`, без `docker build`