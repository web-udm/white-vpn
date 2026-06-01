<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove MTProxy connections from vpn_connection table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM vpn_connection WHERE type = 'mtproxy'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('MTProxy secrets cannot be restored.');
    }
}
