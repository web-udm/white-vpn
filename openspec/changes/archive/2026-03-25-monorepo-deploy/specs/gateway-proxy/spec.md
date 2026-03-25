## ADDED Requirements

### Requirement: Caddy routes domains to services
Caddy SHALL проксировать запросы по доменам к соответствующим сервисам на localhost.

#### Scenario: Prod bot domain
- **WHEN** запрос приходит на `whitevpn.tech`
- **THEN** Caddy проксирует на `localhost:8080` (bot prod)

#### Scenario: Dev bot domain
- **WHEN** запрос приходит на `dev.whitevpn.tech`
- **THEN** Caddy проксирует на `localhost:8081` (bot dev)

#### Scenario: Panel domain
- **WHEN** запрос приходит на `panel.whitevpn.tech`
- **THEN** Caddy проксирует на `localhost:2053` (3x-ui panel)

#### Scenario: Subscription domain
- **WHEN** запрос приходит на `sub.whitevpn.tech`
- **THEN** Caddy проксирует на `localhost:2096` (3x-ui subscription)

### Requirement: Auto SSL
Caddy SHALL автоматически получать и обновлять SSL-сертификаты через Let's Encrypt.

#### Scenario: HTTPS works
- **WHEN** клиент подключается к `https://whitevpn.tech`
- **THEN** соединение защищено валидным SSL-сертификатом