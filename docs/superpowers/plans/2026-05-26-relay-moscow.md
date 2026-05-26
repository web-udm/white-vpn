# Moscow Relay Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Поднять TCP relay на московском сервере VDSINA, чтобы российские клиенты могли подключаться к VPN и subscription URL'ам через незаблокированный IP.

**Architecture:** Кастомный Caddy с плагином `caddy-l4` проксирует L4 TCP/UDP на основной сервер. Переменные окружения (IP, порты) подставляются через `envsubst` при старте контейнера из `caddy.json.template`. CI/CD — GitHub Actions по паттерну `gateway.yaml`: сборка образа → GHCR → деплой на VDSINA по SSH.

**Tech Stack:** Caddy + caddy-l4, Docker, GitHub Actions, appleboy/scp-action, appleboy/ssh-action.

---

## Файловая структура

| Файл | Действие | Назначение |
|------|----------|-----------|
| `relay/caddy.json.template` | Create | L4 TCP/UDP proxy конфиг с `${VAR}` плейсхолдерами |
| `relay/Dockerfile` | Create | xcaddy build с caddy-l4, gettext для envsubst |
| `relay/docker-compose.yaml` | Create | Сервис relay с env-переменными |
| `.github/workflows/relay.yaml` | Create | Build → push GHCR → deploy на VDSINA |

---

## Task 1: caddy.json.template — конфиг L4 relay

**Files:**
- Create: `relay/caddy.json.template`

