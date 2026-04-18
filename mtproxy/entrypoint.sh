#!/bin/sh
set -e

SECRETS_FILE="/data/secrets.txt"
PORT="${MTPROXY_PORT:-8443}"

# Download fresh proxy config
curl -s https://core.telegram.org/getProxySecret -o /data/proxy-secret
curl -s https://core.telegram.org/getProxyConfig -o /data/proxy-multi.conf

# Build -S arguments from secrets file
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

exec /bin/mtproto-proxy \
    -u nobody \
    -p 8888 \
    -H "$PORT" \
    $SECRETS_ARGS \
    $TAG_ARG \
    --aes-pwd /data/proxy-secret \
    /data/proxy-multi.conf \
    -M 1
