<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240112163633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alter book.updated interaction.finished_date shelf.query_string end messenger';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book CHANGE updated updated DATE DEFAULT NULL, CHANGE image_path image_path VARCHAR(255) DEFAULT NULL, CHANGE image_filename image_filename VARCHAR(255) DEFAULT NULL, CHANGE serie_index serie_index DOUBLE PRECISION DEFAULT NULL, CHANGE language language VARCHAR(2) DEFAULT NULL, CHANGE publisher publisher VARCHAR(255) DEFAULT NULL, CHANGE publish_date publish_date DATE DEFAULT NULL, CHANGE image_extension image_extension VARCHAR(5) DEFAULT NULL, CHANGE serie serie VARCHAR(255) DEFAULT NULL, CHANGE authors authors JSON NOT NULL, CHANGE tags tags JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE book_interaction ADD finished_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE shelf CHANGE query_string query_string JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book CHANGE updated updated DATE DEFAULT \'NULL\', CHANGE image_path image_path VARCHAR(255) DEFAULT \'NULL\', CHANGE image_filename image_filename VARCHAR(255) DEFAULT \'NULL\', CHANGE serie serie VARCHAR(255) DEFAULT \'NULL\', CHANGE serie_index serie_index DOUBLE PRECISION DEFAULT \'NULL\', CHANGE language language VARCHAR(2) DEFAULT \'NULL\', CHANGE publisher publisher VARCHAR(255) DEFAULT \'NULL\', CHANGE publish_date publish_date DATE DEFAULT \'NULL\', CHANGE authors authors LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE image_extension image_extension VARCHAR(5) DEFAULT \'NULL\', CHANGE tags tags LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE `user` CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE book_interaction DROP finished_date');
        $this->addSql('ALTER TABLE shelf CHANGE query_string query_string LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
    }
}
