<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240219181604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add book.uuid + interaction.created+updated at';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book ADD uuid VARCHAR(36) DEFAULT NULL, CHANGE created created DATETIME NOT NULL, CHANGE updated updated DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CBE5A331D17F50A6 ON book (uuid)');
        $this->addSql('ALTER TABLE book_interaction ADD created DATETIME DEFAULT \'2024-01-12 00:00:00\' NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated DATETIME DEFAULT \'2024-01-12 00:00:00\' COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_CBE5A331D17F50A6 ON book');
        $this->addSql('ALTER TABLE book DROP uuid, CHANGE created created DATE NOT NULL, CHANGE updated updated DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE book_interaction DROP created, DROP updated');
    }
}
