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
- `relay/docker-compose.yaml` — запускает `nginx:alpine` со stream-конфигом
- `relay/nginx.conf` — L4 TCP/UDP proxy конфиг

**nginx stream** маршрутизирует по портам:
- `443 TCP` → `$MAIN_SERVER_IP:443`
- `$REALITY_PORT TCP` → `$MAIN_SERVER_IP:$REALITY_PORT`
- `$SS_PORT TCP` → `$MAIN_SERVER_IP:$SS_PORT`
- `$SS_PORT UDP` → `$MAIN_SERVER_IP:$SS_PORT`

Переменные окружения подставляются через `envsubst` при старте контейнера.

### .github/workflows/relay.yaml

Повторяет паттерн существующих workflows (например `3x-ui.yaml`): SCP файлов `relay/` на VDSINA, SSH для запуска `docker compose up -d`. Шаг сборки образа не нужен — используется `nginx:alpine` напрямую.

**Новые GitHub Secrets:**

| Secret | Описание |
|--------|----------|
| `RELAY_HOST` | IP московского сервера VDSINA |
| `RELAY_USER` | SSH-пользователь на VDSINA |
| `RELAY_SSH_KEY` | SSH-ключ для VDSINA |
| `MAIN_SERVER_IP` | IP основного сервера (подставляется в nginx.conf) |
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