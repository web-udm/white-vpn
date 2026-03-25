## ADDED Requirements

### Requirement: Monorepo directory structure
The repository SHALL have three top-level directories: `telegram-bot/`, `gateway/`, `3x-ui/`. Весь код бота SHALL находиться в `telegram-bot/`. Конфигурация Caddy SHALL находиться в `gateway/`. Конфигурация 3x-ui SHALL находиться в `3x-ui/`.

#### Scenario: Bot code in telegram-bot/
- **WHEN** разработчик открывает репозиторий
- **THEN** весь PHP-код бота, composer.json, docker/, tests/ находятся в `telegram-bot/`

#### Scenario: Gateway config in gateway/
- **WHEN** разработчик открывает `gateway/`
- **THEN** там находятся Caddyfile и Dockerfile для reverse proxy

#### Scenario: 3x-ui config in 3x-ui/
- **WHEN** разработчик открывает `3x-ui/`
- **THEN** там находится docker-compose.yaml для панели

### Requirement: Repository renamed to whitevpn
Репозиторий на GitHub SHALL быть переименован из `vpn-tg-bot` в `whitevpn`.

#### Scenario: GitHub redirect
- **WHEN** пользователь переходит по старому URL `web-udm/vpn-tg-bot`
- **THEN** GitHub перенаправляет на `web-udm/whitevpn`

### Requirement: Git history preserved
При перемещении файлов в `telegram-bot/` git history SHALL быть сохранена.

#### Scenario: Git log shows history
- **WHEN** разработчик выполняет `git log -- telegram-bot/src/`
- **THEN** отображается история коммитов включая те, что были до перемещения