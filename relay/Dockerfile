FROM caddy:builder AS builder
RUN xcaddy build --with github.com/mholt/caddy-l4

FROM caddy:alpine
RUN apk add --no-cache gettext
COPY --from=builder /usr/bin/caddy /usr/bin/caddy
COPY caddy.json.template /etc/caddy/caddy.json.template
CMD ["/bin/sh", "-c", "envsubst < /etc/caddy/caddy.json.template > /etc/caddy/caddy.json && caddy run --config /etc/caddy/caddy.json"]
