<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250123184640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align PHP attributes with Doctrine ones';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ai_model CHANGE type type VARCHAR(255) DEFAULT NULL, CHANGE model model VARCHAR(255) DEFAULT NULL, CHANGE url url VARCHAR(255) DEFAULT NULL, CHANGE use_epub_context use_epub_context TINYINT(1) DEFAULT 0 NOT NULL, CHANGE use_wikipedia_context use_wikipedia_context TINYINT(1) DEFAULT 0 NOT NULL, CHANGE use_amazon_context use_amazon_context TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE book_interaction CHANGE user_id user_id INT DEFAULT NULL, CHANGE book_id book_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE bookmark_user CHANGE user_id user_id INT DEFAULT NULL, CHANGE book_id book_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE instance_configuration CHANGE name name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE kobo_synced_book CHANGE book_id book_id INT DEFAULT NULL, CHANGE kobo_device_id kobo_device_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE opds_access CHANGE user_id user_id INT DEFAULT NULL, CHANGE token token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE username username VARCHAR(180) DEFAULT NULL, CHANGE language language VARCHAR(2) DEFAULT \'en\' NOT NULL, CHANGE use_kobo_devices use_kobo_devices TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bookmark_user CHANGE book_id book_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE ai_model CHANGE type type VARCHAR(255) NOT NULL, CHANGE model model VARCHAR(255) NOT NULL, CHANGE url url VARCHAR(255) NOT NULL, CHANGE use_epub_context use_epub_context TINYINT(1) NOT NULL, CHANGE use_wikipedia_context use_wikipedia_context TINYINT(1) NOT NULL, CHANGE use_amazon_context use_amazon_context TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE kobo_synced_book CHANGE book_id book_id INT NOT NULL, CHANGE kobo_device_id kobo_device_id INT NOT NULL');
        $this->addSql('ALTER TABLE opds_access CHANGE user_id user_id INT NOT NULL, CHANGE token token VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE instance_configuration CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE username username VARCHAR(180) NOT NULL, CHANGE language language VARCHAR(2) DEFAULT \'en\', CHANGE use_kobo_devices use_kobo_devices TINYINT(1) DEFAULT 1');
        $this->addSql('ALTER TABLE book_interaction CHANGE user_id user_id INT NOT NULL, CHANGE book_id book_id INT NOT NULL');
    }
}
