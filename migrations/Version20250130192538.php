<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250130192538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add archived field on synced books';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kobo_synced_book ADD archived DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kobo_synced_book DROP archived');
    }
}
