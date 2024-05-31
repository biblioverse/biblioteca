<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240121151215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add book.author shelf.query_string and user.display_series';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book CHANGE authors authors LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', CHANGE tags tags LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE shelf CHANGE query_string query_string LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE user ADD display_series TINYINT(1) DEFAULT 1 NOT NULL, ADD display_authors TINYINT(1) DEFAULT 1 NOT NULL, ADD display_tags TINYINT(1) DEFAULT 1 NOT NULL, ADD display_publishers TINYINT(1) DEFAULT 1 NOT NULL, ADD display_timeline TINYINT(1) DEFAULT 1 NOT NULL, ADD display_all_books TINYINT(1) DEFAULT 1 NOT NULL, CHANGE roles roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book CHANGE authors authors LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE tags tags LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE `user` DROP display_series, DROP display_authors, DROP display_tags, DROP display_publishers, DROP display_timeline, DROP display_all_books, CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE shelf CHANGE query_string query_string LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
    }
}
