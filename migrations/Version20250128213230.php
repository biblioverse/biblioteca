<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250128213230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove duplicate synced books';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            DELETE FROM kobo_synced_book
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT MAX(id) AS id
                    FROM kobo_synced_book
                    GROUP BY book_id, kobo_device_id
                ) AS subquery
            )
        ');
    }

    public function down(Schema $schema): void
    {
    }
}