- [ ] **Создать файл `relay/caddy.json.template`**

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
                  "upstreams": [{"dial": ["${MAIN_SERVER_IP}:443"]}]
                }
              ]
            }
          ]
        },
        "relay_reality": {
          "listen": [":${RELAY_REALITY_PORT}"],
          "routes": [
            {
              "handle": [
                {
                  "handler": "proxy",
                  "upstreams": [{"dial": ["${MAIN_SERVER_IP}:${RELAY_REALITY_PORT}"]}]
                }
              ]
            }
          ]
        },
        "relay_ss_tcp": {
          "listen": [":${RELAY_SS_PORT}"],
          "routes": [
            {
              "handle": [
                {
                  "handler": "proxy",
                  "upstreams": [{"dial": ["${MAIN_SERVER_IP}:${RELAY_SS_PORT}"]}]
                }
              ]
            }
          ]
        },
        "relay_ss_udp": {
          "listen": ["udp/:${RELAY_SS_PORT}"],
          "routes": [
            {
              "handle": [
                {
                  "handler": "proxy",
                  "upstreams": [{"dial": ["udp/${MAIN_SERVER_IP}:${RELAY_SS_PORT}"]}]
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

- [ ] **Проверить синтаксис JSON**

```bash
python3 -m json.tool relay/caddy.json.template > /dev/null && echo "OK"
```

Ожидаемый вывод: `OK`

- [ ] **Commit**

```bash
git add relay/caddy.json.template
git commit -m "feature: add caddy L4 relay config template"
```

---

## Task 2: Dockerfile — кастомный Caddy с caddy-l4

**Files:**
- Create: `relay/Dockerfile`

- [ ] **Создать `relay/Dockerfile`**

```dockerfile
FROM caddy:builder AS builder
RUN xcaddy build --with github.com/mholt/caddy-l4

FROM caddy:alpine
RUN apk add --no-cache gettext
COPY --from=builder /usr/bin/caddy /usr/bin/caddy
COPY caddy.json.template /etc/caddy/caddy.json.template
CMD ["/bin/sh", "-c", "envsubst < /etc/caddy/caddy.json.template > /etc/caddy/caddy.json && caddy run --config /etc/caddy/caddy.json"]
```

- [ ] **Проверить сборку локально**

```bash
docker build ./relay -t white-vpn-relay:test
```

Ожидаемый вывод: `Successfully built ...` (сборка занимает 2-5 минут — xcaddy компилирует Go-бинарь).

- [ ] **Проверить что caddy-l4 встроен в собранный бинарь**

```bash
docker run --rm white-vpn-relay:test caddy list-modules | grep layer4
```

Ожидаемый вывод: несколько строк, начинающихся с `layer4.`

- [ ] **Commit**

```bash
git add relay/Dockerfile
git commit -m "feature: add Dockerfile for Caddy relay with caddy-l4"
```

---

## Task 3: docker-compose.yaml — сервис relay

**Files:**
- Create: `relay/docker-compose.yaml`

- [ ] **Создать `relay/docker-compose.yaml`**

```yaml
services:
  relay:
    image: ghcr.io/web-udm/white-vpn-relay:latest
    container_name: relay_caddy
    network_mode: host
    restart: unless-stopped
    environment:
      MAIN_SERVER_IP: ${MAIN_SERVER_IP}
      RELAY_REALITY_PORT: ${RELAY_REALITY_PORT}
      RELAY_SS_PORT: ${RELAY_SS_PORT}
```

- [ ] **Проверить синтаксис compose-файла**

```bash
docker compose -f relay/docker-compose.yaml config > /dev/null && echo "OK"
```

Ожидаемый вывод: `OK` (будут warnings о незаданных переменных — это нормально, они будут в `.env` на сервере).

- [ ] **Commit**

```bash
git add relay/docker-compose.yaml
git commit -m "feature: add docker-compose for relay service"
```

---

## Task 4: GitHub Actions workflow — CI/CD для relay

**Files:**
- Create: `.github/workflows/relay.yaml`

- [ ] **Создать `.github/workflows/relay.yaml`**

```yaml
name: Relay CI/CD

on:
  push:
    branches: [master]
    paths:
      - relay/**
  workflow_dispatch:

env:
  IMAGE: ghcr.io/web-udm/white-vpn-relay

jobs:
  build:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write
    steps:
      - uses: actions/checkout@v6

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Log in to GHCR
        uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Build and push
        uses: docker/build-push-action@v6
        with:
          context: ./relay
          file: ./relay/Dockerfile
          push: true
          tags: ${{ env.IMAGE }}:latest
          cache-from: type=registry,ref=${{ env.IMAGE }}:cache
          cache-to: type=registry,ref=${{ env.IMAGE }}:cache,mode=max

  deploy:
    runs-on: ubuntu-latest
    needs: build
    steps:
      - uses: actions/checkout@v6

      - name: Copy files to server
        uses: appleboy/scp-action@v0.1.7
        with:
          host: ${{ secrets.RELAY_HOST }}
          username: ${{ secrets.RELAY_USER }}
          key: ${{ secrets.RELAY_SSH_KEY }}
          source: "relay/docker-compose.yaml,relay/caddy.json.template"
          target: /app
          strip_components: 0

      - name: Deploy
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.RELAY_HOST }}
          username: ${{ secrets.RELAY_USER }}
          key: ${{ secrets.RELAY_SSH_KEY }}
          script: |
            echo "${{ secrets.GITHUB_TOKEN }}" | docker login ghcr.io -u ${{ github.actor }} --password-stdin
            cat > /app/relay/.env << EOF
            MAIN_SERVER_IP=${{ secrets.MAIN_SERVER_IP }}
            RELAY_REALITY_PORT=${{ secrets.RELAY_REALITY_PORT }}
            RELAY_SS_PORT=${{ secrets.RELAY_SS_PORT }}
            EOF
            cd /app/relay
            docker compose pull
            docker compose down
            docker compose up -d
```

- [ ] **Commit**

```bash
git add .github/workflows/relay.yaml
git commit -m "feature: add GitHub Actions workflow for relay deploy"
```

---

## Task 5: GitHub Secrets (ручной шаг)

Добавить в репозиторий на GitHub (Settings → Secrets and variables → Actions):

| Secret | Значение |
|--------|----------|
| `RELAY_HOST` | IP московского сервера VDSINA |
| `RELAY_USER` | SSH-пользователь на VDSINA (обычно `root`) |
| `RELAY_SSH_KEY` | Приватный SSH-ключ для VDSINA |
| `MAIN_SERVER_IP` | IP основного VPN-сервера |
| `RELAY_REALITY_PORT` | Порт Reality inbound в 3x-ui |
| `RELAY_SS_PORT` | Порт Shadowsocks inbound в 3x-ui |

- [ ] **Добавить все 6 secrets в GitHub репозиторий**

---

## Task 6: Shadowsocks inbound в 3x-ui (ручной шаг)

- [ ] **Открыть панель 3x-ui** (через WireGuard, пока DNS не переключён)

- [ ] **Добавить новый inbound:**
  - Protocol: Shadowsocks
  - Port: значение `RELAY_SS_PORT` из Task 5
  - Encryption: `chacha20-ietf-poly1305` (рекомендуется)
  - Создать клиентов для всех активных подписок (или включить multi-user через subscription)

---

## Task 7: Запустить workflow и обновить DNS

- [ ] **Запустить workflow вручную** (GitHub → Actions → Relay CI/CD → Run workflow) или пушнуть любое изменение в `relay/`

- [ ] **Убедиться что workflow завершился успешно** — зайти в Actions и проверить зелёный статус

- [ ] **Проверить что relay запущен на VDSINA:**

```bash
ssh RELAY_USER@RELAY_HOST "docker ps | grep relay_caddy"
```

Ожидаемый вывод: строка с `relay_caddy` и статусом `Up`

- [ ] **Проверить TCP relay работает** (с локальной машины):

```bash
nc -zv RELAY_HOST 443
nc -zv RELAY_HOST REALITY_PORT
nc -zv RELAY_HOST SS_PORT
```

Ожидаемый вывод: `succeeded` для каждого порта

- [ ] **Обновить DNS A-записи** на московский IP VDSINA:

| Домен | Новая A-запись |
|-------|---------------|
| `sub.whitevpn.tech` | IP VDSINA |
| `panel.whitevpn.tech` | IP VDSINA |
| `bot-prod.whitevpn.tech` | IP VDSINA |
| `awg.whitevpn.tech` | IP VDSINA |
| `hy2.whitevpn.tech` | IP VDSINA |

- [ ] **Дождаться propagation DNS** (обычно 1-5 минут при TTL 300)

- [ ] **Проверить subscription URL работает** (с телефона без VPN):

```bash
curl -I https://sub.whitevpn.tech
```

Ожидаемый вывод: `HTTP/2 200` или редирект

- [ ] **Проверить VPN-подключение** через клиент (VLESS/Trojan Reality) с телефона без VPN