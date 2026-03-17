## ADDED Requirements

### Requirement: Модульная структура
Код SHALL быть организован по модулям верхнего уровня в `src/`: User, Subscription, Panel, Telegram, Notification.

#### Scenario: Создание нового модуля
- **WHEN** добавляется новая бизнес-область (например, Payment)
- **THEN** создаётся директория `src/Payment/` с поддиректориями `Domain/`, `Application/`, `Infrastructure/`

### Requirement: Hexagonal Architecture
Каждый модуль (кроме Telegram) SHALL содержать три слоя: Domain, Application, Infrastructure. Domain SHALL не иметь зависимостей от фреймворка или внешних библиотек.

#### Scenario: Domain не зависит от Symfony
- **WHEN** файл находится в `src/{Module}/Domain/`
- **THEN** он не содержит `use` импортов из Symfony, Doctrine annotations, или других инфраструктурных пакетов

#### Scenario: Порты и адаптеры
- **WHEN** модуль взаимодействует с внешней системой (БД, API)
- **THEN** в Domain определяется интерфейс (порт), а в Infrastructure — реализация (адаптер)

### Requirement: CQRS через Symfony Messenger
Все операции SHALL проходить через Command Bus или Query Bus. Command — для операций записи, Query — для чтения. Синхронные query также SHALL идти через bus.

#### Scenario: Создание команды
- **WHEN** нужна операция записи (например, одобрить заявку)
- **THEN** создаётся `ApproveApplicationCommand` + `ApproveApplicationCommandHandler` в `Application/Command/`

#### Scenario: Создание запроса
- **WHEN** нужна операция чтения (например, получить статус подписки)
- **THEN** создаётся `GetSubscriptionStatusQuery` + `GetSubscriptionStatusQueryHandler` в `Application/Query/`

### Requirement: Telegram как адаптер
Модуль Telegram SHALL быть чистым адаптером без собственного Domain слоя. Он SHALL вызывать Application-слой других модулей через Command/Query Bus.

#### Scenario: Обработка команды бота
- **WHEN** пользователь отправляет команду боту
- **THEN** Telegram Handler диспатчит соответствующий Command или Query в bus другого модуля

### Requirement: Межмодульное взаимодействие
Модули SHALL взаимодействовать друг с другом только через Domain Events или Command/Query Bus. Прямые вызовы между модулями SHALL быть запрещены.

#### Scenario: Одобрение заявки создаёт клиента в 3x-ui
- **WHEN** админ одобряет заявку пользователя
- **THEN** User модуль публикует событие, Panel модуль реагирует на него и создаёт клиента в 3x-ui