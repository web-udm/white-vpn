## 1. User модуль

- [x] 1.1 Создать User entity (telegram_id, xui_email, created_at) + миграция
- [x] 1.2 Создать UserRepositoryInterface (Domain) + DoctrineUserRepository (Infrastructure)
- [x] 1.3 Создать RegisterUserCommand + Handler
- [x] 1.4 Создать GetUserByTelegramIdQuery + Handler
- [x] 1.5 Unit-тесты для RegisterUserCommandHandler

## 2. Panel модуль (3x-ui интеграция)

- [x] 2.1 Создать PanelClientInterface (Domain порт): createClient, getClientStatus, getSubscriptionUrl
- [x] 2.2 Реализовать XuiPanelClient (Infrastructure): аутентификация, cookie-сессия, реавторизация
- [x] 2.3 Реализовать XuiPanelClient: createClient (VLESS, limitIp=3)
- [x] 2.4 Реализовать XuiPanelClient: getClientStatus (enable, expiryTime, traffic)
- [x] 2.5 Реализовать XuiPanelClient: getSubscriptionUrl
- [x] 2.6 Добавить env: XUI_BASE_URL, XUI_USERNAME, XUI_PASSWORD, XUI_INBOUND_ID
- [x] 2.7 Создать CreateClientCommand + Handler
- [x] 2.8 Создать GetClientStatusQuery + Handler
- [x] 2.9 Integration-тесты XuiPanelClient с mock HTTP

## 3. Subscription модуль

- [ ] 3.1 Создать ConnectionRequest entity (user_id, status, created_at, updated_at) + миграция
- [ ] 3.2 Создать ConnectionRequestRepositoryInterface + DoctrineConnectionRequestRepository
- [ ] 3.3 Создать CreateConnectionRequestCommand + Handler
- [ ] 3.4 Создать ApproveConnectionRequestCommand + Handler (вызывает Panel.CreateClientCommand)
- [ ] 3.5 Создать RejectConnectionRequestCommand + Handler
- [ ] 3.6 Unit-тесты для хэндлеров заявок

## 4. Telegram хэндлеры

- [ ] 4.1 Переписать StartHandler: /start с приветствием и inline-кнопками
- [ ] 4.2 Создать ConnectHandler: кнопка "Подключиться" → создание заявки
- [ ] 4.3 Создать StatusHandler: кнопка "Текущий статус" → данные из 3x-ui
- [ ] 4.4 Создать SupportHandler: кнопка "Поддержка" → ссылка на @moildar
- [ ] 4.5 Создать AdminApproveHandler + AdminRejectHandler: callback-кнопки для админа
- [ ] 4.6 Добавить env: ADMIN_TELEGRAM_ID
- [ ] 4.7 Зарегистрировать все хэндлеры в NutgramFactory

## 5. Проверка

- [ ] 5.1 `php bin/phpunit` — все тесты проходят
- [ ] 5.2 `vendor/bin/phpstan analyse src/` — 0 ошибок
- [ ] 5.3 E2E: /start → Подключиться → Админ одобряет → Юзер получает ссылку
- [ ] 5.4 E2E: Текущий статус → показывает данные из 3x-ui