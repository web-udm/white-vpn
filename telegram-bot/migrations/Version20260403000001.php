<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE users (
                id INT AUTO_INCREMENT NOT NULL,
                telegram_id BIGINT NOT NULL,
                sub_id VARCHAR(36) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_1483A5E9E4C7AEA1 (telegram_id),
                UNIQUE INDEX UNIQ_1483A5E93427A7B3 (sub_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE subscription (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                is_vip TINYINT(1) NOT NULL,
                status VARCHAR(20) NOT NULL,
                expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE subscription_request (
                id INT AUTO_INCREMENT NOT NULL,
                telegram_id BIGINT NOT NULL,
                status VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (
                id BIGINT AUTO_INCREMENT NOT NULL,
                body LONGTEXT NOT NULL,
                headers LONGTEXT NOT NULL,
                queue_name VARCHAR(190) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_75EA56E0FB7336F0 (queue_name),
                INDEX IDX_75EA56E0E3BD61CE (available_at),
                INDEX IDX_75EA56E016BA31DB (delivered_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE subscription_request');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
