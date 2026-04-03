<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403100001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create subscription table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE subscription (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, is_vip BOOLEAN NOT NULL, status VARCHAR(20) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE subscription');
    }
}
