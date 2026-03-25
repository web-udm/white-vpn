## ADDED Requirements

### Requirement: Two bot environments on one server
На сервере SHALL работать два независимых окружения бота: prod и dev.

#### Scenario: Both environments running
- **WHEN** администратор проверяет `docker ps`
- **THEN** видны контейнеры для prod (app + worker) и dev (app + worker)

### Requirement: Separate databases
Каждое окружение SHALL использовать свою SQLite базу данных.

#### Scenario: Prod and dev databases isolated
- **WHEN** пользователь создаёт заявку в dev-боте
- **THEN** заявка не появляется в prod-окружении

### Requirement: Separate Telegram bots
Prod и dev окружения SHALL использовать разных Telegram ботов (разные BOT_TOKEN).

#### Scenario: Dev bot independent
- **WHEN** dev-бот получает сообщение
- **THEN** обработка происходит в dev-окружении, prod-бот не затронут

### Requirement: Different ports
Prod-бот SHALL слушать на порту 8080, dev-бот SHALL слушать на порту 8081.

#### Scenario: No port conflict
- **WHEN** оба окружения запущены
- **THEN** prod отвечает на `:8080`, dev на `:8081`