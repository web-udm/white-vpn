<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406194122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, telegram_id BIGINT NOT NULL, sub_id VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9CC0B3066 (telegram_id), UNIQUE INDEX UNIQ_1483A5E956992D9 (sub_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, is_vip TINYINT NOT NULL, status VARCHAR(20) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_subscription_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE subscription_request (id INT AUTO_INCREMENT NOT NULL, telegram_id BIGINT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_subscription_request_user FOREIGN KEY (telegram_id) REFERENCES users (telegram_id) ON DELETE CASCADE, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE vpn_connection (id INT AUTO_INCREMENT NOT NULL, subscription_id INT NOT NULL, protocol VARCHAR(20) NOT NULL, external_id VARCHAR(36) NOT NULL, max_devices INT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_vpn_connection_subscription FOREIGN KEY (subscription_id) REFERENCES subscription (id) ON DELETE CASCADE, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE subscription_request');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE vpn_connection');
    }
}
