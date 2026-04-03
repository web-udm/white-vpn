<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403100002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename connection_requests to subscription_request';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE subscription_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, telegram_id BIGINT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO subscription_request SELECT * FROM connection_requests');
        $this->addSql('DROP TABLE connection_requests');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE connection_requests (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, telegram_id BIGINT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO connection_requests SELECT * FROM subscription_request');
        $this->addSql('DROP TABLE subscription_request');
    }
}
