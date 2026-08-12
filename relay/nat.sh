#!/bin/sh
# Проброс UDP AmneziaWG на основной сервер средствами ядра.
#
# Caddy layer4 для этого плеча не годится: его UDP-listener читает сокет одной
# горутиной, и когда upstream замолкает, она встаёт навсегда — Recv-Q упирается
# в rmem_default, AWG молчит до ручного рестарта. Так было 11 и 12 августа 2026.
# У netfilter вставать нечему, и после снятия блокировки туннель поднимается сам.
#
# Скрипт идемпотентен: можно запускать повторно.
set -e

MAIN=${DEPLOY_HOST:-89.167.91.252}
PORT=${RELAY_AWG_PORT:-51820}

# входящий клиентский UDP -> основной сервер
iptables -t nat -C PREROUTING -p udp --dport "$PORT" -j DNAT --to-destination "$MAIN:$PORT" 2>/dev/null || \
  iptables -t nat -I PREROUTING 1 -p udp --dport "$PORT" -j DNAT --to-destination "$MAIN:$PORT"

# подменяем src, иначе main ответит клиенту напрямую и тот отбросит пакет
iptables -t nat -C POSTROUTING -p udp -d "$MAIN" --dport "$PORT" -j MASQUERADE 2>/dev/null || \
  iptables -t nat -I POSTROUTING 1 -p udp -d "$MAIN" --dport "$PORT" -j MASQUERADE

# FORWARD policy = DROP; DOCKER-USER просматривается раньше цепочек docker
iptables -C DOCKER-USER -p udp -d "$MAIN" --dport "$PORT" -j ACCEPT 2>/dev/null || \
  iptables -I DOCKER-USER 1 -p udp -d "$MAIN" --dport "$PORT" -j ACCEPT
iptables -C DOCKER-USER -p udp -s "$MAIN" --sport "$PORT" -j ACCEPT 2>/dev/null || \
  iptables -I DOCKER-USER 1 -p udp -s "$MAIN" --sport "$PORT" -j ACCEPT

sysctl -qw net.ipv4.ip_forward=1
# дефолтные 30с короче интервала keepalive у клиентов за NAT
sysctl -qw net.netfilter.nf_conntrack_udp_timeout=180
sysctl -qw net.netfilter.nf_conntrack_udp_timeout_stream=600