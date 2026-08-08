<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260808000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove stale AmneziaWG connections left over from the old wg-easy panel';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM vpn_connection WHERE type = 'amnezia_wireguard'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Peers of the old AmneziaWG panel cannot be restored.');
    }
}