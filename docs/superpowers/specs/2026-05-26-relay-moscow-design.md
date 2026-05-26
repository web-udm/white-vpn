# Дизайн: московский relay-сервер (VDSINA)

## Проблема

IP основного VPN-сервера полностью заблокирован российскими провайдерами — без VPN недоступны ни панель администратора, ни subscription URL'ы. Клиенты не могут подключиться через Reality (VLESS/Trojan) и обновить подписки.

## Решение

Запустить TCP relay на сервере VDSINA в Москве. Российские клиенты подключаются к московскому IP; relay пробрасывает сырые TCP-байты на основной сервер. Без расшифровки TLS, без анализа протокола — чистый L4 passthrough.

## Архитектура

```
Клиент (Россия)
    │ TCP
    ▼
VDSINA Москва (relay/)
  :443            ──TCP relay──► Основной сервер :443 (Caddy — панель, sub, bot)
  :REALITY_PORT   ──TCP relay──► Основной сервер :REALITY_PORT (3x-ui Reality)
  :SS_PORT TCP    ──TCP relay──► Основной сервер :SS_PORT (3x-ui Shadowsocks)
  :SS_PORT UDP    ──UDP relay──► Основной сервер :SS_PORT (3x-ui Shadowsocks)
```

Reality и Shadowsocks расшифровываются на основном сервере — московский relay трафик не видит.

## Компоненты

### relay/ сервис

**Файлы:**
- `relay/Dockerfile` — кастомный Caddy с плагином `caddy-l4` (через `xcaddy`)
- `relay/docker-compose.yaml` — запускает кастомный образ Caddy
- `relay/caddy.json` — JSON-конфиг Caddy L4 (Caddyfile не поддерживает L4-директивы)

**Caddy L4** маршрутизирует по портам:
- `443 TCP` → `{env.MAIN_SERVER_IP}:443`
- `{env.RELAY_REALITY_PORT} TCP` → `{env.MAIN_SERVER_IP}:{env.RELAY_REALITY_PORT}`
- `{env.RELAY_SS_PORT} TCP` → `{env.MAIN_SERVER_IP}:{env.RELAY_SS_PORT}`
- `{env.RELAY_SS_PORT} UDP` → `{env.MAIN_SERVER_IP}:{env.RELAY_SS_PORT}`

Переменные окружения подставляются через нативный синтаксис Caddy `{env.VAR}` — `envsubst` не нужен.

### .github/workflows/relay.yaml

Повторяет паттерн `gateway.yaml`: сборка кастомного образа с `caddy-l4` и пуш в GHCR, затем SCP файлов `relay/` на VDSINA и SSH для запуска `docker compose up -d`. Шаг сборки нужен — образ кастомный.

**Новые GitHub Secrets:**

| Secret | Описание |
|--------|----------|
| `RELAY_HOST` | IP московского сервера VDSINA |
| `RELAY_USER` | SSH-пользователь на VDSINA |
| `RELAY_SSH_KEY` | SSH-ключ для VDSINA |
| `MAIN_SERVER_IP` | IP основного сервера (подставляется в caddy.json) |
| `RELAY_REALITY_PORT` | Порт Reality inbound на основном сервере |
| `RELAY_SS_PORT` | Порт Shadowsocks inbound на основном сервере |

### Shadowsocks inbound

Добавить Shadowsocks inbound в существующую панель 3x-ui на основном сервере. Новый Docker-сервис не нужен — 3x-ui нативно поддерживает Shadowsocks. Порт inbound должен совпадать с `RELAY_SS_PORT`.

### Обновление DNS

После деплоя relay: перевести A-записи VPN-доменов на IP московского сервера VDSINA. Subscription URL'ы и клиентские конфиги менять не нужно — домены остаются прежними.

| Домен | Новая A-запись |
|-------|---------------|
| `sub.whitevpn.tech` | IP VDSINA |
| `panel.whitevpn.tech` | IP VDSINA |
| `bot-prod.whitevpn.tech` | IP VDSINA |
| `awg.whitevpn.tech` | IP VDSINA |
| `hy2.whitevpn.tech` | IP VDSINA |

## Что не меняется

- Основной сервер: без изменений в 3x-ui, Caddy и других сервисах
- Subscription URL'ы: те же домены, прозрачно для клиентов
- AmneziaWG: UDP блокируется провайдерами, relay не поможет, сервис остаётся как есть

## За рамками задачи

- Relay для AmneziaWG (UDP заблокирован, никогда не работал)
- Relay для Hysteria2 (сервис отключён)
- Rate limiting и контроль доступа на relay