#!/bin/sh
set -e

SECRETS_FILE="/data/secrets.txt"
PORT="${MTPROXY_PORT:-8443}"
PROXY_SECRET="/tmp/proxy-secret"
BACKEND_CONF="/tmp/backend.conf"

curl -s https://core.telegram.org/getProxySecret -o "$PROXY_SECRET"
curl -s https://core.telegram.org/getProxyConfig -o "$BACKEND_CONF"

SECRETS_ARGS=""
if [ -f "$SECRETS_FILE" ]; then
    while IFS= read -r line; do
        line=$(echo "$line" | tr -d '[:space:]')
        if [ -n "$line" ]; then
            SECRETS_ARGS="$SECRETS_ARGS -S $line"
        fi
    done < "$SECRETS_FILE"
fi

if [ -z "$SECRETS_ARGS" ]; then
    echo "ERROR: No secrets found in $SECRETS_FILE" >&2
    exit 1
fi

TAG_ARG=""
if [ -n "$AD_TAG" ]; then
    TAG_ARG="-P $AD_TAG"
fi

exec /usr/local/bin/mtproto-proxy \
    -u nobody \
    -p 2398 \
    -H "$PORT" \
    -M 2 \
    --aes-pwd "$PROXY_SECRET" \
    "$BACKEND_CONF" \
    $SECRETS_ARGS \
    $TAG_ARG
