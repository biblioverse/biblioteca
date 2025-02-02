<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250131184632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store lastSync token into kobo';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE kobo_device ADD last_sync_token LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE kobo_device CHANGE last_sync_token last_sync_token JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE kobo_device DROP last_sync_token');
    }
}
