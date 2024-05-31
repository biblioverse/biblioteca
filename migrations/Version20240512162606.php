<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240512162606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add book pagenumber and interactions.read_pages';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book ADD page_number INT DEFAULT NULL');
        $this->addSql('ALTER TABLE book_interaction ADD read_pages INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book_interaction DROP read_pages');
        $this->addSql('ALTER TABLE book DROP page_number');
    }
}
