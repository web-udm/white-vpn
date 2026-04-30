<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop sub_id from users: UUID is now generated at VPN connection creation time';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP COLUMN sub_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users ADD COLUMN sub_id VARCHAR(36) NOT NULL DEFAULT ''");
    }
}
