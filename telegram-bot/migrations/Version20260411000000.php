<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactor vpn_connection: rename protocol->type, update vless->subscription, drop status and max_devices';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vpn_connection CHANGE protocol type VARCHAR(20) NOT NULL');
        $this->addSql("UPDATE vpn_connection SET type = 'subscription' WHERE type = 'vless'");
        $this->addSql('ALTER TABLE vpn_connection DROP COLUMN status');
        $this->addSql('ALTER TABLE vpn_connection DROP COLUMN max_devices');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vpn_connection CHANGE type protocol VARCHAR(20) NOT NULL');
        $this->addSql("UPDATE vpn_connection SET protocol = 'vless' WHERE protocol = 'subscription'");
        $this->addSql("ALTER TABLE vpn_connection ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'");
        $this->addSql('ALTER TABLE vpn_connection ADD COLUMN max_devices INT NOT NULL DEFAULT 1');
    }
}
