## 1. Реструктуризация репозитория

- [x] 1.1 Переименовать репозиторий на GitHub: `vpn-tg-bot` → `whitevpn` (вручную: Settings → General → Repository name)
- [x] 1.2 Переместить весь код бота в `telegram-bot/` (git mv)
- [x] 1.3 Обновить пути в Dockerfile (context, COPY) — пути уже корректны относительно build context
- [x] 1.4 Обновить пути в docker-compose.yaml (volumes, build context) — пути уже корректны, всё переехало вместе
- [x] 1.5 Обновить phpunit.xml.dist, phpstan.neon (пути) — пути уже корректны относительно telegram-bot/
- [x] 1.6 Обновить git remote URL локально
- [x] 1.7 Проверить что тесты проходят после перемещения

## 2. Gateway (Caddy)

- [x] 2.1 Создать `gateway/Caddyfile` с роутингом доменов
- [x] 2.2 Создать `gateway/Dockerfile` (caddy:alpine + Caddyfile)
- [x] 2.3 Создать `gateway/docker-compose.yaml`

## 3. Multi-env (dev/prod)

- [x] 3.1 Создать `docker-compose.prod.yaml` для prod-бота (порт 8080)
- [x] 3.2 Создать `docker-compose.dev.yaml` для dev-бота (порт 8081)
- [x] 3.3 Настроить отдельные SQLite базы и volumes для каждого окружения
- [x] 3.4 Создать dev-бота в BotFather, получить токен — вручную

## 4. 3x-ui конфигурация

- [x] 4.1 Создать `3x-ui/docker-compose.yaml` для панели