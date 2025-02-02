<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250202073734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM book_interaction WHERE book_id is null or user_id is null');
        $this->addSql('ALTER TABLE book_interaction CHANGE user_id user_id INT NOT NULL, CHANGE book_id book_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book_interaction CHANGE user_id user_id INT DEFAULT NULL, CHANGE book_id book_id INT DEFAULT NULL');
    }
}
