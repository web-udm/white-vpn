## Context

Бот подключен к Telegram через webhook и отвечает "Привет!". Нужно реализовать полный онбординг: регистрация, заявка на подключение, одобрение админом, создание клиента в 3x-ui, просмотр статуса.

Существующие 15 клиентов в 3x-ui будут переименованы вручную в формат `tg_{telegram_id}`. Данные о подписке запрашиваются из 3x-ui API на лету, локально хранится только User entity (связка Telegram ↔ 3x-ui).

## Goals / Non-Goals

**Goals:**
- Полный флоу от /start до получения VPN-ссылки
- Ручное одобрение заявок админом через бота
- Просмотр статуса подписки и subscription URL
- Интеграция с 3x-ui API для управления клиентами

**Non-Goals:**
- Оплата и тарифы (v2)
- Реферальная система (v2)
- WireGuard / wg-easy интеграция (позже)
- Рассылка новостей (позже)
- Миграция клиентов из 3x-ui (вручную, 15 человек)

## Decisions

### 1. Source of truth — 3x-ui API
Статус подписки, трафик, subscription URL запрашиваются из 3x-ui API при каждом обращении. Локально храним только User entity как связку telegram_id ↔ xui_email.

**Альтернатива:** Хранить копию данных в SQLite с периодической синхронизацией. Отклонено — дубляж данных, сложность синхронизации, для 15-50 юзеров не нужна.

### 2. Один клиент с limitIp=3
Каждый юзер получает одного клиента в 3x-ui с `limitIp=3`. Одна ссылка работает на нескольких устройствах одновременно (в отличие от WireGuard).

**Альтернатива:** Несколько клиентов на юзера. Отклонено — сложнее управлять, не нужно для текущего сценария.

### 3. Только VLESS
Первый протокол — VLESS. Другие протоколы (Trojan, WireGuard) добавляются позже через дополнительные адаптеры.

### 4. Модульная структура

```
src/
├── User/
│   ├── Domain/
│   │   ├── Entity/User.php          # telegram_id, xui_email, created_at
│   │   └── Repository/UserRepositoryInterface.php
│   ├── Application/
│   │   ├── Command/RegisterUserCommand.php
│   │   └── Query/GetUserByTelegramIdQuery.php
│   └── Infrastructure/
│       └── Persistence/DoctrineUserRepository.php
│
├── Panel/
│   ├── Domain/
│   │   └── PanelClientInterface.php  # порт для 3x-ui
│   ├── Application/
│   │   ├── Command/CreateClientCommand.php
│   │   └── Query/GetClientStatusQuery.php
│   └── Infrastructure/
│       └── Xui/XuiPanelClient.php    # HTTP-адаптер 3x-ui API
│
├── Subscription/
│   ├── Domain/
│   │   ├── Entity/ConnectionRequest.php  # заявка: pending/approved/rejected
│   │   └── Repository/ConnectionRequestRepositoryInterface.php
│   ├── Application/
│   │   ├── Command/CreateConnectionRequestCommand.php
│   │   ├── Command/ApproveConnectionRequestCommand.php
│   │   └── Command/RejectConnectionRequestCommand.php
│   └── Infrastructure/
│       └── Persistence/DoctrineConnectionRequestRepository.php
│
└── Telegram/Infrastructure/
    ├── Handler/
    │   ├── StartHandler.php           # /start с кнопками
    │   ├── ConnectHandler.php         # "Подключиться"
    │   ├── StatusHandler.php          # "Текущий статус"
    │   ├── SupportHandler.php         # "Поддержка"
    │   ├── AdminApproveHandler.php    # callback одобрения
    │   └── AdminRejectHandler.php     # callback отклонения
    └── ...
```

### 5. Флоу одобрения заявки

```
User: [Подключиться]
  → CreateConnectionRequestCommand (status=pending)
  → Бот отправляет админу (ADMIN_TELEGRAM_ID) уведомление с кнопками [Одобрить] [Отклонить]
  → Бот отвечает юзеру "Заявка на рассмотрении"

Admin: [Одобрить]
  → ApproveConnectionRequestCommand
    → CreateClientCommand (Panel) → 3x-ui API: добавить клиента
    → Бот уведомляет юзера: ссылка + инструкции
```

### 6. 3x-ui API аутентификация
3x-ui использует сессионную cookie-аутентификацию: POST /login → cookie → запросы с cookie. Клиент хранит сессию и переавторизуется при истечении.

## Risks / Trade-offs

- **[3x-ui API недоступен]** → Бот отвечает "Сервис временно недоступен, попробуйте позже". Не блокирует создание заявки (она сохраняется локально).
- **[3x-ui API не документирован официально]** → API стабилен на практике, используется многими. Тесты с mock HTTP покроют контракт.
- **[Нет локального кеша статуса]** → Каждый запрос "Текущий статус" идёт в 3x-ui. При 15-50 юзерах нагрузка минимальна.
- **[Админ не увидел заявку]** → Заявка сохранена в БД, не теряется. Можно добавить напоминания позже.