<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250128214238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique constraint to kobo_synced_book';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX kobo_synced_book_unique ON kobo_synced_book (book_id, kobo_device_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX kobo_synced_book_unique ON kobo_synced_book');
    }
}
