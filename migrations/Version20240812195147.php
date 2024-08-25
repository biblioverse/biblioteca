<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240812195147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bookmarks';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bookmark_user (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, book_id INT NOT NULL, percent DOUBLE PRECISION DEFAULT NULL, source_percent DOUBLE PRECISION DEFAULT NULL, location_value VARCHAR(255) DEFAULT NULL, location_type VARCHAR(255) DEFAULT NULL, location_source VARCHAR(255) DEFAULT NULL,  updated DATETIME DEFAULT NULL, INDEX IDX_6F0BEE95A76ED395 (user_id), INDEX IDX_6F0BEE9516A2B381 (book_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bookmark_user ADD CONSTRAINT FK_6F0BEE95A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE bookmark_user ADD CONSTRAINT FK_6F0BEE9516A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bookmark_user DROP FOREIGN KEY FK_6F0BEE95A76ED395');
        $this->addSql('ALTER TABLE bookmark_user DROP FOREIGN KEY FK_6F0BEE9516A2B381');
        $this->addSql('DROP TABLE bookmark_user');
    }
}
