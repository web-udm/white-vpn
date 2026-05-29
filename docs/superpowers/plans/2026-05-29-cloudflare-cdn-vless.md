# Cloudflare CDN + VLESS WS/gRPC/xHTTP Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Пустить VLESS gRPC/xHTTP/WS через Cloudflare CDN на поддомен `vpn.whitevpn.tech:443`, убрав зависимость от московского relay для этих протоколов.

**Architecture:** Cloudflare CDN принимает VLESS трафик на `vpn.whitevpn.tech:443`, проксирует на Caddy (gateway), Caddy маршрутизирует по пути к соответствующим inbound'ам 3x-ui. Relay остаётся для AWG, старые порты выключаются только после полной проверки.

**Tech Stack:** Caddy (Caddyfile, whitevpn repo), 3x-ui (Xray), Cloudflare DNS/CDN, Docker Compose, GitHub Actions CI/CD

---

## Файловая структура

| Файл | Действие | Репо |
|------|----------|------|
| `gateway/Caddyfile` | Изменить — добавить vpn.whitevpn.tech | `whitevpn` |
| `relay/caddy.json.template` | Изменить — убрать лишние порты | `whitevpn-agent` |

---

## Task 1: Добавить VLESS WS inbound в 3x-ui (ручная операция)

> Это ручной шаг в панели. Код не меняется.

- [ ] **Шаг 1: Открыть 3x-ui панель**

  Перейти по адресу `panel.whitevpn.tech` → войти → Inbounds.

- [ ] **Шаг 2: Записать порты существующих inbound'ов**

  Открыть gRPC inbound и xHTTP inbound, записать их локальные порты:
  ```
  GRPC_PORT  = ____
  XHTTP_PORT = ____
  ```
  Эти значения нужны для Task 2.

