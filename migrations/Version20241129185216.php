<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241129185216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add upstream_sync on koboDevice';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kobo_device ADD upstream_sync TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kobo_device DROP upstream_sync');
    }
}
