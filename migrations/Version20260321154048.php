<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260321154048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, telegram_id, created_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, telegram_id BIGINT NOT NULL, created_at DATETIME NOT NULL, sub_id VARCHAR(36) NOT NULL)');
        $this->addSql('INSERT INTO users (id, telegram_id, created_at) SELECT id, telegram_id, created_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9CC0B3066 ON users (telegram_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E956992D9 ON users (sub_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, telegram_id, created_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, telegram_id BIGINT NOT NULL, created_at DATETIME NOT NULL, xui_email VARCHAR(64) NOT NULL)');
        $this->addSql('INSERT INTO users (id, telegram_id, created_at) SELECT id, telegram_id, created_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9CC0B3066 ON users (telegram_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E98B84D2EA ON users (xui_email)');
    }
}