- [ ] **Шаг 3: Создать VLESS WS inbound**

  Нажать «+ Add Inbound»:
  - Protocol: `VLESS`
  - Port: любой свободный локальный (например `10003`) — **запиши его**
  - Network / Transport: `WebSocket`
  - Path: `/ws`
  - TLS: `None` (TLS терминирует Caddy/CF)
  - Добавить пользователей (скопировать UUID из существующего inbound'а)

  ```
  WS_PORT = ____  (тот порт, что указал выше)
  ```

- [ ] **Шаг 4: Убедиться, что inbound запустился**

  В списке Inbounds новый WS inbound должен быть зелёным (Running).

---

## Task 2: Добавить vpn.whitevpn.tech в Caddy

**Files:**
- Modify: `gateway/Caddyfile` (в репо `whitevpn`)

- [ ] **Шаг 1: Открыть Caddyfile**

  Путь: `whitevpn/gateway/Caddyfile`

  Текущее содержимое:
  ```
  bot-prod.whitevpn.tech { ... }
  panel.whitevpn.tech { ... }
  http://hy2.whitevpn.tech { ... }
  awg.whitevpn.tech { ... }
  sub.whitevpn.tech { ... }
  ```

- [ ] **Шаг 2: Добавить виртуальный хост vpn.whitevpn.tech**

  Добавить в конец файла (подставить реальные порты из Task 1):

  ```
  vpn.whitevpn.tech {
      @grpc {
          header Content-Type application/grpc*
      }
      @xhttp path /xhttp*
      @ws    path /ws*

      reverse_proxy @grpc  h2c://localhost:GRPC_PORT
      reverse_proxy @xhttp localhost:XHTTP_PORT
      reverse_proxy @ws    localhost:WS_PORT
  }
  ```

  Заменить `GRPC_PORT`, `XHTTP_PORT`, `WS_PORT` на значения из Task 1, шаг 2 и 3.

  Пример с портами 10001 / 10002 / 10003:
  ```
  vpn.whitevpn.tech {
      @grpc {
          header Content-Type application/grpc*
      }
      @xhttp path /xhttp*
      @ws    path /ws*

      reverse_proxy @grpc  h2c://localhost:10001
      reverse_proxy @xhttp localhost:10002
      reverse_proxy @ws    localhost:10003
  }
  ```

- [ ] **Шаг 3: Закоммитить и запушить**

  ```bash
  git add gateway/Caddyfile
  git commit -m "feature: добавить vpn.whitevpn.tech routing для VLESS gRPC/xHTTP/WS"
  git push origin master
  ```

- [ ] **Шаг 4: Дождаться успешного CI/CD**

  GitHub Actions → репо `whitevpn` → workflow «Gateway CI/CD» должен пройти (build + deploy).
  
  Ожидаемое: оба джоба зелёные, деплой на основной сервер выполнен.

---

## Task 3: Cloudflare — DNS и настройки (ручная операция)

> Два этапа: сначала серое облако (чтобы Caddy получил сертификат), затем оранжевое.

- [ ] **Шаг 1: Добавить DNS A-запись (серое облако)**

  Cloudflare Dashboard → `whitevpn.tech` → DNS → Add record:
  - Type: `A`
  - Name: `vpn`
  - IPv4 address: `<IP основного сервера>`
  - Proxy status: **DNS only (серое облако)**

  Нажать Save. Подождать 1–2 минуты на propagation.

- [ ] **Шаг 2: Дождаться сертификата от Caddy**

  Открыть в браузере `https://vpn.whitevpn.tech` — должен появиться ответ от Caddy (не важно что именно, главное — не ошибка сертификата). Caddy автоматически запросит сертификат через Let's Encrypt.

  Обычно занимает 30–60 секунд после появления DNS-записи.

- [ ] **Шаг 3: Включить Cloudflare proxy (оранжевое облако)**

  DNS → запись `vpn` → Edit → Proxy status: **Proxied (оранжевое облако)** → Save.

- [ ] **Шаг 4: Включить gRPC поддержку**

  CF Dashboard → `whitevpn.tech` → **Network** → **gRPC → On**.

- [ ] **Шаг 5: Проверить SSL режим**

  CF Dashboard → `whitevpn.tech` → **SSL/TLS → Overview** → убедиться, что выбрано **Full (strict)**.

---

## Task 4: Обновить клиентские конфиги в 3x-ui (ручная операция)

> Обновляем адрес во всех VLESS inbound'ах — клиенты получат новые конфиги автоматически через subscription.

- [ ] **Шаг 1: Обновить gRPC inbound**

  3x-ui → Inbounds → редактировать gRPC inbound:
  - Domain / Remark Address: `vpn.whitevpn.tech`
  - Port (в конфиге клиента): `443`
  - TLS: убедиться, что в конфиге клиента будет `tls`
  - Нажать Update

- [ ] **Шаг 2: Обновить xHTTP inbound**

  3x-ui → Inbounds → редактировать xHTTP inbound:
  - Domain / Remark Address: `vpn.whitevpn.tech`
  - Port (в конфиге клиента): `443`
  - Нажать Update

- [ ] **Шаг 3: Настроить WS inbound (созданный в Task 1)**

  3x-ui → Inbounds → редактировать WS inbound:
  - Domain / Remark Address: `vpn.whitevpn.tech`
  - Port (в конфиге клиента): `443`
  - Нажать Update

- [ ] **Шаг 4: Убедиться, что subscription обновилась**

  Открыть subscription URL (`https://sub.whitevpn.tech/...`) — в ответе должны быть конфиги с адресом `vpn.whitevpn.tech` и портом `443`.

---

## Task 5: Проверка соединений

- [ ] **Шаг 1: Проверить gRPC**

  Скачать клиент (v2rayN, Hiddify, Streisand или любой совместимый с VLESS gRPC).
  
  Импортировать конфиг gRPC из обновлённой subscription. Параметры должны быть:
  ```
  address: vpn.whitevpn.tech
  port:    443
  network: grpc
  tls:     true
  ```
  Подключиться, открыть `https://2ip.ru` — должен показать не российский IP.

- [ ] **Шаг 2: Проверить xHTTP**

  Аналогично: импортировать xHTTP конфиг, подключиться, проверить IP.
  ```
  address: vpn.whitevpn.tech
  port:    443
  network: xhttp
  path:    /xhttp
  tls:     true
  ```

- [ ] **Шаг 3: Проверить WS**

  Импортировать WS конфиг, подключиться, проверить IP.
  ```
  address: vpn.whitevpn.tech
  port:    443
  network: ws
  path:    /ws
  tls:     true
  ```

- [ ] **Шаг 4: Проверить AWG**

  Убедиться, что AWG (через relay) продолжает работать — подключиться на телефоне/десктопе. AmneziaWG конфиги не трогали, должны работать без изменений.

- [ ] **Шаг 5: Проверить subscription и панель**

  Открыть `https://sub.whitevpn.tech` и `https://panel.whitevpn.tech` — оба должны быть доступны. Relay по-прежнему прокидывает `:443`.

---

## Task 6: Выключить лишние порты на relay (только после Task 5)

> **Выполнять только после того, как убедился, что все клиенты перешли на CF и жалоб нет.**

**Files:**
- Modify: `relay/caddy.json.template` (в репо `whitevpn-agent`)

- [ ] **Шаг 1: Удалить неиспользуемые relay entries**

  В `whitevpn-agent/relay/caddy.json.template` убрать следующие секции из `"servers"`:
  - `relay_vless`
  - `relay_trojan`
  - `relay_ss_tcp`
  - `relay_ss_udp`
  - `relay_xhttp`
  - `relay_grpc`

  **Оставить:**
  - `relay_443` (нужен для panel/sub/bot через relay)
  - `relay_awg_udp` (нужен для AmneziaWG)

  Итоговая структура `"servers"`:
  ```json
  {
    "apps": {
      "layer4": {
        "servers": {
          "relay_443": {
            "listen": [":443"],
            "routes": [
              {
                "handle": [
                  {
                    "handler": "proxy",
                    "upstreams": [{"dial": ["tcp/${DEPLOY_HOST}:443"]}]
                  }
                ]
              }
            ]
          },
          "relay_awg_udp": {
            "listen": ["udp/:${RELAY_AWG_PORT}"],
            "routes": [
              {
                "handle": [
                  {
                    "handler": "proxy",
                    "upstreams": [{"dial": ["udp/${DEPLOY_HOST}:${RELAY_AWG_PORT}"]}]
                  }
                ]
              }
            ]
          }
        }
      }
    }
  }
  ```

- [ ] **Шаг 2: Также убрать неиспользуемые env var из docker-compose.yaml**

  В `whitevpn-agent/relay/docker-compose.yaml` удалить строки:
  ```yaml
  RELAY_VLESS_PORT: ${RELAY_VLESS_PORT}
  RELAY_TROJAN_PORT: ${RELAY_TROJAN_PORT}
  RELAY_SS_PORT: ${RELAY_SS_PORT}
  RELAY_XHTTP_PORT: ${RELAY_XHTTP_PORT}
  RELAY_GRPC_PORT: ${RELAY_GRPC_PORT}
  ```

  Оставить:
  ```yaml
  environment:
    DEPLOY_HOST: ${DEPLOY_HOST}
    RELAY_AWG_PORT: ${RELAY_AWG_PORT}
  ```

- [ ] **Шаг 3: Закоммитить и запушить**

  ```bash
  git add relay/caddy.json.template relay/docker-compose.yaml
  git commit -m "feature: убрать лишние порты из relay, оставить только AWG"
  git push origin master
  ```

- [ ] **Шаг 4: Дождаться CI/CD relay**

  GitHub Actions → `whitevpn-agent` → «Relay CI/CD» → дождаться зелёного деплоя.

- [ ] **Шаг 5: Убедиться, что relay продолжает работать для AWG и :443**

  - AWG: подключиться через AmneziaWG — должно работать
  - Панель: `https://panel.whitevpn.tech` — должна открываться
