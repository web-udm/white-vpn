# Дизайн: Cloudflare CDN + VLESS WS/gRPC/xHTTP

## Проблема

- Основной VPN-сервер заблокирован в России; сейчас трафик идёт через московский TCP relay
- Мобильные операторы применяют белые списки: кастомные порты relay не проходят
- VLESS Reality не работает; пользователи сидят на gRPC и xHTTP
- Нет VLESS WebSocket — популярного транспорта для стабильных соединений

## Решение

Поставить Cloudflare CDN перед основным сервером для всех HTTP-based протоколов (gRPC, xHTTP, WS). Cloudflare скрывает реальный IP сервера и предоставляет anycast-сеть с широким покрытием, в том числе проходит через мобильные белые списки (CF IP — в списках операторов, потому что за ними стоят легитимные сервисы).

## Архитектура

```
Клиент (port 443, HTTPS)
    │
    ▼
Cloudflare CDN — vpn.whitevpn.tech
    │  скрывает IP, anycast, мобильные белые списки
    ▼
Caddy — main server :443 (path-based routing)
    ├── /grpc*   → 3x-ui VLESS gRPC inbound  (localhost:GRPC_PORT,  H2C)
    ├── /xhttp*  → 3x-ui VLESS xHTTP inbound (localhost:XHTTP_PORT)
    └── /ws*     → 3x-ui VLESS WS inbound    (localhost:WS_PORT, WebSocket)

awg.whitevpn.tech → Москва relay → main server (без изменений, UDP)
```

Relay продолжает работать только для AmneziaWG. Все остальные порты relay (VLESS, gRPC, xHTTP, SS, Trojan) выключаются.

## Компоненты

### 1. 3x-ui — новый VLESS WS inbound

Добавить inbound вручную в панели 3x-ui:

- Protocol: VLESS
- Transport: WebSocket
- Path: `/ws`
- Port: локальный (например `10003`), порт не открывать наружу — только для Caddy

Существующие gRPC и xHTTP inbound'ы не трогать, только уточнить их локальные порты.

### 2. Caddy (gateway) — path-based routing

Добавить виртуальный хост `vpn.whitevpn.tech` в конфиг Caddy.  
Caddy уже стоит на `network_mode: host` и держит порт 443 — нужно только добавить маршруты.

```
vpn.whitevpn.tech {
    @grpc  path /grpc*
    @xhttp path /xhttp*
    @ws    path /ws*

    reverse_proxy @grpc  h2c://localhost:{$XRAY_GRPC_PORT}
    reverse_proxy @xhttp localhost:{$XRAY_XHTTP_PORT}
    reverse_proxy @ws    localhost:{$XRAY_WS_PORT}
}
```

Конкретный синтаксис Caddyfile уточняется при реализации (зависит от текущей структуры конфига в gateway/).

### 3. Cloudflare — DNS и настройки

**DNS:**

| Запись | Тип | Значение | Proxy |
|--------|-----|----------|-------|
| `vpn.whitevpn.tech` | A | IP main server | ✅ Orange cloud |
| `awg.whitevpn.tech` | A | IP relay (Москва) | ❌ DNS only |

**Настройки в CF dashboard:**

- SSL/TLS → Overview → **Full (strict)**
- Network → **gRPC → On**
- Network → **WebSockets → On** (по умолчанию включены)

### 4. Клиентские конфиги / подписки

В 3x-ui обновить server address и port во всех VLESS конфигах:

- Address: `vpn.whitevpn.tech`
- Port: `443`
- TLS: enabled
- Path: `/grpc`, `/xhttp`, `/ws` (по транспорту)

Subscription URL'ы пересоздаются в 3x-ui — клиенты получают обновлённые конфиги автоматически.

### 5. Relay — частичное выключение

После успешного тестирования CF CDN отключить в relay конфиге порты:

- RELAY_VLESS_PORT
- RELAY_TROJAN_PORT
- RELAY_SS_PORT
- RELAY_XHTTP_PORT
- RELAY_GRPC_PORT

Оставить только `RELAY_AWG_PORT`. Сервис relay продолжает работать.

## Что не меняется

- AmneziaWG: без изменений, через relay
- Панель 3x-ui (`panel.whitevpn.tech`): продолжает работать через Caddy
- Subscription URL'ы (`sub.whitevpn.tech`): только обновляются адреса в конфигах
- Telegram bot: без изменений

## Порядок деплоя

Принцип: **ничего не ломаем в процессе**. Relay и старые порты работают до конца — новый маршрут добавляется параллельно, а не вместо.

1. Добавить VLESS WS inbound в 3x-ui (новый inbound, старые не трогать)
2. Обновить конфиг Caddy (добавить vpn.whitevpn.tech routing, существующие маршруты не трогать)
3. Добавить A-запись `vpn.whitevpn.tech` в CF с orange cloud
4. Включить gRPC в CF dashboard
5. Проверить вручную: gRPC, xHTTP, WS через `vpn.whitevpn.tech:443` — убедиться, что всё работает
6. Обновить конфиги в 3x-ui → subscription автообновляется у клиентов
7. Дать клиентам время переключиться — relay и старые порты продолжают работать
8. **Только после полной уверенности** (мониторинг, отсутствие жалоб): выключить лишние порты на relay

## За рамками задачи

- Полное выключение relay (AWG остаётся зависимым)
- Миграция AmneziaWG на CF (CF не поддерживает UDP)
- Cloudflare Tunnel (cloudflared) как альтернатива CDN
- Rate limiting и WAF правила на CF
