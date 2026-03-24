## Why

Бот умеет только отвечать "Привет!". Чтобы дать адрес бота людям, нужен рабочий флоу: регистрация пользователя, создание VPN-подключения в 3x-ui, просмотр статуса и ссылки на подключение.

## What Changes

- Команда `/start` с приветствием и inline-кнопками (Подключиться, Текущий статус, Поддержка)
- Сохранение пользователя в SQLite при первом взаимодействии (User entity с telegram_id и xui_email)
- Кнопка "Подключиться": создание заявки → ручное одобрение админом → автоматическое создание клиента в 3x-ui (VLESS, limitIp=3)
- Кнопка "Текущий статус": запрос 3x-ui API на лету → статус подписки, оставшиеся дни, subscription URL
- Кнопка "Поддержка": ссылка на @moildar
- HTTP-клиент для 3x-ui API (создание клиента, получение трафика/статуса, subscription URL)

## Capabilities

### New Capabilities
- `user-registration`: Сохранение пользователя в БД, команда /start с приветствием и кнопками
- `connection-request`: Заявка на подключение с ручным одобрением админом, создание клиента в 3x-ui
- `subscription-status`: Просмотр текущего статуса подписки и ссылки на подключение из 3x-ui
- `xui-integration`: HTTP-клиент для 3x-ui API (CRUD клиентов, получение статуса/трафика)

### Modified Capabilities
- `telegram-webhook`: Замена StartHandler("Привет!") на полноценные хэндлеры с кнопками

## Impact

- Новые модули: `User`, `Subscription`, `Panel`
- Расширение модуля `Telegram` (новые хэндлеры, callback-кнопки)
- Новые зависимости: Doctrine entity/repository для User
- Внешние зависимости: 3x-ui API (HTTP)
- Переменные окружения: XUI_BASE_URL, XUI_USERNAME, XUI_PASSWORD, ADMIN_TELEGRAM_ID