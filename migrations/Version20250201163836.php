<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250201163836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'resync schema';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book CHANGE authors authors JSON NOT NULL, CHANGE tags tags JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE book_interaction CHANGE created created DATETIME DEFAULT \'2024-01-12 00:00:00\' NOT NULL, CHANGE updated updated DATETIME DEFAULT \'2024-01-12 00:00:00\'');
        $this->addSql('ALTER TABLE user DROP open_aikey, CHANGE roles roles JSON NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE available_at available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE book CHANGE authors authors LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', CHANGE tags tags LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE book_interaction CHANGE created created DATETIME DEFAULT \'2024-01-12 00:00:00\' NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT \'2024-01-12 00:00:00\' COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE `user` ADD open_aikey VARCHAR(255) DEFAULT NULL, CHANGE roles roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
    }
}
