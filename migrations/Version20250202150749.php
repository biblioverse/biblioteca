<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250202150749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Only one book interaction per user/book';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX unique_user_book ON book_interaction (user_id, book_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX unique_user_book ON book_interaction');
    }
}
