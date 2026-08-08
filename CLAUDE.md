# WhiteVPN

## О проекте
Личный VPN-сервис на ~100 человек: несколько VPN-протоколов на своём сервере плюс
Telegram-бот, через который люди подают заявку на подписку и получают подключения.

Пользователь пишет боту → админ подтверждает заявку → бот заводит подключения в
панелях и отдаёт их в чат (VLESS-ссылка + `.conf`-файлы с QR-кодами для AmneziaWG).

**Общаемся в чате на русском.**

### Что крутится на сервере
Каждый сервис — отдельный каталог в корне репозитория со своим `docker-compose.yaml`
и своим workflow в `.github/workflows/`. Деплой автоматический: push в `master`
триггерит workflow по фильтру `paths`, тот копирует файлы по SSH и перезапускает compose.

| Каталог | Что это |
|---|---|
| `telegram-bot/` | Symfony-приложение, бот (основной код) |
| `3x-ui/` | Панель Xray: VLESS, Hysteria 2 |
| `amneziawg/` | Панель wg-easy v15 с AmneziaWG |
| `gateway/` | Caddy: TLS-терминация и роутинг поддоменов `*.whitevpn.tech` |
| `relay/` | Caddy layer4 на отдельном сервере — проброс трафика на основной |
| `mysql/` | БД для бота |

## Стек
- PHP 8.5 + Symfony 8.0
- Telegram: SergiX44/Nutgram
- БД: MySQL 8 + Doctrine ORM
- Очереди: Symfony Messenger + doctrine transport
- Деплой: Docker + docker compose
- VCS: GitHub

## Архитектура

### Модульная структура
```
src/
├── User/
├── Subscription/
├── VPN/                # VPN-провайдеры (3x-ui и др.)
├── Telegram/           # Чистый адаптер, нет Domain
└── Shared/             # Кросс-модульная инфраструктура
```

### Слои внутри модуля
- `Domain/` — Entity, Repository (интерфейсы), Event. **0 зависимостей от фреймворка.**
- `Application/Command/` — команды записи + хэндлеры
- `Application/Query/` — запросы чтения + хэндлеры
- `Infrastructure/` — Doctrine реализации, HTTP-клиенты, адаптеры

### Правила
- **CQRS**: все операции через Command Bus или Query Bus (включая синхронные query)
- **Hexagonal**: порты (интерфейсы) в Domain, адаптеры в Infrastructure
- **Telegram — адаптер**: без Domain слоя, вызывает Application других модулей через bus
- **Межмодульное взаимодействие**: только через Domain Events или Command/Query Bus. Прямые вызовы запрещены.
- **Modulite**: модули (кроме Shared) ничего не экспортируют (`export:` пуст). Вся коммуникация через bus.

## Стандарты кода
- PSR-12 (PHP-CS-Fixer)
- `declare(strict_types=1)` в каждом файле
- PHPStan level 6+
- Именование:
  - Команды: `{Action}{Entity}Command` (например `ApproveApplicationCommand`)
  - Запросы: `Get{Entity}{Detail}Query` (например `GetSubscriptionStatusQuery`)
  - Хэндлеры: `{CommandOrQuery}Handler`
  - Таблицы БД: единственное число, с префиксом модуля: `subscription`, `subscription_request`, `vpn_connection`, `user`. Множественное число запрещено.
- Расширение `.yaml` (не `.yml`) для всех YAML-файлов
- `new Class()` со скобками всегда: `new Foo()`, не `new Foo`
- Порядок методов: публичные сверху, приватные снизу
- Классы `final readonly` по умолчанию, если нет мутабельного состояния
- Аббревиатуры пишутся БОЛЬШИМИ буквами: `VPN`, `API`, `URL`, `HTTP`, `ID` и т.д.
- `try` должен быть в начале метода. Если try оказывается в середине — вынести блок в приватный метод.
- **Внешние ключи**: всегда добавлять FK-constraint в миграцию, когда колонка ссылается на другую таблицу. Указывать `ON DELETE CASCADE` / `ON DELETE SET NULL` где уместно. Пример: `CONSTRAINT FK_vpn_connection_subscription FOREIGN KEY (subscription_id) REFERENCES subscription(id) ON DELETE CASCADE`
- **`#[ORM\Column]` без `type:`**: Doctrine автоматически определяет тип по PHP-типу свойства (`int`, `string`, `bool`, `\DateTimeImmutable` и т.д.). Явно указывать `type:` не нужно.
- **Кросс-модульные ссылки**: между Bounded Context'ами ссылаться только по ID (int), не по объекту. `#[ORM\ManyToOne]` на Entity из другого модуля запрещён — это нарушение Modulite. FK на уровне БД (в миграции) при этом допустим.

## Тесты
- **Сначала код, потом тесты.** Тесты пишутся после реализации.
- Структура тестов: `// Arrange`, `// Act`, `// Assert`
- Command/Query хэндлеры — integration-тесты через `KernelTestCase` (реальная SQLite), если нет особых причин для unit
- Domain Value Objects — unit-тесты (валидация, equals)
- Infrastructure — integration-тесты (реальная SQLite, mock HTTP)

## После завершения каждой фичи
Перед тем как сообщить о завершении, ОБЯЗАТЕЛЬНО запустить:
1. `php bin/phpunit` — все тесты должны проходить
2. `vendor/bin/phpstan analyse src/` — 0 ошибок
Если что-то падает — исправить до завершения.

## Git
- Формат коммитов: `feature: {краткое описание}`, `fix: ...`, `docs: ...`, `refactor: ...`

## OpenSpec
Детальные спецификации: `openspec/specs/`