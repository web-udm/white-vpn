<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Separate Subscription and VPNConnection domains; rename connection_requests to subscription_requests; migrate existing data; remove sub_id from users';
    }

    public function up(Schema $schema): void
    {
        // 1. Create subscriptions table
        $this->addSql('CREATE TABLE subscriptions (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, is_vip BOOLEAN NOT NULL, status VARCHAR(20) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL)');

        // 2. Create vpn_connections table
        $this->addSql('CREATE TABLE vpn_connections (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, subscription_id INTEGER NOT NULL, sub_id VARCHAR(36) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_VPN_CONNECTIONS_SUB_ID ON vpn_connections (sub_id)');

        // 3. Rename connection_requests -> subscription_requests (SQLite requires temp table dance)
        $this->addSql('CREATE TABLE subscription_requests (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, telegram_id BIGINT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO subscription_requests SELECT * FROM connection_requests');
        $this->addSql('DROP TABLE connection_requests');

        // 4. Data migration: create subscription + vpn_connection for users with existing sub_id
        $this->addSql("INSERT INTO subscriptions (user_id, is_vip, status, expires_at, created_at) SELECT id, 0, 'active', NULL, created_at FROM users WHERE sub_id IS NOT NULL AND sub_id != ''");
        $this->addSql("INSERT INTO vpn_connections (subscription_id, sub_id, status, created_at) SELECT s.id, u.sub_id, 'active', u.created_at FROM subscriptions s JOIN users u ON u.id = s.user_id WHERE u.sub_id IS NOT NULL AND u.sub_id != ''");

        // 5. Remove sub_id from users (SQLite requires temp table dance)
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, telegram_id, created_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, telegram_id BIGINT NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO users (id, telegram_id, created_at) SELECT id, telegram_id, created_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9CC0B3066 ON users (telegram_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE subscriptions');
        $this->addSql('DROP TABLE vpn_connections');
        $this->addSql('CREATE TABLE connection_requests (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, telegram_id BIGINT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO connection_requests SELECT * FROM subscription_requests');
        $this->addSql('DROP TABLE subscription_requests');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, telegram_id, created_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, telegram_id BIGINT NOT NULL, created_at DATETIME NOT NULL, sub_id VARCHAR(36) NOT NULL DEFAULT \'\')');
        $this->addSql('INSERT INTO users (id, telegram_id, created_at) SELECT id, telegram_id, created_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9CC0B3066 ON users (telegram_id)');
    }
}