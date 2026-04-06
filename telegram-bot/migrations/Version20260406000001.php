<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add vpn_connection table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE vpn_connection (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                subscription_id INT NOT NULL,
                protocol VARCHAR(20) NOT NULL,
                external_id VARCHAR(36) NOT NULL,
                max_devices INT NOT NULL,
                status VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id),
                CONSTRAINT FK_vpn_connection_subscription FOREIGN KEY (subscription_id) REFERENCES subscription(id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE vpn_connection');
    }
}
