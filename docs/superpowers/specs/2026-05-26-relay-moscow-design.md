# Design: Moscow Relay Server (VDSINA)

## Problem

The main VPN server IP is fully blocked by Russian ISPs — even the admin panel and subscription URLs are inaccessible without VPN. Clients using Reality (VLESS/Trojan) and the subscription service cannot connect.

## Solution

Add a TCP relay service running on a VDSINA server in Moscow. Russian clients connect to the Moscow IP; the relay forwards raw TCP bytes to the main server. No TLS termination, no protocol awareness — pure L4 passthrough.

## Architecture

```
Client (Russia)
    │ TCP
    ▼
VDSINA Moscow (relay/)
  :443            ──TCP relay──► Main Server :443 (Caddy — panel, sub, bot)
  :REALITY_PORT   ──TCP relay──► Main Server :REALITY_PORT (3x-ui Reality)
  :SS_PORT TCP    ──TCP relay──► Main Server :SS_PORT (3x-ui Shadowsocks)
  :SS_PORT UDP    ──UDP relay──► Main Server :SS_PORT (3x-ui Shadowsocks)
```

Reality and Shadowsocks are decrypted on the main server — the Moscow relay never sees plaintext traffic.

## Components

### relay/ service

**Files:**
- `relay/docker-compose.yaml` — runs `nginx:alpine` with stream config
- `relay/nginx.conf` — L4 TCP/UDP proxy config

**nginx stream config** routes by port:
- `443 TCP` → `$MAIN_SERVER_IP:443`
- `$REALITY_PORT TCP` → `$MAIN_SERVER_IP:$REALITY_PORT`
- `$SS_PORT TCP` → `$MAIN_SERVER_IP:$SS_PORT`
- `$SS_PORT UDP` → `$MAIN_SERVER_IP:$SS_PORT`

Environment variables are substituted via `envsubst` at container startup (Docker entrypoint).

### .github/workflows/relay.yaml

Mirrors the pattern of existing workflows (e.g. `3x-ui.yaml`): SCP `relay/` files to VDSINA, SSH to run `docker compose up -d`. No image build step — uses `nginx:alpine` directly.

**New GitHub Secrets required:**

| Secret | Description |
|--------|-------------|
| `RELAY_HOST` | VDSINA server IP |
| `RELAY_USER` | SSH username on VDSINA |
| `RELAY_SSH_KEY` | SSH private key for VDSINA |
| `MAIN_SERVER_IP` | IP of the main server (injected into nginx.conf) |
| `RELAY_REALITY_PORT` | Reality inbound port on main server |
| `RELAY_SS_PORT` | Shadowsocks inbound port on main server |

### Shadowsocks inbound

Add a Shadowsocks inbound in the existing 3x-ui panel on the main server. No new Docker service needed — 3x-ui natively supports Shadowsocks inbounds. The inbound port must match `RELAY_SS_PORT`.

### DNS update

After relay is deployed: update A-records for VPN-related domains to point to the VDSINA Moscow IP. Subscription URLs and client configs require no changes — domains stay the same.

| Domain | New A record |
|--------|-------------|
| `sub.whitevpn.tech` | VDSINA IP |
| `panel.whitevpn.tech` | VDSINA IP |
| `bot-prod.whitevpn.tech` | VDSINA IP |
| `awg.whitevpn.tech` | VDSINA IP |
| `hy2.whitevpn.tech` | VDSINA IP |

## What Is Not Changing

- Main server setup: no changes to 3x-ui, Caddy, or other services
- Subscription URLs: same domains, transparent to clients
- AmneziaWG: UDP is blocked by Russian ISPs — relay doesn't help, service left as-is

## Out of Scope

- AmneziaWG relay (UDP blocked, never worked)
- Hysteria2 relay (currently disabled)
- Rate limiting or access control on the relay